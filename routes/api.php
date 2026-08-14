<?php

use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\AuthenticationController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::controller(AuthenticationController::class)->prefix('auth')->name('auth.')->group(function() {
    Route::post('/register', 'register')->name('register');
    Route::post('/login', 'login')->name('login');

    Route::middleware('auth:sanctum')->group(function() {
        Route::get('/profile', 'profile')->name('profile');
        Route::put('/profile', 'updateProfile')->name('profile.update');
        Route::put('/password', 'updatePassword')->name('password.update');
    });
});

Route::controller(ProductController::class)->prefix('products')->name('products.')->group(function() {
    Route::get('/', 'index')->name('index');
    Route::get('/{product:slug}', 'show')->name('show');

    Route::middleware(['auth:sanctum', 'admin'])->group(function() {
        Route::post('/', 'store')->name('store');
        Route::put('/{product:slug}', 'update')->name('update');
        Route::delete('/{product:slug}', 'destroy')->name('destroy');
    });
});

Route::controller(CategoryController::class)->prefix('categories')->name('categories.')->group(function() {
    Route::get('/', 'index')->name('index');
    Route::get('/{category:slug}', 'show')->name('show');

    Route::middleware(['auth:sanctum', 'admin'])->group(function() {
        Route::post('/', 'store')->name('store');
        Route::put('/{category:slug}', 'update')->name('update');
        Route::delete('/{category:slug}', 'destroy')->name('destroy');
    });
});

Route::controller(AddressController::class)->prefix('addresses')->name('addresses.')->middleware('auth:sanctum')->group(function() {
    Route::get('/me', 'mine')->name('mine');
    Route::post('/', 'store')->name('store');
    Route::get('/{address}', 'show')->name('show');
    Route::put('/{address}', 'update')->name('update');
    Route::delete('/{address}', 'destroy')->name('destroy');

    Route::middleware('admin')->group(function() {
        Route::get('/', 'index')->name('index');
    });
});

Route::controller(UserController::class)->prefix('users')->name('users.')->middleware(['auth:sanctum', 'admin'])->group(function() {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/{user}', 'show')->name('show');
    Route::put('/{user}', 'update')->name('update');
    Route::delete('/{user}', 'destroy')->name('destroy');
});

Route::controller(RoleController::class)->prefix('roles')->name('roles.')->middleware(['auth:sanctum', 'admin'])->group(function() {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/{role}', 'show')->name('show');
    Route::put('/{role}', 'update')->name('update');
    Route::delete('/{role}', 'destroy')->name('destroy');
});

Route::controller(TransactionController::class)->prefix('transactions')->name('transactions.')->middleware('auth:sanctum')->group(function() {
    Route::post('/', 'store')->name('store');
    Route::get('/mine', 'mine')->name('mine');
    Route::get('/{transaction}', 'show')->name('show');

    Route::middleware('admin')->group(function() {
        Route::get('/', 'index')->name('index');
    });
});

Route::controller(BannerController::class)->prefix('banners')->name('banners.')->group(function() {
    Route::get('/', 'index')->name('index');
    Route::get('/{banner}', 'show')->name('show');

    Route::middleware(['auth:sanctum', 'admin'])->group(function() {
        Route::post('/', 'store')->name('store');
        Route::put('/{banner}', 'update')->name('update');
        Route::delete('/{banner}', 'destroy')->name('destroy');
    });
});
