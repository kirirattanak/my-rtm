<?php

use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\Projects\ProjectMemberController;
use App\Http\Controllers\Projects\ReportController;
use App\Http\Controllers\Projects\RtmController;
use App\Http\Controllers\Projects\TestSuiteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('projects', ProjectController::class);

    Route::prefix('projects/{project}/members')->name('projects.members.')->group(function () {
        Route::get('/', [ProjectMemberController::class, 'index'])->name('index');
        Route::post('/', [ProjectMemberController::class, 'store'])->name('store');
        Route::patch('/{member}', [ProjectMemberController::class, 'update'])->name('update');
        Route::delete('/{member}', [ProjectMemberController::class, 'destroy'])->name('destroy');
    });

    Route::get('projects/{project}/rtm', [RtmController::class, 'index'])->name('projects.rtm');
    Route::get('projects/{project}/rtm/export', [RtmController::class, 'export'])->name('projects.rtm.export');

    Route::get('projects/{project}/reports', [ReportController::class, 'health'])->name('projects.reports');

    Route::get('projects/{project}/test-suites', [TestSuiteController::class, 'index'])->name('projects.test-suites.index');
    Route::get('projects/{project}/test-suites/create', [TestSuiteController::class, 'create'])->name('projects.test-suites.create');
    Route::post('projects/{project}/test-suites', [TestSuiteController::class, 'store'])->name('projects.test-suites.store');
    Route::get('projects/{project}/test-suites/{testSuite}', [TestSuiteController::class, 'show'])->name('projects.test-suites.show');
    Route::delete('projects/{project}/test-suites/{testSuite}', [TestSuiteController::class, 'destroy'])->name('projects.test-suites.destroy');
});
