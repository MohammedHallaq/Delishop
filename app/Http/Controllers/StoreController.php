<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\Store;
use App\Models\StoreRating;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StoreController extends Controller
{
   use StoreFileTrait;

   public function create(Request $request)
   {


       $validator = Validator::make($request->all(), [
           'name' => 'required|unique:stores,name',
           'store_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
           'description' => 'required',
           'location_name' => 'required',
           'location_url' => 'required|url',
           'category' => 'required|exists:categories,name',
           'phone_number' => 'required|exists:users,phone_number',
       ]);
       if ($validator->fails()) {
           return ResponseFormatter::error('Validation Error', $validator->errors(), 422);
       }
       //store picture in project
       $fileUrl = $this->store($validator['store_picture'], 'uploads');
       $store = Store::query()->create([
           'user_id' => User::query()->where('phone_number', $validator['phone_number'])->first()->id,
           'category_id' => Category::query()->where('name', $validator['category'])->first()->id,
           'name' => $validator['name'],
           'store_picture' => $fileUrl,
           'description' => $validator['description'],
           'location_name' => $validator['location_name'],
           'location_url' => $validator['location_url'],
       ]);
       return ResponseFormatter::success('The Store Created Successfully',$store,201);
   }

    public function update(Request $request)
    {
        $store = Store::query()->find($request->id);
        if (is_null($store))
            return ResponseFormatter::error('The Store Not Found',null,404);

        $user_id=Auth::id();

        if ($user_id!=$store->user_id)
            return ResponseFormatter::error('This user has no permission to edit',null,403);

        $validator = Validator::make($request->all(), [
            'name' => 'unique:stores,name',
            'store_picture' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'string|max:255',
            'location_name' => '',
            'location_url' => 'url',
            'category' => 'exists:categories,name',
            'id' => 'required|exists:stores,id',
        ]);
        if ($validator->fails()) {
            return ResponseFormatter::error('Validation Error', $validator->errors(), 422);
        }
        if ($request->hasFile('store_picture')) {
            if ($store->store_picture) {
                // حذف الصورة القديمة
                $oldFilePath = str_replace(asset('storage/'), '',$store->store_picture);
                if (Storage::disk('public')->exists($oldFilePath)) {
                    Storage::disk('public')->delete($oldFilePath);
                }
            }

            // رفع الصورة الجديدة
            $fileUrl = $this->store($request->file('store_picture'), 'uploads');
            $store->store_picture = $fileUrl;
        }
        //store picture in project

             $store->category_id = Category::query()->where('name',$validator['category'])->first()->id;
             $store->name = $validator['name'];
             $store->description = $validator['description'];
             $store->location_name = $validator['location_name'];
             $store->location_url = $validator['location_url'];
             $store->save();

        return ResponseFormatter::success('The Store Updated Successfully',$store,200);
    }
    public function delete($id)
    {
        $store = Store::query()->find($id);
        if (is_null($store))
            return ResponseFormatter::error('The Store Not Found',null,404);
        $user_id = Auth::id();
        if ($user_id!=$store->user_id)
            return ResponseFormatter::error('This user has no permission to delete',null,403);
        if ($store->store_picture) {
                // حذف الصورة القديمة
                $oldFilePath = str_replace(asset('storage/'), '',$store->store_picture);
                if (Storage::disk('public')->exists($oldFilePath)) {
                    Storage::disk('public')->delete($oldFilePath);
                }
        }
        $store->delete();
        return ResponseFormatter::success('The Store Deleted Successfully',$store,200);

    }
    public function getStoreByCategory($category_id)
    {
        $store = Store::query()->where('category_id',$category_id)->get();
        return ResponseFormatter::success('The Store Got Successfully',$store,200);
    }
    public function search(Request $request)
    {
        $storeName = $request->input('name');
        $categoryId = $request->input('category_id');

        $query = Store::query();

        if ($storeName) {
            $query->where('name', 'like', '%' . $storeName . '%');
        }
        if ($categoryId) {
            $query->where('category_id',$categoryId);
        }

        $stores = $query->get();

        if ($stores->isEmpty())
            return ResponseFormatter::error('No Stores  Found',null,404);

        return ResponseFormatter::success(' Stores retrieved successfully',$stores,200);
    }
    public function getStoresByIds(Request $request)
    {
        // Get the list of store IDs from the request input
        $storeIds = $request->input('store_ids');

        // Validate that store_ids is an array
        if (!is_array($storeIds)) {
            return ResponseFormatter::error('Store IDs must be provided as an array', null, 400);
        }

        // If the array is empty, return an empty success response
        if (empty($storeIds)) {
            return ResponseFormatter::success('No Stores Found', [], 200);
        }

        // Fetch stores by the provided IDs
        $stores = Store::whereIn('id', $storeIds)->get();

        // Return the stores in a success response
        return ResponseFormatter::success('Stores retrieved successfully', $stores, 200);
    }

    public function getStore($id)
    {
        $store = Store::query()->find($id);
        if (is_null($store))
            return ResponseFormatter::error('The Store Not Found',null,404);
        // التحقق مما إذا كان المتجر موجوداً في المفضلة
        $isFavorite = Favorite::where('user_id', $store->user_id)
            ->where('product_id', $id)
            ->exists();

        // الحصول على متوسط تقييمات المتجر
        $rating = StoreRating::where('store_id', $id)->avg('rating');

        // تشكيل البيانات للخروج
        $data = [
            'id' => $store->id,
            'user_id' => $store->user_id,
            'category_id' => $store->category_id,
            'name' => $store->name,
            'store_picture' => $store->store_picture,
            'location_name' => $store->location_name,
            'location_url' => $store->location_url,
            'description' => $store->description,
            'is_favorite' => $isFavorite,
            'rating' => $rating ?? null,
            'created_at' => $store->created_at,
            'updated_at' => $store->updated_at,
        ];

        return ResponseFormatter::success('The Store Got Successfully',$data,200);
    }


}
