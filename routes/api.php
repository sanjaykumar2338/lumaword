<?php

use App\Http\Controllers\Api\V1\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\V1\Auth\NewPasswordController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetLinkController;
use App\Http\Controllers\Api\V1\Auth\RegisteredUserController;
use App\Http\Controllers\Api\V1\AuthenticatedUserController;
use App\Http\Controllers\Api\V1\PasswordController;
use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::middleware('guest:web')->group(function (): void {
        Route::post('/auth/register', RegisteredUserController::class)
            ->middleware('throttle:registration')
            ->name('auth.register');
        Route::post('/auth/login', [AuthenticatedSessionController::class, 'store'])
            ->middleware('throttle:login')
            ->name('auth.login');
        Route::post('/auth/forgot-password', PasswordResetLinkController::class)
            ->middleware('throttle:password-reset-link')
            ->name('auth.password.email');
        Route::post('/auth/reset-password', NewPasswordController::class)
            ->middleware('throttle:password-reset')
            ->name('auth.password.update');
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/user', [AuthenticatedUserController::class, 'show'])->name('user.show');
        Route::patch('/user/profile', [ProfileController::class, 'update'])->name('user.profile.update');
        Route::put('/user/password', [PasswordController::class, 'update'])->name('user.password.update');
        Route::post('/auth/logout', [AuthenticatedSessionController::class, 'destroy'])->name('auth.logout');
    });
});
