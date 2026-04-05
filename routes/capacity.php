<?php

use App\Http\Controllers\Projects\SprintController;
use App\Http\Controllers\Projects\TaskController;
use App\Http\Controllers\Projects\TaskLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('projects/{project}')->name('projects.')->group(function () {

    // Sprints
    Route::prefix('sprints')->name('sprints.')->group(function () {
        Route::get('/',              [SprintController::class, 'index'])->name('index');
        Route::get('/create',        [SprintController::class, 'create'])->name('create');
        Route::post('/',             [SprintController::class, 'store'])->name('store');
        Route::get('/{sprint}',      [SprintController::class, 'show'])->name('show');
        Route::get('/{sprint}/edit', [SprintController::class, 'edit'])->name('edit');
        Route::patch('/{sprint}',    [SprintController::class, 'update'])->name('update');
        Route::delete('/{sprint}',   [SprintController::class, 'destroy'])->name('destroy');
    });

    // Tasks
    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/',            [TaskController::class, 'index'])->name('index');
        Route::get('/create',      [TaskController::class, 'create'])->name('create');
        Route::post('/',           [TaskController::class, 'store'])->name('store');
        Route::get('/{task}',      [TaskController::class, 'show'])->name('show');
        Route::get('/{task}/edit', [TaskController::class, 'edit'])->name('edit');
        Route::patch('/{task}',    [TaskController::class, 'update'])->name('update');
        Route::delete('/{task}',   [TaskController::class, 'destroy'])->name('destroy');

        // Inline status update
        Route::patch('/{task}/status', [TaskController::class, 'updateStatus'])->name('status.update');

        // Log actual hours
        Route::post('/{task}/logs', [TaskLogController::class, 'store'])->name('logs.store');
    });
});
