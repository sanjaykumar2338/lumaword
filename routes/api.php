<?php

use App\Http\Controllers\Api\V1\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\V1\Auth\AuthenticationStatusController;
use App\Http\Controllers\Api\V1\Auth\NewPasswordController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetLinkController;
use App\Http\Controllers\Api\V1\Auth\RegisteredUserController;
use App\Http\Controllers\Api\V1\AuthenticatedUserController;
use App\Http\Controllers\Api\V1\PasswordController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SermonController;
use App\Http\Controllers\Api\V1\SermonConversationController;
use App\Http\Controllers\Api\V1\SermonMessageController;
use App\Http\Controllers\Api\V1\SermonNotesController;
use App\Http\Controllers\Api\V1\SermonProcessingController;
use App\Http\Controllers\Api\V1\SermonStatusController;
use App\Http\Controllers\Api\V1\SermonTranscriptController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('/auth/status', AuthenticationStatusController::class)->name('auth.status');

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

        Route::apiResource('sermons', SermonController::class)
            ->middlewareFor('store', 'throttle:sermon-submission');
        Route::post('/sermons/{sermon}/transcript', SermonTranscriptController::class)
            ->middleware('throttle:sermon-submission')
            ->name('sermons.transcript.store');
        Route::post('/sermons/{sermon}/process', SermonProcessingController::class)
            ->middleware('throttle:sermon-processing')
            ->name('sermons.process');
        Route::get('/sermons/{sermon}/status', SermonStatusController::class)->name('sermons.status');
        Route::get('/sermons/{sermon}/notes', SermonNotesController::class)->name('sermons.notes');
        Route::get('/sermons/{sermon}/conversation', [SermonConversationController::class, 'show'])
            ->name('sermons.conversation.show');
        Route::get('/sermons/{sermon}/conversation/messages', [SermonMessageController::class, 'index'])
            ->name('sermons.conversation.messages.index');
        Route::post('/sermons/{sermon}/conversation/messages', [SermonMessageController::class, 'store'])
            ->middleware('throttle:sermon-questions')
            ->name('sermons.conversation.messages.store');
    });
});
