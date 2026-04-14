<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('/', '/dashboard')->name('home');
    Route::redirect('dashboard', '/projects')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/org.php';
require __DIR__.'/projects.php';
require __DIR__.'/requirements.php';
require __DIR__.'/test-cases.php';
require __DIR__.'/capacity.php';
require __DIR__.'/notifications.php';
require __DIR__.'/auth.php';
