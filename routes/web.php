<?php

use App\Http\Controllers\PlatformController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Test routes for error pages (remove in production)
Route::get('/404', function () {
    return Inertia::render('errors/404');
});

Route::get('/500', function () {
    return Inertia::render('errors/500');
});

Route::get('/503', function () {
    return Inertia::render('errors/503');
});

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('reviews', function () {
    return Inertia::render('Reviews', [
        'reviews' => [], // TODO: Fetch actual reviews from database
    ]);
})->middleware(['auth', 'verified'])->name('reviews');

// Platform OAuth Routes
Route::middleware(['auth', 'verified'])->prefix('platforms')->name('platforms.')->group(function () {
    Route::get('/connect/{provider}', [PlatformController::class, 'connect'])->name('connect');
    Route::get('/callback/{provider}', [PlatformController::class, 'callback'])->name('callback');
    Route::delete('/{platform}', [PlatformController::class, 'disconnect'])->name('disconnect');
});

// Admin Routes
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalUsers' => \App\Models\User::count(),
                'totalPlatforms' => \App\Models\ConnectedPlatform::count(),
                'totalAdmins' => \App\Models\User::where('is_admin', true)->count(),
            ],
        ]);
    })->name('dashboard');

    // User Management
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
    Route::post('users/{user}/toggle-admin', [\App\Http\Controllers\Admin\UserController::class, 'toggleAdmin'])->name('users.toggle-admin');
});

require __DIR__.'/settings.php';
