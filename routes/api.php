<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\LocationsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductOrderController;
use App\Http\Controllers\ProductRatingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\StoreRatingController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\WalletController;
use App\Http\Middleware\JwtMiddleware;
use Illuminate\Support\Facades\Route;


Route::group([ 'prefix' => 'auth'], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class,'login']);
});
Route::middleware(['auth:api'])->group(function () {
    Route::post('logout', [AuthController::class,'logout']);
    Route::post('refresh', [AuthController::class,'refresh']);
    Route::post('me', [AuthController::class,'me']);
});
Route::group(['middleware' => [JwtMiddleware::class]], function() {

Route::group(['prefix'=>'categories'],function (){
    Route::post('create',[CategoryController::class,'create'])->name('category.create')->middleware('can:category.create');
    Route::post('update',[CategoryController::class,'update'])->name('category.update')->middleware('can:category.update');
    Route::delete('delete/{id}',[CategoryController::class,'delete'])->name('category.delete')->middleware('can:category.delete');
    Route::get('getCategories',[CategoryController::class,'getCategories'])->name('category.get')->middleware('can:category.get');
    Route::post('search',[CategoryController::class,'searchByCategory'])->name('category.search');
    Route::post('saveKeyword',[CategoryController::class,'keywordSave'])->name('category.keyword');

});
Route::group(['prefix'=>'store'],function (){
    Route::post('create',[StoreController::class,'create'])->name('store.create')->middleware('can:store.create');
    Route::post('update',[StoreController::class,'update'])->name('store.update')->middleware('can:store.update');
    Route::delete('delete/{id}',[StoreController::class,'delete'])->name('store.delete')->middleware('can:store.delete');
    Route::get('getStoreByCategory/{category_id}',[StoreController::class,'getStoreByCategory'])->name('store.getByCategory')->middleware('can:store.getByCategory');
    Route::post('search',[StoreController::class,'search'])->name('store.search')->middleware('can:store.search');
    Route::post('getStoresByIds',[StoreController::class,'getStoresByIds'])->name('store.getByIds')->middleware('can:store.getByIds');
    Route::get('getStore/{id}',[StoreController::class,'getStore'])->name('store.get')->middleware('can:store.get');
});
Route::group(['prefix'=>'product'],function (){
    Route::post('create',[ProductController::class,'create'])->name('product.create')->middleware('can:product.create');
    Route::post('update',[ProductController::class,'update'])->name('product.update')->middleware('can:product.update');
    Route::delete('delete/{id}',[ProductController::class,'delete'])->name('product.delete')->middleware('can:product.delete');
    Route::get('getProductsByStore/{store_id}',[ProductController::class,'getProductsByStore'])->name('product.getByStore')->middleware('can:product.getByStore');
    Route::get('getProduct/{id}',[ProductController::class,'getProduct'])->name('product.get')->middleware('can:product.get');
    Route::post('search',[ProductController::class,'search'])->name('product.search')->middleware('can:product.search');
    Route::post('getProductsByIds',[ProductController::class,'getProductsByIds'])->name('product.getByIds');

});
Route::group(['prefix'=>'favorite'],function (){
    Route::post('addToFavorite',[FavoriteController::class,'addToFavorites'])->name('favorite.add')->middleware('can:favorite.add');
    Route::delete('removeFromFavorite/{id}',[FavoriteController::class,'removeFromFavorites'])->name('favorite.remove')->middleware('can:favorite.remove');
    Route::get('getFavorites',[FavoriteController::class,'getFavorites'])->name('favorite.get')->middleware('can:favorite.get');
});

Route::group(['prefix'=>'storeRating'],function (){
    Route::post('addRating',[StoreRatingController::class,'addRating'])->name('storeRating.add')->middleware('can:storeRating.add');
    Route::post('updateRating',[StoreRatingController::class,'updateRating'])->name('storeRating.update')->middleware('can:storeRating.update');
    Route::delete('deleteRating/{rating_id}',[StoreRatingController::class,'deleteRating'])->name('storeRating.delete')->middleware('can:storeRating.delete');
    Route::get('getRatings/{store_id}',[StoreRatingController::class,'getRatings'])->name('storeRating.get')->middleware('can:storeRating.get');
    Route::get('getMyRating',[StoreRatingController::class,'getRatingUser'])->name('storeRating.getMyUser')->middleware('can:storeRating.getMyUser');
    Route::get('getRatingValue/{store_id}',[StoreRatingController::class,'getRatingValue'])->name('storeRating.getValue')->middleware('can:storeRating.getValue');
});

Route::group(['prefix'=>'productRating'],function (){
    Route::post('addRating',[ProductRatingController::class,'addRating'])->name('productRating.add')->middleware('can:productRating.add');
    Route::post('updateRating',[ProductRatingController::class,'updateRating'])->name('productRating.update')->middleware('can:productRating.update');
    Route::delete('deleteRating/{rating_id}',[ProductRatingController::class,'deleteRating'])->name('productRating.delete')->middleware('can:productRating.delete');
    Route::get('getRatings/{product_id}',[ProductRatingController::class,'getRatings'])->name('productRating.get')->middleware('can:productRating.get');
    Route::get('getMyRating',[ProductRatingController::class,'getRatingUser'])->name('productRating.getMyUser')->middleware('can:productRating.getMyUser');
    Route::get('getRatingValue/{product_id}',[ProductRatingController::class,'getRatingValue'])->name('productRating.getValue')->middleware('can:productRating.getValue');
});
Route::group(['prefix'=>'location'],function (){

    Route::post('addLocation',[LocationsController::class,'addLocation'])->name('location.add')->middleware('can:location.add');
    Route::get('getUserLocations',[LocationsController::class,'getUserLocations'])->name('location.get')->middleware('can:location.get');
    Route::get('getLastUsedLocation',[LocationsController::class,'getLastUsedLocation'])->name('location.getLastUsed')->middleware('can:location.getLastUsed');
    Route::delete('deleteLocation/{id}',[LocationsController::class,'deleteLocation'])->name('location.delete')->middleware('can:location.delete');
    Route::get('getDefaultUserLocation',[LocationsController::class,'getDefaultUserLocation']);
});
Route::group(['prefix'=>'order'],function (){
    Route::get('getOrdersMyStore/{store_id}',[ProductOrderController::class,'getOrderMyStore'])->name('order.getMyStore')->middleware('can:order.getMyStore');
    Route::post('addProductToOrder',[ProductOrderController::class,'addProductToOrder'])->name('order.add')->middleware('can:order.add');
    Route::post('removeProductFromOrder',[ProductOrderController::class,'removeProductFromOrder'])->name('order.remove')->middleware('can:order.remove');
    Route::post('updateStatusOrder',[ProductOrderController::class,'updateStatusOrder'])->name('order.updateStatus')->middleware('can:order.updateStatus');
    Route::post('createOrder',[ProductOrderController::class,'createOrder'])->name('order.create')->middleware('can:order.create');
    Route::get('getUserOrders',[ProductOrderController::class,'getUserOrders'])->name('order.get')->middleware('can:order.get');
});
Route::group(['prefix'=>'wallet'],function (){
    Route::post('deposit',[WalletController::class,'deposit'])->name('wallet.deposit')->middleware('can:wallet.deposit');
    Route::get('balance', [WalletController::class, 'getMyBalance'])->name('wallet.getMyBalance')->middleware('can:wallet.getMyBalance');
});
Route::group(['prefix'=>'profile'],function (){
    Route::post('createProfile',[ProfileController::class,'createProfile'])->name('profile.create')->middleware('can:profile.create');
    Route::post('updateProfile',[ProfileController::class,'updateProfile'])->name('profile.update')->middleware('can:profile.update');
    Route::get('getProfile',[ProfileController::class,'getProfile'])->name('profile.get')->middleware('can:profile.get');
});
Route::group(['prefix'=>'user'],function (){
    Route::post('creatUser',[UsersController::class,'createUser'])->name('user.create')->middleware('can:user.create');
    Route::post('updateUser',[UsersController::class,'updateUser'])->name('user.update')->middleware('can:user.update');
    Route::delete('deleteUser/{id}',[UsersController::class,'deleteUser'])->name('user.delete')->middleware('can:user.delete');
    Route::get('getUsers',[UsersController::class,'getUsers'])->name('user.gets')->middleware('can:user.gets');
    Route::get('getUser',[UsersController::class,'getUser'])->name('user.get')->middleware('can:user.get');
    Route::post('searchByPhoneNumber',[UsersController::class,'searchUserByPhoneNumber'])->name('user.searchByPhoneNumber')->middleware('can:user.searchByPhoneNumber');
});
Route::group(['prefix'=>'notification'],function (){
    Route::post('sendNotification',[NotificationController::class,'sendNotification'])->name('notification.send');
    Route::get('index',[NotificationController::class,'index'])->name('notification.index');
    Route::get('unreadCount',[NotificationController::class,'unreadCount'])->name('notification.unreadCount');
});

});
