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
use App\Http\Controllers\TaskTimeLogController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\DocumentController;
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
    Route::get('/reports', [ReportsController::class, 'global'])->name('reports.global');
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

        // Task time log routes
        Route::post('tasks/{task}/time-logs', [TaskTimeLogController::class, 'store'])->name('tasks.time-logs.store');
        Route::put('tasks/{task}/time-logs/{timeLog}', [TaskTimeLogController::class, 'update'])->name('tasks.time-logs.update');
        Route::delete('tasks/{task}/time-logs/{timeLog}', [TaskTimeLogController::class, 'destroy'])->name('tasks.time-logs.destroy');

        // Project time report
        Route::get('reports', [ReportsController::class, 'project'])->name('reports.project');

        // Document routes
        Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
        Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
        Route::put('documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
        Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

        // Team management routes
        Route::get('team', [TeamController::class, 'index'])->name('team.index');
        Route::post('team', [TeamController::class, 'store'])->name('team.store');
        Route::put('team/{user}', [TeamController::class, 'update'])->name('team.update');
        Route::delete('team/{user}', [TeamController::class, 'destroy'])->name('team.destroy');
    });
});

require __DIR__.'/auth.php';
