<?php

use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\ProductManagementController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\RateController;
use App\Http\Controllers\API\ResellerPriceController;
use App\Http\Controllers\API\ResellerProductController;
use App\Http\Controllers\API\SettingsController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\UserManagementController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// RESPONSE USER
Route::post('register', [UserController::class, 'register']);
Route::post('register-local', [UserController::class, 'registerLocal']);
Route::post('login', [UserController::class, 'login']);
Route::post('forgot-password', [UserController::class, 'forgotPassword']);
Route::post('reset-password', [UserController::class, 'resetPassword']);
Route::post('updated/{id}', [UserController::class, 'updated']);
Route::post('delete/{id}', [UserController::class, 'delete']);
Route::get('user', [UserController::class, 'user']);
Route::get('captcha', [UserController::class, 'generateCaptcha']);

// REPONSE CATEGORY
Route::get('category', [CategoryController::class, 'category']);

// Response Profile
Route::post('profile', [ProfileController::class, 'profile']);
Route::post('profile/{id}', [ProfileController::class, 'editProfile']);

// Product
Route::get('product', [ProductController::class, 'product']);
Route::get('productFavorite', [ProductController::class, 'productFavorite']);

// Rating
Route::post('rate', [RateController::class, 'storeRate']);

// Cart
Route::post('cart', [CartController::class, 'storeCart']);
Route::post('cart/{id}', [CartController::class, 'updateCart']);
Route::delete('cart/{id}', [CartController::class, 'destroyCart']);
Route::get('cart', [CartController::class, 'cart']);

// Transaction
Route::post('checkout', [TransactionController::class, 'checkout']);
Route::post('checkout/{id}', [TransactionController::class, 'updateCheckout']);
Route::delete('checkout/{id}', [TransactionController::class, 'deleteCheckout']);
Route::get('transaction', [TransactionController::class, 'transaction']);
Route::get('city', [TransactionController::class, 'searchCityByName']);
Route::post('cost', [TransactionController::class, 'cekOngkir']);

// Reseller Product Routes (authenticated)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('reseller/product', [ResellerProductController::class, 'index']);
    Route::post('reseller/product', [ResellerProductController::class, 'store']);
    Route::get('reseller/product/{id}', [ResellerProductController::class, 'show']);
    Route::put('reseller/product/{id}', [ResellerProductController::class, 'update']);
    Route::delete('reseller/product/{id}', [ResellerProductController::class, 'destroy']);
    Route::get('reseller/categories', [ResellerProductController::class, 'categories']);
});

// Settings Routes (public - no auth needed)
Route::get('settings', [SettingsController::class, 'index']);
Route::put('settings', [SettingsController::class, 'update']);

// User Management Routes (owner, admin, developer only)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('users', [UserManagementController::class, 'index']);
    Route::get('users/{id}', [UserManagementController::class, 'show']);
    Route::post('users', [UserManagementController::class, 'store']);
    Route::put('users/{id}', [UserManagementController::class, 'update']);
    Route::delete('users/{id}', [UserManagementController::class, 'destroy']);
    Route::post('users/{id}/reset-password', [UserManagementController::class, 'resetPassword']);
});

// Product Management Routes (owner, admin, developer only)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('management/products', [ProductManagementController::class, 'index']);
    Route::post('management/products', [ProductManagementController::class, 'store']);
    Route::get('management/products/{id}', [ProductManagementController::class, 'show']);
    Route::put('management/products/{id}', [ProductManagementController::class, 'update']);
    Route::delete('management/products/{id}', [ProductManagementController::class, 'destroy']);
    Route::get('management/categories', [ProductManagementController::class, 'categories']);
});

// Reseller Price Routes (reseller only)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('reseller/prices', [ResellerPriceController::class, 'index']);
    Route::post('reseller/prices/{productId}', [ResellerPriceController::class, 'setPrice']);
    Route::get('reseller/prices/{productId}', [ResellerPriceController::class, 'getPrice']);
    Route::delete('reseller/prices/{productId}', [ResellerPriceController::class, 'deletePrice']);
});

