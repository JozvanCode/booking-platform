<?php

use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BusinessHourController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:5,1');

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

// Authenticated
Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::put('/bookings/{id}', [BookingController::class, 'update']);
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);

    Route::get(
        '/services/{service}/availability',
        [AvailabilityController::class, 'index']
    );
});

// Provider
Route::middleware(['auth:sanctum', 'active', 'role:provider'])->group(function () {
    Route::patch('/bookings/{id}/confirm', [BookingController::class, 'confirm']);
    Route::patch('/bookings/{id}/complete', [BookingController::class, 'complete']);

    Route::post('/services', [ServiceController::class, 'store']);
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/{id}', [ServiceController::class, 'show']);
    Route::put('/services/{id}', [ServiceController::class, 'update']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);

    Route::get('/business-hours', [BusinessHourController::class, 'index']);
    Route::post('/business-hours', [BusinessHourController::class, 'store']);
    Route::put('/business-hours/{id}', [BusinessHourController::class, 'update']);
    Route::delete('/business-hours/{id}', [BusinessHourController::class, 'destroy']);
});

// Admin
Route::middleware(['auth:sanctum', 'active', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/stats', [AdminController::class, 'stats']);

        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/users/{id}', [AdminController::class, 'showUser']);
        Route::patch('/users/{id}/role', [AdminController::class, 'updateRole']);
        Route::patch('/users/{id}/status', [AdminController::class, 'updateStatus']);

        Route::get('/bookings', [AdminController::class, 'bookings']);

        Route::get('/services', [AdminController::class, 'services']);
        Route::delete('/services/{id}', [AdminController::class, 'deleteService']);

        Route::get('/audit-logs', [AdminController::class, 'auditLogs']);
    });

// Temporary test route
Route::middleware(['auth:sanctum', 'role:provider'])
    ->get('/provider-test', function () {
        return response()->json([
            'message' => 'Provider access granted',
        ]);
    });
