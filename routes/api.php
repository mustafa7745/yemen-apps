<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\StoreManager\StoreManagerController;
use App\Http\Controllers\Api\Users\UserController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::get('/r2', function () {
    return new JsonResponse([
        'data' => 12345
    ]);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



Route::prefix('v1')->group(function () {
    Route::apiResource('/', UserController::class);
    Route::post('/upload-image', [UserController::class, 'uploadImage']);
    // Route::apiResource('users', UserController::class);
    // Route::apiResource('posts', PostController::class);
    // Route::apiResource('comments', CommentController::class);
});
Route::prefix('v1/storeManager')->group(function () {
    Route::apiResource('/', StoreManagerController::class);
    Route::post('/updateProductImage', [StoreManagerController::class, 'updateProductImage']);
    Route::post('/addProductImage', [StoreManagerController::class, 'addProductImage']);
    Route::post('/deleteProductImage', [StoreManagerController::class, 'deleteProductImage']);
    // 
    Route::post('/updateProductName', [StoreManagerController::class, 'updateProductName']);
    // Route::apiResource('users', UserController::class);
    // Route::apiResource('posts', PostController::class);
    // Route::apiResource('comments', CommentController::class);
});