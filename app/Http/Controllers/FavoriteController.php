<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use function Laravel\Prompts\select;

class FavoriteController extends Controller
{
    // إضافة منتج إلى المفضلة
    public function addToFavorites(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error('Validation Error', $validator->errors(), 422);
        }

        $userId = Auth::id();

        // تحقق إذا كان المنتج موجودًا بالفعل في المفضلة
        $exists = Favorite::query()->where('user_id', $userId)
            ->where('product_id', $request['product_id'])
            ->exists();

        if ($exists) {
            return ResponseFormatter::error('Product is already in favorites', null, 409);
        }

        $favorite = Favorite::query()->create([
            'user_id' => $userId,
            'product_id' => $request['product_id'],
        ]);

        return ResponseFormatter::success('Product added to favorites', $favorite, 201);
    }

    // حذف منتج من المفضلة
    // public function removeFromFavorites($id)
    // {

    //     $favorite = Favorite::query()->find($id);

    //     if (!$favorite) {
    //         return ResponseFormatter::error('Product not found in favorites', null, 404);
    //     }

    //     $favorite->delete();

    //     return ResponseFormatter::success('Product removed from favorites', $favorite, 200);
    // }
    public function removeFromFavorites($productId)
{
    $userId = Auth::id();  // Get the authenticated user's ID

    // Find the favorite by user_id and product_id
    $favorite = Favorite::query()->where('user_id', $userId)
                        ->where('product_id', $productId)
                        ->first();

    // If the favorite doesn't exist, return an error
    if (!$favorite) {
        return ResponseFormatter::error('Product not found in favorites', null, 404);
    }

    // Delete the favorite
    $favorite->delete();

    // Return success response
    return ResponseFormatter::success('Product removed from favorites', $favorite, 200);
}

// عرض قائمة المنتجات المفضلة
public function getFavorites()
{
    $user_id = Auth::id();

    // الحصول على قائمة المنتجات المفضلة مع معلومات المنتج فقط
    $favorites = Favorite::query()->where('user_id', $user_id)
        ->with(['product' => function ($query) {
            $query->select('id', 'store_id', 'name', 'product_picture', 'description', 'price', 'discount', 'quantity');
        }])
        ->get()
        ->pluck('product'); // استخرج فقط معلومات المنتج من العلاقة

    // If there are no favorites, return the same response model with an empty list
    return ResponseFormatter::success('Favorite products retrieved successfully', $favorites, 200);
}

}
