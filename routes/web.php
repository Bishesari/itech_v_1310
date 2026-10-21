<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});



Route::livewire('log-reg', 'pages::auth.log-reg')->name('login-register')->middleware('guest');

require __DIR__.'/settings.php';
