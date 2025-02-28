<?php

use App\Http\Controllers\Api\Delivery\DeliveryControllerGet;
use App\Http\Controllers\Api\StoreManager\StoreManagerControllerAdd;
use App\Http\Controllers\Api\StoreManager\StoreManagerControllerDelete;
use App\Http\Controllers\Api\StoreManager\StoreManagerControllerGet;
use App\Http\Controllers\Api\StoreManager\StoreManagerControllerUpdate;
use App\Http\Controllers\Api\Stores\StoresControllerAdd;
use App\Http\Controllers\Api\Stores\StoresControllerGet;
use App\Http\Controllers\Api\Users\UserControllerAdd;
use App\Http\Controllers\Api\Users\UserControllerGet;
use App\Http\Controllers\Api\Users\UserControllerUpdate;
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
    Route::post('/', [StoresControllerGet::class, 'getMain']);

    // 
    Route::post('/login', [StoresControllerGet::class, 'login']);
    Route::post('/refreshToken', [StoresControllerGet::class, 'refreshToken']);
    Route::post('/getProducts', [StoresControllerGet::class, 'getProducts']);
    Route::post('/getStoreInfo', [StoresControllerGet::class, 'getStoreInfo']);
    Route::post('/getLocations', [StoresControllerGet::class, 'getLocations']);
    Route::post('/getPaymentTypes', [StoresControllerGet::class, 'getPaymentTypes']);
    Route::post('/getCustomPrices', [StoresControllerGet::class, 'getCustomPrices']);

    Route::post('/whatsapp_webhook', [StoresControllerGet::class, 'whatsapp_webhook']);



    Route::post('/addLocation', [StoresControllerAdd::class, 'addLocation']);
    Route::post('/confirmOrder', [StoresControllerAdd::class, 'confirmOrder']);

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
    Route::post('/getOrders', [StoreManagerControllerGet::class, 'getOrders']);
    Route::post('/getOrderProducts', [StoreManagerControllerGet::class, 'getOrderProducts']);
    Route::post('/getDeliveryMen', [StoreManagerControllerGet::class, 'getDeliveryMen']);
    Route::post('/getProductViews', [StoreManagerControllerGet::class, 'getProductViews']);
    Route::post('/getUserProfile', [StoreManagerControllerGet::class, 'getUserProfile']);
    Route::post('/getCurrencies', [StoreManagerControllerGet::class, 'getCurrencies']);
    Route::post('/getCustomPrices', [StoreManagerControllerGet::class, 'getCustomPrices']);
    Route::post('/getOrderSituations', [StoreManagerControllerGet::class, 'getOrderSituations']);
    Route::post('/getInAppProducts', [StoreManagerControllerGet::class, 'getInAppProducts']);
    Route::post('/getStoreTime', [StoreManagerControllerGet::class, 'getStoreTime']);
    Route::post('/getLoginConfiguration', [StoreManagerControllerGet::class, 'getLoginConfiguration']);









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
    Route::post('/addDeliveryManToStore', [StoreManagerControllerAdd::class, 'addDeliveryManToStore']);
    Route::post('/addNotification', [StoreManagerControllerAdd::class, 'addNotification']);
    Route::post('/addAds', [StoreManagerControllerAdd::class, 'addAds']);
    Route::post('/addCustomPrice', [StoreManagerControllerAdd::class, 'addCustomPrice']);






    Route::post('/updateStoreConfig', [StoreManagerControllerUpdate::class, 'updateStoreConfig']);
    Route::post('/updateProductName', [StoreManagerControllerUpdate::class, 'updateProductName']);
    Route::post('/updateProductDescription', [StoreManagerControllerUpdate::class, 'updateProductDescription']);
    Route::post('/updateProductOptionName', [StoreManagerControllerUpdate::class, 'updateProductOptionName']);
    Route::post('/updateProductOptionPrice', [StoreManagerControllerUpdate::class, 'updateProductOptionPrice']);
    Route::post('/updateProductImage', [StoreManagerControllerUpdate::class, 'updateProductImage']);
    Route::post('/updateStore', [StoreManagerControllerUpdate::class, 'updateStore']);
    Route::post('/updateStoreLocation', [StoreManagerControllerUpdate::class, 'updateStoreLocation']);
    Route::post('/updateOrderProductQuantity', [StoreManagerControllerUpdate::class, 'updateOrderProductQuantity']);
    Route::post('/updateOrderDeliveryMan', [StoreManagerControllerUpdate::class, 'updateOrderDeliveryMan']);
    Route::post('/updateProductView', [StoreManagerControllerUpdate::class, 'updateProductView']);
    Route::post('/updateStoreProductOrder', [StoreManagerControllerUpdate::class, 'updateStoreProductOrder']);
    Route::post('/updateProductOrder', [StoreManagerControllerUpdate::class, 'updateProductOrder']);
    Route::post('/updateCurrency', [StoreManagerControllerUpdate::class, 'updateCurrency']);
    Route::post('/updateCustomPrice', [StoreManagerControllerUpdate::class, 'updateCustomPrice']);
    Route::post('/updateAds', [StoreManagerControllerUpdate::class, 'updateAds']);
    Route::post('/updatePoints', [StoreManagerControllerUpdate::class, 'updatePoints']);
    Route::post('/updateStoreDeliveryPrice', [StoreManagerControllerUpdate::class, 'updateStoreDeliveryPrice']);
    Route::post('/updateStoreServiceAccount', [StoreManagerControllerUpdate::class, 'updateStoreServiceAccount']);
    Route::post('/updateStoreTime', [StoreManagerControllerUpdate::class, 'updateStoreTime']);





    Route::post('/cancelOrder', [StoreManagerControllerUpdate::class, 'cancelOrder']);





    Route::post('/logout', [StoreManagerControllerUpdate::class, 'logout']);


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
    Route::post('/deleteOrderProducts', [StoreManagerControllerDelete::class, 'deleteOrderProducts']);
    Route::post('/deleteAds', [StoreManagerControllerDelete::class, 'deleteAds']);





    Route::post('/login', [StoreManagerControllerGet::class, 'login']);
    Route::post('/refreshToken', [StoreManagerControllerGet::class, 'refreshToken']);
});

