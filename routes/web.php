<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('/select-role', 'pages::roles.select')
        ->name('roles.select');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

