<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DeleteDeviceTokenRequest;
use App\Http\Requests\Api\V1\StoreDeviceTokenRequest;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;

class DeviceTokenController extends Controller
{
    public function store(StoreDeviceTokenRequest $request): JsonResponse
    {
        $data = $request->validated();
        $token = $request->user()->deviceTokens()->updateOrCreate(
            ['provider' => $data['provider'], 'token_hash' => hash('sha256', $data['token'])],
            [...$data, 'token_hash' => hash('sha256', $data['token']), 'last_seen_at' => now()],
        );

        return response()->json(['data' => $this->data($token)], $token->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(DeleteDeviceTokenRequest $request): JsonResponse
    {
        $data = $request->validated();
        $request->user()->deviceTokens()
            ->where('provider', $data['provider'])
            ->where('token_hash', hash('sha256', $data['token']))
            ->delete();

        return response()->json(['message' => 'Device notifications disabled.']);
    }

    /** @return array<string, mixed> */
    private function data(DeviceToken $token): array
    {
        return [
            'id' => $token->id,
            'provider' => $token->provider,
            'platform' => $token->platform,
            'app_version' => $token->app_version,
            'last_seen_at' => $token->last_seen_at?->toISOString(),
        ];
    }
}
