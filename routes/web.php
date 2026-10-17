<?php

use App\Http\Controllers\Api\V1\YoutubeConnectionController;
use App\Http\Controllers\Api\V1\YoutubeOAuthCallbackController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'app')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'app')->name('login');
    Route::view('/register', 'app')->name('register');
    Route::view('/forgot-password', 'app')->name('password.request');
    Route::view('/reset-password/{token}', 'app')->name('password.reset');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/api/v1/youtube/connection', [YoutubeConnectionController::class, 'store'])
        ->middleware('throttle:youtube-connection')
        ->name('api.v1.youtube.connection.store');
    Route::get('/api/v1/youtube/callback', YoutubeOAuthCallbackController::class)
        ->middleware('throttle:youtube-connection')
        ->name('api.v1.youtube.callback');

    Route::view('/dashboard', 'app')->name('dashboard');
    Route::view('/sermons', 'app')->name('sermons.index');
    Route::view('/sermons/new', 'app')->name('sermons.create');
    Route::view('/sermons/{sermon}', 'app')->whereNumber('sermon')->name('sermons.show');
    Route::view('/settings', 'app')->name('settings');
});

Route::view('/{path?}', 'app')
    ->where('path', '^(?!api(?:/|$)|sanctum(?:/|$)|up$).*$')
    ->name('app');
