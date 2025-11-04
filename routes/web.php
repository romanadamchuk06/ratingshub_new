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

require __DIR__.'/settings.php';
