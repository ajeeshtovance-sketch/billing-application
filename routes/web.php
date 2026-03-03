<?php

use App\Http\Controllers\Web\AdminWebController;
use App\Http\Controllers\Web\SuperAdminWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Auth middleware redirects here when unauthenticated
Route::get('/login', fn () => redirect()->route('admin.login'))->name('login');

// Organization / Sub-Admin - Web Login & Dashboard
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminWebController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminWebController::class, 'login']);

    Route::middleware(['auth', 'org_admin_web'])->group(function () {
        Route::get('dashboard', [AdminWebController::class, 'dashboard'])->name('dashboard');
        Route::get('users', [AdminWebController::class, 'usersIndex'])->name('users.index');
        Route::post('users', [AdminWebController::class, 'usersStore'])->name('users.store');
        Route::post('logout', [AdminWebController::class, 'logout'])->name('logout');
    });
});

// Super Admin - Web Dashboard (session-based)
Route::prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('login', [SuperAdminWebController::class, 'showLogin'])->name('login');
    Route::post('login', [SuperAdminWebController::class, 'login']);

    Route::middleware(['auth', 'super_admin_web'])->group(function () {
        Route::get('dashboard', [SuperAdminWebController::class, 'dashboard'])->name('dashboard');
        Route::post('logout', [SuperAdminWebController::class, 'logout'])->name('logout');

        // Roles & Permissions
        Route::get('roles', [SuperAdminWebController::class, 'rolesIndex'])->name('roles.index');
        Route::get('roles/create', [SuperAdminWebController::class, 'rolesCreate'])->name('roles.create');
        Route::post('roles', [SuperAdminWebController::class, 'rolesStore'])->name('roles.store');
        Route::get('roles/{role}/edit', [SuperAdminWebController::class, 'rolesEdit'])->name('roles.edit');
        Route::put('roles/{role}', [SuperAdminWebController::class, 'rolesUpdate'])->name('roles.update');
        Route::delete('roles/{role}', [SuperAdminWebController::class, 'rolesDestroy'])->name('roles.destroy');

        Route::get('permissions', [SuperAdminWebController::class, 'permissionsIndex'])->name('permissions.index');

        // Organizations
        Route::get('organizations', [SuperAdminWebController::class, 'organizationsIndex'])->name('organizations.index');
        Route::get('organizations/create', [SuperAdminWebController::class, 'organizationsCreate'])->name('organizations.create');
        Route::post('organizations', [SuperAdminWebController::class, 'organizationsStore'])->name('organizations.store');
        Route::get('organizations/{organization}/edit', [SuperAdminWebController::class, 'organizationsEdit'])->name('organizations.edit');
        Route::put('organizations/{organization}', [SuperAdminWebController::class, 'organizationsUpdate'])->name('organizations.update');
        Route::post('organizations/{organization}/users', [SuperAdminWebController::class, 'organizationsAddUser'])->name('organizations.add-user');
        Route::delete('organizations/{organization}', [SuperAdminWebController::class, 'organizationsDestroy'])->name('organizations.destroy');
    });
});
