<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('/', '/dashboard')->name('home');
    Route::redirect('dashboard', '/projects')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/projects.php';
require __DIR__.'/auth.php';