Route::prefix('v1/u')->group(function () {

    Route::post('/getApp', [UserControllerGet::class, 'getApp']);
    Route::post('/getStores', [UserControllerGet::class, 'getStores']);
    Route::post('/getHome', [UserControllerGet::class, 'getHome']);
    Route::post('/getProducts', [UserControllerGet::class, 'getProducts']);
    Route::post('/getLocations', [UserControllerGet::class, 'getLocations']);
    Route::post('/getOrders', [UserControllerGet::class, 'getOrders']);
    Route::post('/getOrderProducts', [UserControllerGet::class, 'getOrderProducts']);
    Route::post('/getPaymentTypes', [UserControllerGet::class, 'getPaymentTypes']);
    Route::post('/getUserProfile', [UserControllerGet::class, 'getUserProfile']);
    Route::post('/getCustomPrices', [UserControllerGet::class, 'getCustomPrices']);

    // Route::post('/getCurrencies', [UserControllerGet::class, 'getCurrencies']);

    Route::post('/search', [UserControllerGet::class, 'search']);



    Route::post('/logout', [UserControllerUpdate::class, 'logout']);
    Route::post('/updateProfile', [UserControllerUpdate::class, 'updateProfile']);






    Route::post('/addLocation', [UserControllerAdd::class, 'addLocation']);
    Route::post('/addPaidCode', [UserControllerAdd::class, 'addPaidCode']);

    Route::post('/confirmOrder', [UserControllerAdd::class, 'confirmOrder']);





    Route::post('/login', [UserControllerGet::class, 'login']);
    Route::post('/refreshToken', [UserControllerGet::class, 'refreshToken']);
    // Route::post('/getProducts', [StoresControllerGet::class, 'getProducts']);
    // Route::post('/getStoreInfo', [StoresControllerGet::class, 'getStoreInfo']);
    // Route::post('/getLocations', [StoresControllerGet::class, 'getLocations']);
    // Route::post('/addLocation', [StoresControllerAdd::class, 'addLocation']);
});

Route::prefix('v1/delivery')->group(function () {

    Route::post('/getStores', [DeliveryControllerGet::class, 'getStores']);


    Route::post('/login', [DeliveryControllerGet::class, 'login']);
    Route::post('/refreshToken', [DeliveryControllerGet::class, 'refreshToken']);
});