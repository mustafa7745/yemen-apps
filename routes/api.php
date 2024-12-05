<?php

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



Route::prefix('v1')->group(function () {
    Route::apiResource('/', UserController::class);
    Route::post('/upload-image', [UserController::class, 'uploadImage']);
});
Route::prefix('v1/storeManager')->group(function () {
    Route::apiResource('/', StoreManagerController::class);

    Route::post('/login', [StoreManagerController::class, 'login']);


    Route::post('/updateProductImage', [StoreManagerController::class, 'updateProductImage']);
    Route::post('/addProductImage', [StoreManagerController::class, 'addProductImage']);
    Route::post('/deleteProductImage', [StoreManagerController::class, 'deleteProductImage']);
    // 
    Route::post('/updateProductName', [StoreManagerController::class, 'updateProductName']);
    Route::post('/updateProductDescription', [StoreManagerController::class, 'updateProductDescription']);
    Route::post('/updateProductOptionName', [StoreManagerController::class, 'updateProductOptionName']);
    Route::post('/updateProductOptionPrice', [StoreManagerController::class, 'updateProductOptionPrice']);
    // 
    Route::post('/addProductOption', [StoreManagerController::class, 'addProductOption']);
    Route::post('/deleteProductOptions', [StoreManagerController::class, 'deleteProductOptions']);
    //
    Route::post('/getMyProducts', [StoreManagerController::class, 'getMyProducts']);
    Route::post('/getMyCategories', [StoreManagerController::class, 'getMyCategories']);

    Route::post('/addMyProduct', [StoreManagerController::class, 'addMyProduct']);
    Route::post('/addMyCategory', [StoreManagerController::class, 'addMyCategory']);
    // 
    Route::post('/getProducts', [StoreManagerController::class, 'getProducts']);
    Route::post('/getCategories', [StoreManagerController::class, 'getCategories']);
    // 
    Route::post('/readOptions', [StoreManagerController::class, 'readOptions']);
    Route::post('/readStoreCategories', [StoreManagerController::class, 'readStoreCategories']);

    Route::post('/refreshToken', [StoreManagerController::class, 'refreshToken']);



});