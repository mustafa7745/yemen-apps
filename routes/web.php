<?php

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/r',function(){
return new JsonResponse([
'data'=>12345]);
});

Route::get('/read', function () {
    return view('welcome');
});

Route::get('/privacy', function () {
    return view('privacy');
});