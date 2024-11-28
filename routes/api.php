<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductRatingController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StoreRatingController;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Support\Facades\Route;


Route::group([ 'prefix' => 'auth'], function ($router) {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class,'login']);
});
Route::middleware(['auth:api'])->group(function () {
    Route::post('logout', [AuthController::class,'logout']);
    Route::post('refresh', [AuthController::class,'refresh']);
    Route::post('me', [AuthController::class,'me']);
});
//Routes Categories
Route::group(['prefix'=>'categories'],function (){
    Route::post('create',[CategoryController::class,'create']);
    Route::post('update',[CategoryController::class,'update']);
    Route::delete('delete/{id}',[CategoryController::class,'delete']);
    Route::get('getCategories',[CategoryController::class,'getCategories']);


});
Route::group(['prefix'=>'store'],function (){
    Route::post('create',[StoreController::class,'create'])->middleware([JwtMiddleware::class]);
    Route::post('update',[StoreController::class,'update'])->middleware([JwtMiddleware::class]);
    Route::delete('delete/{id}',[StoreController::class,'delete'])->middleware([JwtMiddleware::class]);
    Route::get('getStoreByCategory/{category_id}',[StoreController::class,'getStoreByCategory']);
    Route::post('search',[StoreController::class,'search']);
    Route::get('getStore/{id}',[StoreController::class,'getStore']);

});
Route::group(['prefix'=>'product'],function (){
    Route::post('create',[ProductController::class,'create'])->middleware([JwtMiddleware::class]);
    Route::post('update',[ProductController::class,'update'])->middleware([JwtMiddleware::class]);
    Route::delete('delete/{id}',[ProductController::class,'delete'])->middleware([JwtMiddleware::class]);
    Route::get('getProductsByStore/{store_id}',[ProductController::class,'getProductsByStore']);
    Route::get('getProduct/{id}',[ProductController::class,'getProduct']);
    Route::post('search',[ProductController::class,'search']);

});
Route::group(['prefix'=>'favorite'],function (){
    Route::post('addToFavorite',[FavoriteController::class,'addToFavorites'])->middleware([JwtMiddleware::class]);
    Route::delete('removeFromFavorite/{id}',[FavoriteController::class,'removeFromFavorites'])->middleware([JwtMiddleware::class]);
    Route::get('getFavorites',[FavoriteController::class,'getFavorites'])->middleware([JwtMiddleware::class]);
});

Route::group(['prefix'=>'storeRating'],function (){
    Route::post('addRating',[StoreRatingController::class,'addRating'])->middleware([JwtMiddleware::class]);
    Route::post('updateRating',[StoreRatingController::class,'updateRating'])->middleware([JwtMiddleware::class]);
    Route::delete('deleteRating/{rating_id}',[StoreRatingController::class,'deleteRating'])->middleware([JwtMiddleware::class]);
    Route::get('getRatings/{store_id}',[StoreRatingController::class,'getRatings']);
    Route::get('getRatingValue/{store_id}',[StoreRatingController::class,'getRatingValue'])->middleware([JwtMiddleware::class]);
});

Route::group(['prefix'=>'productRating'],function (){
    Route::post('addRating',[ProductRatingController::class,'addRating'])->middleware([JwtMiddleware::class]);
    Route::post('updateRating',[ProductRatingController::class,'updateRating'])->middleware([JwtMiddleware::class]);
    Route::delete('deleteRating/{rating_id}',[ProductRatingController::class,'deleteRating'])->middleware([JwtMiddleware::class]);
    Route::get('getRatings/{product_id}',[ProductRatingController::class,'getRatings']);
    Route::get('getRatingValue/{product_id}',[ProductRatingController::class,'getRatingValue'])->middleware([JwtMiddleware::class]);
});
