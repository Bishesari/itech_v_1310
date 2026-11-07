<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::livewire('/products/create-written-questions', 'pages::products.create-written-questions')
        ->name('products.create.written-questions');

    Route::livewire('/products/create-mock-exam', 'pages::products.create-mock-exam')
        ->name('products.create.mock-exam');
});
