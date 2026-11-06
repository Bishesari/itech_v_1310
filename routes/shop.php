<?php
Route::livewire('/shop', 'pages::shop.index')->name('shop.index');

Route::livewire('/shop/{type}', 'pages::shop.products')->name('shop.products');

Route::livewire('/shop/product/{product:slug}', 'pages::shop.product')->name('shop.product');
