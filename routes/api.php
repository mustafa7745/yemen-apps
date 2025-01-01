<?php

use App\Http\Controllers\Api\StoreManager\StoreManagerControllerAdd;
use App\Http\Controllers\Api\StoreManager\StoreManagerControllerDelete;
use App\Http\Controllers\Api\StoreManager\StoreManagerControllerGet;
use App\Http\Controllers\Api\StoreManager\StoreManagerControllerUpdate;
use App\Http\Controllers\Api\Stores\StoresControllerAdd;
use App\Http\Controllers\Api\Stores\StoresControllerGet;
use App\Http\Controllers\Api\Users\UserControllerGet;
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
    Route::apiResource('/', StoresControllerGet::class);
    Route::post('/getHome', [StoresControllerGet::class, 'getHome']);
    // 
    Route::post('/login', [StoresControllerGet::class, 'login']);
    Route::post('/refreshToken', [StoresControllerGet::class, 'refreshToken']);
    Route::post('/getProducts', [StoresControllerGet::class, 'getProducts']);
    Route::post('/getStoreInfo', [StoresControllerGet::class, 'getStoreInfo']);
    Route::post('/getLocations', [StoresControllerGet::class, 'getLocations']);
    Route::post('/addLocation', [StoresControllerAdd::class, 'addLocation']);
});


Route::prefix('v1/storeManager')->group(function () {

    Route::post('/getMain', [StoreManagerControllerGet::class, 'getMain']);
    Route::post('/getStores', [StoreManagerControllerGet::class, 'getStores']);
    Route::post('/getCategories', [StoreManagerControllerGet::class, 'getCategories']);
    Route::post('/getStoreCategories', [StoreManagerControllerGet::class, 'getStoreCategories']);
    Route::post('/getSections', [StoreManagerControllerGet::class, 'getSections']);
    Route::post('/getSecionsStoreCategories', [StoreManagerControllerGet::class, 'getSecionsStoreCategories']);
    Route::post('/getStoreNestedSections', [StoreManagerControllerGet::class, 'getStoreNestedSections']);
    Route::post('/getNestedSections', [StoreManagerControllerGet::class, 'getNestedSections']);
    Route::post('/getOptions', [StoreManagerControllerGet::class, 'getOptions']);
    Route::post('/getProducts', [StoreManagerControllerGet::class, 'getProducts']);
    Route::post('/getStoreInfo', [StoreManagerControllerGet::class, 'getStoreInfo']);


    Route::post('/addCategory', [StoreManagerControllerAdd::class, 'addCategory']);
    Route::post('/addSection', [StoreManagerControllerAdd::class, 'addSection']);
    Route::post('/addNestedSection', [StoreManagerControllerAdd::class, 'addNestedSection']);
    Route::post('/addStoreSection', [StoreManagerControllerAdd::class, 'addStoreSection']);
    Route::post('/addStoreNestedSection', [StoreManagerControllerAdd::class, 'addStoreNestedSection']);
    Route::post('/addProduct', [StoreManagerControllerAdd::class, 'addProduct']);
    Route::post('/addStoreCategory', [StoreManagerControllerAdd::class, 'addStoreCategory']);
    Route::post('/addProductImage', [StoreManagerControllerAdd::class, 'addProductImage']);
    Route::post('/addProductOption', [StoreManagerControllerAdd::class, 'addProductOption']);
    Route::post('/addStore', [StoreManagerControllerAdd::class, 'addStore']);

    Route::post('/updateStoreConfig', [StoreManagerControllerUpdate::class, 'updateStoreConfig']);
    Route::post('/updateProductName', [StoreManagerControllerUpdate::class, 'updateProductName']);
    Route::post('/updateProductDescription', [StoreManagerControllerUpdate::class, 'updateProductDescription']);
    Route::post('/updateProductOptionName', [StoreManagerControllerUpdate::class, 'updateProductOptionName']);
    Route::post('/updateProductOptionPrice', [StoreManagerControllerUpdate::class, 'updateProductOptionPrice']);
    Route::post('/updateProductImage', [StoreManagerControllerUpdate::class, 'updateProductImage']);
    Route::post('/updateStore', [StoreManagerControllerUpdate::class, 'updateStore']);
    Route::post('/updateStoreLocation', [StoreManagerControllerUpdate::class, 'updateStoreLocation']);



    Route::post('/deleteProductImage', [StoreManagerControllerDelete::class, 'deleteProductImage']);
    Route::post('/deleteProductOptions', [StoreManagerControllerDelete::class, 'deleteProductOptions']);
    Route::post('/deleteProducts', [StoreManagerControllerDelete::class, 'deleteProducts']);
    Route::post('/deleteStores', [StoreManagerControllerDelete::class, 'deleteStores']);
    Route::post('/deleteProducts', [StoreManagerControllerDelete::class, 'deleteProducts']);
    Route::post('/deleteStoreCategories', [StoreManagerControllerDelete::class, 'deleteStoreCategories']);
    Route::post('/deleteCategories', [StoreManagerControllerDelete::class, 'deleteCategories']);
    Route::post('/deleteStoreSections', [StoreManagerControllerDelete::class, 'deleteStoreSections']);
    Route::post('/deleteSections', [StoreManagerControllerDelete::class, 'deleteSections']);
    Route::post('/deleteStoreNestedSections', [StoreManagerControllerDelete::class, 'deleteStoreNestedSections']);
    Route::post('/deleteNestedSections', [StoreManagerControllerDelete::class, 'deleteNestedSections']);



    Route::post('/login', [StoreManagerControllerGet::class, 'login']);
    Route::post('/refreshToken', [StoreManagerControllerGet::class, 'refreshToken']);
});

Route::prefix('v1/u')->group(function () {
    Route::post('/getApp', [UserControllerGet::class, 'getApp']);
    Route::post('/getStores', [UserControllerGet::class, 'getStores']);
    Route::post('/getHome', [UserControllerGet::class, 'getHome']);
    Route::post('/getProducts', [UserControllerGet::class, 'getProducts']);


    Route::post('/login', [UserControllerGet::class, 'login']);
    // Route::post('/refreshToken', [StoresControllerGet::class, 'refreshToken']);
    // Route::post('/getProducts', [StoresControllerGet::class, 'getProducts']);
    // Route::post('/getStoreInfo', [StoresControllerGet::class, 'getStoreInfo']);
    // Route::post('/getLocations', [StoresControllerGet::class, 'getLocations']);
    // Route::post('/addLocation', [StoresControllerAdd::class, 'addLocation']);
});