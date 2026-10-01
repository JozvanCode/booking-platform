<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BusinessHourController;
use App\Http\Controllers\Api\HelloController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Support\Facades\Route;

// Public
Route::get('/hello', [HelloController::class, 'index']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Authenticated
Route::middleware('auth:sanctum')->group(function () {
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
Route::middleware(['auth:sanctum', 'role:provider'])->group(function () {
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

// Temporary test route
Route::middleware(['auth:sanctum', 'role:provider'])
    ->get('/provider-test', function () {
        return response()->json([
            'message' => 'Provider access granted',
        ]);
    });
