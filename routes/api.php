<?php

use App\Http\Controllers\Api\StoreManager\StoreManagerController;
use App\Http\Controllers\Api\StoreManager\StoreManagerController2;
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
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/getProducts', [UserController::class, 'getProducts']);
    // Route::post('/readMain', [UserController::class, 'readMain']);

});
Route::prefix('v1/storeManager')->group(function () {
    Route::post('/getStores', [StoreManagerController2::class, 'getStores']);
    //
    Route::post('/getCategories', [StoreManagerController2::class, 'getCategories']);
    Route::post('/addCategory', [StoreManagerController2::class, 'addCategory']);
    Route::post('/addSection', [StoreManagerController2::class, 'addSection']);

    Route::post('/addNestedSection', [StoreManagerController2::class, 'addNestedSection']);

    Route::post('/getStoreCategories', [StoreManagerController2::class, 'getStoreCategories']);
    Route::post('/addStoreCategory', [StoreManagerController2::class, 'addStoreCategory']);
    // 
    Route::post('/getSections', [StoreManagerController2::class, 'getSections']);
    Route::post('/getSecionsStoreCategories', [StoreManagerController2::class, 'getSecionsStoreCategories']);
    Route::post('/addStoreSection', [StoreManagerController2::class, 'addStoreSection']);
    //
    Route::post('/addStoreNestedSection', [StoreManagerController2::class, 'addStoreNestedSection']);
    Route::post('/getStoreNestedSections', [StoreManagerController2::class, 'getStoreNestedSections']);
    Route::post('/getNestedSections', [StoreManagerController2::class, 'getNestedSections']);

    Route::post('/updateStoreConfig', [StoreManagerController2::class, 'updateStoreConfig']);
    Route::post('/addProduct', [StoreManagerController2::class, 'addProduct']);


















    // Route::apiResource('/', StoreManagerController::class);
    Route::post('/readMain', [StoreManagerController::class, 'readMain']);
    Route::post('/login', [StoreManagerController::class, 'login']);


    Route::post('/updateProductImage', [StoreManagerController::class, 'updateProductImage']);
    Route::post('/addProductImage', [StoreManagerController::class, 'addProductImage']);
    Route::post('/deleteProductImage', [StoreManagerController::class, 'deleteProductImage']);

    Route::post('/addStore', [StoreManagerController::class, 'addStore']);

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
    Route::post('/getCategories1', [StoreManagerController::class, 'getCategories1']);
    // 
    Route::post('/readOptions', [StoreManagerController::class, 'readOptions']);
    Route::post('/readStoreCategories', [StoreManagerController::class, 'readStoreCategories']);

    Route::post('/refreshToken', [StoreManagerController::class, 'refreshToken']);



});