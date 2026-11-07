<?php

use Illuminate\Support\Facades\Route;

// Route::view('/', 'welcome')->name('home');
Route::livewire('/', 'pages::home')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('/select-role', 'pages::auth.select-role')->name('roles.select');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/payments.php';
require __DIR__.'/exam.php';
require __DIR__.'/product.php';
require __DIR__.'/shop.php';
