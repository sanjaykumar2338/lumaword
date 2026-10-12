<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'app')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'app')->name('login');
    Route::view('/register', 'app')->name('register');
    Route::view('/forgot-password', 'app')->name('password.request');
    Route::view('/reset-password/{token}', 'app')->name('password.reset');
});

Route::middleware('auth')->group(function (): void {
    Route::view('/dashboard', 'app')->name('dashboard');
    Route::view('/sermons', 'app')->name('sermons.index');
    Route::view('/sermons/new', 'app')->name('sermons.create');
    Route::view('/sermons/{sermon}', 'app')->whereNumber('sermon')->name('sermons.show');
    Route::view('/settings', 'app')->name('settings');
});

Route::view('/{path?}', 'app')
    ->where('path', '^(?!api(?:/|$)|sanctum(?:/|$)|up$).*$')
    ->name('app');
