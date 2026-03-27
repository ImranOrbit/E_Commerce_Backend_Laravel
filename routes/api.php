<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProfileController;

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/contact', [ContactController::class, 'store']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']); // Add this route for single product

// Product create public (without auth)
Route::post('/products', [ProductController::class, 'store']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    
    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::delete('/profile/image', [ProfileController::class, 'deleteImage']);
    
    // Offer Management
    Route::post('/offers', [OfferController::class, 'store']);
    Route::put('/offers/{id}', [OfferController::class, 'update']);
    Route::delete('/offers/{id}', [OfferController::class, 'destroy']);
    Route::get('/offers', [OfferController::class, 'index']);
    Route::get('/offers/{id}', [OfferController::class, 'show']);
    
    // Order Routes
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
});