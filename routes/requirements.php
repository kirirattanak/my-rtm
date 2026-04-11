<?php

use App\Http\Controllers\Projects\BusinessRequirementController;
use App\Http\Controllers\Projects\CommentController;
use App\Http\Controllers\Projects\TechnicalRequirementController;
use App\Http\Controllers\Projects\TrLinkController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('projects/{project}/requirements')->name('projects.requirements.')->group(function () {

    // Business Requirements
    Route::prefix('business')->name('business.')->group(function () {
        Route::get('/', [BusinessRequirementController::class, 'index'])->name('index');
        Route::get('/create', [BusinessRequirementController::class, 'create'])->name('create');
        Route::post('/', [BusinessRequirementController::class, 'store'])->name('store');
        Route::get('/import', [BusinessRequirementController::class, 'importCreate'])->name('import');
        Route::post('/import', [BusinessRequirementController::class, 'import'])->name('import.store');
        Route::get('/{businessRequirement}', [BusinessRequirementController::class, 'show'])->name('show');
        Route::get('/{businessRequirement}/edit', [BusinessRequirementController::class, 'edit'])->name('edit');
        Route::patch('/{businessRequirement}', [BusinessRequirementController::class, 'update'])->name('update');
        Route::delete('/{businessRequirement}', [BusinessRequirementController::class, 'destroy'])->name('destroy');

        // TR links on a BR
        Route::post('/{businessRequirement}/tr-links', [TrLinkController::class, 'store'])->name('tr-links.store');
        Route::delete('/{businessRequirement}/tr-links/{technicalRequirement}', [TrLinkController::class, 'destroy'])->name('tr-links.destroy');

        // Comments on a BR
        Route::post('/{businessRequirement}/comments', [CommentController::class, 'storeBr'])->name('comments.store');
    });

    // Technical Requirements
    Route::prefix('technical')->name('technical.')->group(function () {
        Route::get('/', [TechnicalRequirementController::class, 'index'])->name('index');
        Route::get('/create', [TechnicalRequirementController::class, 'create'])->name('create');
        Route::post('/', [TechnicalRequirementController::class, 'store'])->name('store');
        Route::get('/import', [TechnicalRequirementController::class, 'importCreate'])->name('import');
        Route::post('/import', [TechnicalRequirementController::class, 'import'])->name('import.store');
        Route::get('/{technicalRequirement}', [TechnicalRequirementController::class, 'show'])->name('show');
        Route::get('/{technicalRequirement}/edit', [TechnicalRequirementController::class, 'edit'])->name('edit');
        Route::patch('/{technicalRequirement}', [TechnicalRequirementController::class, 'update'])->name('update');
        Route::delete('/{technicalRequirement}', [TechnicalRequirementController::class, 'destroy'])->name('destroy');

        // Comments on a TR
        Route::post('/{technicalRequirement}/comments', [CommentController::class, 'storeTr'])->name('comments.store');
    });

    // Comments (delete — model-agnostic)
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
});
