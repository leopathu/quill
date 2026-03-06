<?php

use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectViewController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\TaskCommentController;
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
    Route::get('/project/{projectId}', [ProjectViewController::class, 'show'])->name('project.view');
    Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
    
    // Task routes under projects
    Route::prefix('project/{projectId}')->name('project.')->group(function () {
        Route::resource('tasks', TaskController::class)->except(['show', 'create', 'edit']);
        Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        
        // Board view routes
        Route::get('board', [BoardController::class, 'index'])->name('board.index');
        Route::put('board/{task}/status', [BoardController::class, 'updateStatus'])->name('board.updateStatus');
        
        // Task comment routes
        Route::post('tasks/{task}/comments', [TaskCommentController::class, 'store'])->name('tasks.comments.store');
        Route::delete('tasks/{task}/comments/{comment}', [TaskCommentController::class, 'destroy'])->name('tasks.comments.destroy');

        // Team management routes
        Route::get('team', [TeamController::class, 'index'])->name('team.index');
        Route::post('team', [TeamController::class, 'store'])->name('team.store');
        Route::put('team/{user}', [TeamController::class, 'update'])->name('team.update');
        Route::delete('team/{user}', [TeamController::class, 'destroy'])->name('team.destroy');
    });
});

require __DIR__.'/auth.php';
