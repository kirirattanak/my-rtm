<?php

use App\Http\Controllers\Org\OrgRoleController;
use App\Http\Controllers\Org\OrgUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('org')->name('org.')->group(function () {
    // User & invitation management (org owner + system admin)
    Route::get('users', [OrgUserController::class, 'index'])->name('users.index');
    Route::post('invitations', [OrgUserController::class, 'invite'])->name('invitations.store');
    Route::delete('invitations/{invitation}', [OrgUserController::class, 'revokeInvite'])->name('invitations.destroy');
    Route::patch('users/{user}/role', [OrgUserController::class, 'updateRole'])->name('users.update-role');
    Route::patch('users/{user}/toggle-active', [OrgUserController::class, 'toggleActive'])->name('users.toggle-active');

    // Role & permission management (org owner + system admin)
    Route::get('roles', [OrgRoleController::class, 'index'])->name('roles.index');
    Route::post('roles', [OrgRoleController::class, 'store'])->name('roles.store');
    Route::patch('roles/{role}', [OrgRoleController::class, 'update'])->name('roles.update');
    Route::delete('roles/{role}', [OrgRoleController::class, 'destroy'])->name('roles.destroy');
    Route::get('roles/{role}/permissions', [OrgRoleController::class, 'editPermissions'])->name('roles.permissions.edit');
    Route::put('roles/{role}/permissions', [OrgRoleController::class, 'updatePermissions'])->name('roles.permissions.update');
});
