<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DeleteAccountRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        $user = $request->user();
        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->subscriptions()->delete();
            $user->delete();
        });

        return response()->json(['message' => 'Account permanently deleted.']);
    }
}
