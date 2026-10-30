<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoRoleController;
use App\Http\Controllers\Teams\TeamInvitationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('welcome');
})->name('home');

// Quick Demo Role Switcher (One-click login / role change)
Route::post('/demo/switch-role', [DemoRoleController::class, 'switchRole'])->name('demo.switch-role');

// Authenticated application routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('pegel.dashboard');

    Route::prefix('{current_team}')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
    });

    // Time booking operations
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::post('/bookings/{booking}/approve', [BookingController::class, 'approve'])->name('bookings.approve');
    Route::post('/bookings/{booking}/reject', [BookingController::class, 'reject'])->name('bookings.reject');

    // Team invitations (starter kit compatibility)
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
