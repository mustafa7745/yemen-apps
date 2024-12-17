<?php

use App\Http\Controllers\Api\StoreManager\StoreManagerController;
use App\Http\Controllers\Api\StoreManager\StoreManagerController2;
use App\Http\Controllers\Api\StoreManager\StoreManagerControllerGet;
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

    Route::post('/readMain', [StoreManagerControllerGet::class, 'readMain']);
    Route::post('/getStores', [StoreManagerControllerGet::class, 'getStores']);
    Route::post('/getCategories', [StoreManagerControllerGet::class, 'getCategories']);
    Route::post('/getStoreCategories', [StoreManagerControllerGet::class, 'getStoreCategories']);
    Route::post('/getSections', [StoreManagerControllerGet::class, 'getSections']);
    Route::post('/getSecionsStoreCategories', [StoreManagerControllerGet::class, 'getSecionsStoreCategories']);
    Route::post('/getStoreNestedSections', [StoreManagerControllerGet::class, 'getStoreNestedSections']);
    Route::post('/getNestedSections', [StoreManagerControllerGet::class, 'getNestedSections']);
    Route::post('/readOptions', [StoreManagerControllerGet::class, 'readOptions']);
    Route::post('/getProducts', [StoreManagerControllerGet::class, 'getProducts']);

    Route::post('/addCategory', [StoreManagerController2::class, 'addCategory']);
    Route::post('/addSection', [StoreManagerController2::class, 'addSection']);
    Route::post('/addNestedSection', [StoreManagerController2::class, 'addNestedSection']);
    Route::post('/addStoreSection', [StoreManagerController2::class, 'addStoreSection']);
    Route::post('/addStoreNestedSection', [StoreManagerController2::class, 'addStoreNestedSection']);
    Route::post('/addProduct', [StoreManagerController2::class, 'addProduct']);
    Route::post('/addStoreCategory', [StoreManagerController2::class, 'addStoreCategory']);
    Route::post('/addProductImage', [StoreManagerController::class, 'addProductImage']);
    Route::post('/addProductOption', [StoreManagerController::class, 'addProductOption']);
    Route::post('/addStore', [StoreManagerController::class, 'addStore']);

    Route::post('/updateStoreConfig', [StoreManagerController2::class, 'updateStoreConfig']);
    Route::post('/updateProductName', [StoreManagerController::class, 'updateProductName']);
    Route::post('/updateProductDescription', [StoreManagerController::class, 'updateProductDescription']);
    Route::post('/updateProductOptionName', [StoreManagerController::class, 'updateProductOptionName']);
    Route::post('/updateProductOptionPrice', [StoreManagerController::class, 'updateProductOptionPrice']);
    Route::post('/updateProductImage', [StoreManagerController::class, 'updateProductImage']);

    Route::post('/deleteProductImage', [StoreManagerController::class, 'deleteProductImage']);
    Route::post('/deleteProductOptions', [StoreManagerController::class, 'deleteProductOptions']);


    // Route::apiResource('/', StoreManagerController::class);










    // 
    // 


    //
    // Route::post('/getMyProducts', [StoreManagerController::class, 'getMyProducts']);
    // Route::post('/getMyCategories', [StoreManagerController::class, 'getMyCategories']);

    // Route::post('/addMyProduct', [StoreManagerController::class, 'addMyProduct']);
    // Route::post('/addMyCategory', [StoreManagerController::class, 'addMyCategory']);
    // 

    // Route::post('/getCategories1', [StoreManagerController::class, 'getCategories1']);
    // 

    // Route::post('/readStoreCategories', [StoreManagerController::class, 'readStoreCategories']);


    Route::post('/login', [StoreManagerControllerGet::class, 'login']);
    Route::post('/refreshToken', [StoreManagerControllerGet::class, 'refreshToken']);
});