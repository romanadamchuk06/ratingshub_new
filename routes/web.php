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

Route::get('dashboard', function () {
    $hasGoogleConnected = auth()->user()->connectedPlatforms()
        ->where('provider', 'google')
        ->where('is_active', true)
        ->exists();

    return Inertia::render('Dashboard', [
        'hasGoogleConnected' => $hasGoogleConnected,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// Platform OAuth Routes
Route::middleware(['auth', 'verified'])->prefix('platforms')->name('platforms.')->group(function () {
    Route::get('/connect/{provider}', [PlatformController::class, 'connect'])->name('connect');
    Route::get('/callback/{provider}', [PlatformController::class, 'callback'])->name('callback');
    Route::delete('/{platform}', [PlatformController::class, 'disconnect'])->name('disconnect');
});

require __DIR__.'/settings.php';
