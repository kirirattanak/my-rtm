<?php

use App\Http\Controllers\Projects\CoverageController;
use App\Http\Controllers\Projects\TcLinkController;
use App\Http\Controllers\Projects\TestCaseController;
use App\Http\Controllers\Projects\TestRunController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('projects/{project}')->name('projects.')->group(function () {

    // Test Cases
    Route::prefix('test-cases')->name('test-cases.')->group(function () {
        Route::get('/',                    [TestCaseController::class, 'index'])->name('index');
        Route::get('/create',              [TestCaseController::class, 'create'])->name('create');
        Route::post('/',                   [TestCaseController::class, 'store'])->name('store');
        Route::get('/{testCase}',          [TestCaseController::class, 'show'])->name('show');
        Route::get('/{testCase}/edit',     [TestCaseController::class, 'edit'])->name('edit');
        Route::patch('/{testCase}',        [TestCaseController::class, 'update'])->name('update');
        Route::delete('/{testCase}',       [TestCaseController::class, 'destroy'])->name('destroy');

        // Test runs
        Route::post('/{testCase}/runs',    [TestRunController::class, 'store'])->name('runs.store');

        // TR links
        Route::post('/{testCase}/tr-links',                             [TcLinkController::class, 'store'])->name('tr-links.store');
        Route::delete('/{testCase}/tr-links/{technicalRequirement}',    [TcLinkController::class, 'destroy'])->name('tr-links.destroy');
    });

    // Coverage
    Route::get('/coverage', [CoverageController::class, 'show'])->name('coverage');
});
