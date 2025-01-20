<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProductRatingController extends Controller
{
    public function addRating(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|numeric|min:1|max:5',
            'product_id' => 'required|exists:products,id',
            'comment' => 'required|string|max:255',
        ]);
        if ($validator->fails()){
            return ResponseFormatter::error('Validation Error', $validator->errors(), 422);
        }
        if ($rating=ProductRating::query()->where('user_id',Auth::id())->first()){
            return ResponseFormatter::success('You cannot add a new rating',$rating,422);
        }

        $rating = ProductRating::query()->create([
            'user_id' => Auth::id(),
            'product_id' => $request->input('product_id'),
            'rating' => $request->input('rating'),
            'comment' => $request->input('comment'),
        ]);
        return ResponseFormatter::success('Add Rating Successful', $rating,200);
    }
    public function getRatingUser($product_id)
    {
        $ratingsProduct = ProductRating::query()->where('product_id',$product_id)->where('user_id',Auth::id())->get();
        return ResponseFormatter::success('Get My Rating successfully',$ratingsProduct,200);
    }
    public function getRatings($product_id)
    {
        $product = Product::query()->find($product_id);
        if (is_null($product))
            return ResponseFormatter::error('product not found',null ,404);

        $rating = ProductRating::query()->where('product_id', $product_id)->get();
        return ResponseFormatter::success('Get Rating Successful', $rating,200);
    }

    public function updateRating(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'nullable|numeric|min:1|max:5',
            'comment' => 'nullable|string|max:255',
            'rating_id' => 'required|exists:product_ratings,id',
        ]);
        if ($validator->fails()){
            return ResponseFormatter::error('Validation Error', $validator->errors(), 422);

        }
        $rating = ProductRating::query()->find($request->input('rating_id'));
        if (is_null($rating)){
            return ResponseFormatter::error(' Rating not found',null ,404);

        }
        if ($rating->user_id != Auth::id()){
            return ResponseFormatter::error('The user has no permission to edit',null ,403);

        }
        if ($request->filled('rating')){
            $rating->rating = $request->input('rating');
        }
        if ($request->filled('comment')){
            $rating->comment = $request->input('comment');
        }
        $rating->save();
        return ResponseFormatter::success('Update Rating Successful', $rating,200);
    }
    public function deleteRating($rating_id)
    {
        $rating = ProductRating::query()->find($rating_id);
        if (is_null($rating))
            return ResponseFormatter::error(' Rating not found',null ,404);
        if ($rating->user_id != Auth::id())
            return ResponseFormatter::error('The user has no permission to delete',null ,403);

        $rating->delete();

        return ResponseFormatter::success('Delete Rating Successful', $rating,200);
    }
    public function getRatingValue($product_id)
    {
        $product = Product::query()->find($product_id);

        if (is_null($product))
            return ResponseFormatter::error('Product not found',null ,404);

        $avg= ProductRating::query()->where('product_id', $product->id)->avg('rating');

        return ResponseFormatter::success('Get Avg Rating Successful', $avg,200);
    }

}
