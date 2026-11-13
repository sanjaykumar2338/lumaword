<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MobileAuthenticationController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:120'],
        ]);

        $user = User::query()->where('email', strtolower($credentials['email']))->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        return $this->tokenResponse($user, $credentials['device_name'], 'Signed in successfully.');
    }

    public function register(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'device_name' => ['required', 'string', 'max:120'],
        ]);

        $user = User::create([
            'name' => trim($credentials['name']),
            'email' => strtolower($credentials['email']),
            'password' => $credentials['password'],
        ]);

        return $this->tokenResponse($user, $credentials['device_name'], 'Welcome to Luma.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Signed out successfully.']);
    }

    private function tokenResponse(User $user, string $deviceName, string $message): JsonResponse
    {
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'data' => UserResource::make($user)->resolve(request()),
            'token' => $token,
            'message' => $message,
        ]);
    }
}
