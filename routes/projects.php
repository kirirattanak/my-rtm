<?php

use App\Http\Controllers\Projects\ProjectController;
use App\Http\Controllers\Projects\ProjectMemberController;
use App\Http\Controllers\Projects\ReportController;
use App\Http\Controllers\Projects\RtmController;
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
});
