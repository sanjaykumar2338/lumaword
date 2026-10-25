<?php

use App\Http\Controllers\Api\V1\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\V1\Auth\AuthenticationStatusController;
use App\Http\Controllers\Api\V1\Auth\NewPasswordController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetLinkController;
use App\Http\Controllers\Api\V1\Auth\RegisteredUserController;
use App\Http\Controllers\Api\V1\AuthenticatedUserController;
use App\Http\Controllers\Api\V1\DailyActivityController;
use App\Http\Controllers\Api\V1\DevotionalActivityController;
use App\Http\Controllers\Api\V1\DevotionalController;
use App\Http\Controllers\Api\V1\DevotionalReminderPreferenceController;
use App\Http\Controllers\Api\V1\PasswordController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\PushSubscriptionController;
use App\Http\Controllers\Api\V1\ReadingPlanController;
use App\Http\Controllers\Api\V1\ReadingPlanProgressController;
use App\Http\Controllers\Api\V1\ScriptureController;
use App\Http\Controllers\Api\V1\SermonController;
use App\Http\Controllers\Api\V1\SermonConversationController;
use App\Http\Controllers\Api\V1\SermonMediaController;
use App\Http\Controllers\Api\V1\SermonMessageController;
use App\Http\Controllers\Api\V1\SermonNotesController;
use App\Http\Controllers\Api\V1\SermonProcessingController;
use App\Http\Controllers\Api\V1\SermonStatusController;
use App\Http\Controllers\Api\V1\SermonTranscriptController;
use App\Http\Controllers\Api\V1\TestPushNotificationController;
use App\Http\Controllers\Api\V1\UserReadingPlanController;
use App\Http\Controllers\Api\V1\YoutubeConnectionController;
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

        Route::get('/scriptures', ScriptureController::class)->name('scriptures.index');
        Route::get('/devotionals/today', [DevotionalController::class, 'today'])->name('devotionals.today');
        Route::post('/devotionals/activity', DevotionalActivityController::class)->name('devotionals.activity.store');
        Route::apiResource('devotionals', DevotionalController::class)->only(['index', 'store', 'show'])
            ->middlewareFor('store', 'throttle:devotional-generation');

        Route::get('/daily-activity', DailyActivityController::class)->name('daily-activity.show');
        Route::get('/devotional-reminder-preferences', [DevotionalReminderPreferenceController::class, 'show'])
            ->name('devotional-reminder-preferences.show');
        Route::put('/devotional-reminder-preferences', [DevotionalReminderPreferenceController::class, 'update'])
            ->name('devotional-reminder-preferences.update');
        Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])
            ->name('push-subscriptions.store');
        Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])
            ->name('push-subscriptions.destroy');
        Route::post('/push-subscriptions/test', TestPushNotificationController::class)
            ->middleware('throttle:test-push-notification')
            ->name('push-subscriptions.test');

        Route::apiResource('reading-plans', ReadingPlanController::class)
            ->only(['index', 'show'])
            ->parameters(['reading-plans' => 'readingPlan']);
        Route::get('/reading-plan-enrollments', [UserReadingPlanController::class, 'index'])
            ->name('reading-plan-enrollments.index');
        Route::get('/reading-plan-enrollments/{userReadingPlan}', [UserReadingPlanController::class, 'show'])
            ->name('reading-plan-enrollments.show');
        Route::post('/reading-plans/{readingPlan}/enrollments', [UserReadingPlanController::class, 'store'])
            ->name('reading-plans.enrollments.store');
        Route::put('/reading-plan-enrollments/{userReadingPlan}/days/{readingPlanDay}', [ReadingPlanProgressController::class, 'update'])
            ->name('reading-plan-enrollments.days.update');

        Route::apiResource('sermons', SermonController::class)
            ->middlewareFor('store', 'throttle:sermon-submission');
        Route::post('/sermons/{sermon}/transcript', SermonTranscriptController::class)
            ->middleware('throttle:sermon-submission')
            ->name('sermons.transcript.store');
        Route::post('/sermons/{sermon}/media', SermonMediaController::class)
            ->middleware('throttle:sermon-submission')
            ->name('sermons.media.store');
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

        Route::get('/youtube/connection', [YoutubeConnectionController::class, 'show'])
            ->name('youtube.connection.show');
        Route::delete('/youtube/connection', [YoutubeConnectionController::class, 'destroy'])
            ->middleware('throttle:youtube-connection')
            ->name('youtube.connection.destroy');
    });
});
