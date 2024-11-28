<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\StoreRating;
class StoreRatingController extends Controller
{
    public function addRating(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|numeric|min:1|max:5',
            'store_id' => 'required|exists:stores,id',
            'comment' => 'required|string|max:255',
        ]);
        if ($validator->fails())
            return ResponseFormatter::error('Validation Error', $validator->errors(), 422);

        $rating = StoreRating::create([
            'user_id' => Auth::id(),
            'store_id' => $request->input('store_id'),
            'rating' => $request->input('rating'),
            'comment' => $request->input('comment'),
        ]);
        return ResponseFormatter::success('Add Rating Successful', $rating,200);
    }
    public function getRatings($id_store)
    {
        $store = Store::query()->find($id_store);
        if (is_null($store))
            return ResponseFormatter::error('Store not found',null ,404);

        $rating = StoreRating::query()->where('store_id', $id_store)->get();
        return ResponseFormatter::success('Get Rating Successful', $rating,200);
    }

    public function updateRating(Request $request)
    {
        $user_id = Auth::id();
        $rating = StoreRating::query()->find($request->input('id'));
        if (is_null($rating))
            return ResponseFormatter::error(' Rating not found',null ,404);
        if ($rating->user_id != $user_id)
            return ResponseFormatter::error('The user has no permission to edit',null ,403);
        $validator = Validator::make($request->all(), [
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'required|string|max:255',
            'id' => 'required|exists:store_ratings,id',
        ]);
        if ($validator->fails())
            return ResponseFormatter::error('Validation Error', $validator->errors(), 422);

        $rating->rating = $request->input('rating');
        $rating->comment = $request->input('comment');
        $rating->save();
        return ResponseFormatter::success('Update Rating Successful', $rating,200);

    }
    public function deleteRating($id_rating)
    {
        $user_id = Auth::id();
        $rating = StoreRating::query()->find($id_rating);
        if (is_null($rating))
            return ResponseFormatter::error(' Rating not found',null ,404);
        if ($rating->user_id != $user_id)
            return ResponseFormatter::error('The user has no permission to delete',null ,403);

        $rating->delete();

        return ResponseFormatter::success('Delete Rating Successful', $rating,200);
    }
    public function getRatingValue($store_id)
    {
         $store = Store::query()->find($store_id);

         if (is_null($store))
             return ResponseFormatter::error('Store not found',null ,404);

         $avg= StoreRating::query()->where('store_id', $store->id)->avg('rating');

         return ResponseFormatter::success('Get Avg Rating Successful', $avg,200);
    }
}
