<?php

namespace App\Services;

use App\Contracts\StorePurchaseVerifier;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class ApplePurchaseVerifier implements StorePurchaseVerifier
{
    public function verify(string $productId, string $transactionId, string $verificationData): array
    {
        if (! config('mobile_purchases.app_store.enabled')) {
            throw ValidationException::withMessages(['store' => 'Apple purchase verification is not configured yet.']);
        }

        $token = $this->jwt();
        $response = Http::withToken($token)->connectTimeout(3)->timeout(10)
            ->get('https://api.storekit.itunes.apple.com/inApps/v1/transactions/'.rawurlencode($transactionId));
        if ($response->status() === 404 && $response->json('errorCode') === 4040010) {
            $response = Http::withToken($token)->connectTimeout(3)->timeout(10)
                ->get('https://api.storekit-sandbox.apple.com/inApps/v1/transactions/'.rawurlencode($transactionId));
        }
        $response->throw();
        $signedTransaction = $response->json('signedTransactionInfo');
        if (! is_string($signedTransaction) || ! $this->verifyJws($signedTransaction)) {
            throw ValidationException::withMessages(['verification_data' => 'Apple returned an unverifiable transaction.']);
        }

        $transaction = $this->jwsPayload($signedTransaction);
        if (($transaction['bundleId'] ?? null) !== config('mobile_purchases.app_store.bundle_id')
            || ($transaction['productId'] ?? null) !== $productId
            || ($transaction['transactionId'] ?? null) !== $transactionId) {
            throw ValidationException::withMessages(['verification_data' => 'Apple transaction details do not match the purchase.']);
        }

        $expiresAt = isset($transaction['expiresDate']) ? now()->createFromTimestampMs((int) $transaction['expiresDate']) : null;

        return [
            'status' => ! isset($transaction['revocationDate']) && ($expiresAt === null || $expiresAt->isFuture()) ? 'active' : 'expired',
            'product_id' => $productId, 'transaction_id' => $transactionId,
            'original_transaction_id' => $transaction['originalTransactionId'] ?? null,
            'purchased_at' => isset($transaction['purchaseDate']) ? now()->createFromTimestampMs((int) $transaction['purchaseDate'])->toISOString() : null,
            'expires_at' => $expiresAt?->toISOString(), 'response' => ['signed_transaction_info' => $signedTransaction],
        ];
    }

    private function jwt(): string
    {
        $header = $this->base64Url(json_encode(['alg' => 'ES256', 'kid' => config('mobile_purchases.app_store.key_id'), 'typ' => 'JWT']));
        $claims = $this->base64Url(json_encode([
            'iss' => config('mobile_purchases.app_store.issuer_id'), 'iat' => now()->timestamp,
            'exp' => now()->addMinutes(5)->timestamp, 'aud' => 'appstoreconnect-v1', 'bid' => config('mobile_purchases.app_store.bundle_id'),
        ]));
        $unsigned = $header.'.'.$claims;
        if (! openssl_sign($unsigned, $signature, (string) config('mobile_purchases.app_store.private_key'), OPENSSL_ALGO_SHA256)) {
            throw ValidationException::withMessages(['store' => 'Apple signing credentials are invalid.']);
        }

        return $unsigned.'.'.$this->base64Url($this->derToJose($signature));
    }

    private function verifyJws(string $jws): bool
    {
        [$header, $payload, $signature] = array_pad(explode('.', $jws, 3), 3, '');
        $decodedHeader = json_decode($this->base64UrlDecode($header), true);
        $certificates = $decodedHeader['x5c'] ?? [];
        $root = config('mobile_purchases.app_store.root_certificate');
        if (! is_array($certificates) || $certificates === [] || ! collect($certificates)->every('is_string') || ! is_string($root) || $root === '') {
            return false;
        }
        $chain = array_map(fn (string $certificate) => openssl_x509_read(
            "-----BEGIN CERTIFICATE-----\n".chunk_split($certificate, 64, "\n").'-----END CERTIFICATE-----',
        ), $certificates);
        $rootCertificate = openssl_x509_read($root);
        $chainIsValid = $rootCertificate !== false;
        foreach ($chain as $index => $certificate) {
            $issuer = $chain[$index + 1] ?? $rootCertificate;
            $chainIsValid = $chainIsValid && $certificate !== false && $issuer !== false && openssl_x509_verify($certificate, $issuer) === 1;
        }

        return $chainIsValid && $chain[0] !== false
            && openssl_verify($header.'.'.$payload, $this->joseToDer($this->base64UrlDecode($signature)), $chain[0], OPENSSL_ALGO_SHA256) === 1;
    }

    /** @return array<string, mixed> */
    private function jwsPayload(string $jws): array
    {
        $parts = explode('.', $jws);
        $payload = json_decode($this->base64UrlDecode($parts[1] ?? ''), true);

        return is_array($payload) ? $payload : [];
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/').'==', true) ?: '';
    }

    private function derToJose(string $der): string
    {
        $offset = 2;
        $rLength = ord($der[$offset + 1]);
        $r = substr($der, $offset + 2, $rLength);
        $offset += 2 + $rLength;
        $sLength = ord($der[$offset + 1]);
        $s = substr($der, $offset + 2, $sLength);

        return str_pad(ltrim($r, "\0"), 32, "\0", STR_PAD_LEFT).str_pad(ltrim($s, "\0"), 32, "\0", STR_PAD_LEFT);
    }

    private function joseToDer(string $jose): string
    {
        $r = ltrim(substr($jose, 0, 32), "\0") ?: "\0";
        $s = ltrim(substr($jose, 32, 32), "\0") ?: "\0";
        if (ord($r[0]) & 0x80) {
            $r = "\0".$r;
        }
        if (ord($s[0]) & 0x80) {
            $s = "\0".$s;
        }
        $body = "\x02".chr(strlen($r)).$r."\x02".chr(strlen($s)).$s;

        return "\x30".chr(strlen($body)).$body;
    }
}
