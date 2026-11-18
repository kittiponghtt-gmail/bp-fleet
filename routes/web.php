<?php

use App\Http\Controllers\FleetController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::post('/setup', [LoginController::class, 'setup'])->middleware('throttle:5,1');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', [PageController::class, 'fleet']);
    Route::post('/logout', [LoginController::class, 'destroy']);

    Route::get('/password', [UserController::class, 'passwordForm']);
    Route::post('/password', [UserController::class, 'updatePassword']);

    Route::middleware('admin')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::post('/users/{user}/password', [UserController::class, 'resetPassword']);
        Route::post('/users/{user}/active', [UserController::class, 'setActive']);
        Route::post('/users/{user}/role', [UserController::class, 'setRole']);
        Route::post('/api/fleet/replace', [FleetController::class, 'replace']);
    });

    Route::get('/api/fleet', [FleetController::class, 'show']);

    Route::post('/api/vehicles', [FleetController::class, 'saveVehicle']);
    Route::put('/api/vehicles/{vehicle}', [FleetController::class, 'saveVehicle'])->where('vehicle', '.*');

    Route::post('/api/services', [FleetController::class, 'saveService']);
    Route::put('/api/services/{service}', [FleetController::class, 'saveService']);
    Route::delete('/api/services/{service}', [FleetController::class, 'deleteService']);

    Route::post('/api/rules', [FleetController::class, 'saveRule']);
    Route::put('/api/rules/{rule}', [FleetController::class, 'saveRule']);
    Route::patch('/api/rules/{rule}', [FleetController::class, 'setRuleActive']);

    Route::post('/api/odometer', [FleetController::class, 'saveOdometer']);
    Route::delete('/api/odometer/{log}', [FleetController::class, 'deleteOdometer']);

    Route::post('/api/downtime', [FleetController::class, 'saveDowntime']);
    Route::delete('/api/downtime/{downtime}', [FleetController::class, 'deleteDowntime']);

    Route::post('/api/issues/{issue}/resolve', [FleetController::class, 'resolveIssue']);
    Route::patch('/api/config', [FleetController::class, 'setConfig']);
});
