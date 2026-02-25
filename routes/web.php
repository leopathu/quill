<?php

use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->middleware(['auth', 'verified']);

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/organization/settings', [OrganizationController::class, 'edit'])->name('organization.edit');
    Route::post('/organization/settings', [OrganizationController::class, 'update'])->name('organization.update');

    Route::resource('projects', ProjectController::class)->except(['show', 'create', 'edit']);
    Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
});

require __DIR__.'/auth.php';
