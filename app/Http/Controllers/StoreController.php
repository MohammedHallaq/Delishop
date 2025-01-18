<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Keyword;
use App\Models\Store;
use App\Models\StoreRating;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class StoreController extends Controller
{
   use StoreFileTrait;

   public function create(Request $request)
   {


       $validator = Validator::make($request->all(), [
           'name' => 'required',
           'store_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
           'description' => 'required',
           'location_name' => 'required',
           'location_url' => 'required|url',
           'category_id' => 'required|exists:categories,id',
       ]);
       if ($validator->fails()) {
           return ResponseFormatter::error('Validation Error', $validator->errors(), 422);
       }
       //store picture in project
       $fileUrl = $this->storePicture($request['store_picture'], 'uploads');
       $store = Store::query()->create([
           'user_id' => Auth::id(),
           'category_id' => $request['category_id'],
           'name' => $request['name'],
           'store_picture' => $fileUrl,
           'description' => $request['description'],
           'location_name' => $request['location_name'],
           'location_url' => $request['location_url'],
       ]);
       $keyword = Keyword::query()->where('keyword',$request['name'])->pluck('user_id')->toArray();
       $users = User::query()->whereIn('id',$keyword)->get();
       foreach ($users as $user){
           ( new NotificationController )->sendNotification($user,'New Store','Dear'.$user->first_name.'the store you
           previously searched for has been added',$store);
       }
       return ResponseFormatter::success('The Store Created Successfully',$store,201);
   }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable',
            'store_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string|max:255',
            'location_name' => 'nullable|string|max:255',
            'location_url' => 'nullable|url',
            'category_id' => 'nullable|exists:categories,id',
        ]);
        if ($validator->fails()) {
            return ResponseFormatter::error('Validation Error', $validator->errors(), 422);
        }

        $store = Store::query()->where('user_id',Auth::id())->first();
        if (is_null($store)){
            return $this->create($request);
        }

        if ($request->hasFile('store_picture')) {
            $newPath=$this->updatePicture($request['store_picture'],$store->store_picture);
            $store->store_picture  = $newPath ;
        }
        if ($request->filled('category_id')){
            $store->category_id = $request['category_id'];
        }
        if ($request->filled('name')){
            $store->name = $request['name'];
        }
        if ($request->filled('description')){
            $store->description = $request['description'];
        }
        if ($request->filled('location_name')){
            $store->location_name = $request['location_name'];
        }
        if ($request->filled('location_url')){
            $store->location_url = $request['location_url'];
        }
        $store->save();



        return ResponseFormatter::success('The Store Updated Successfully',$store,200);
    }
    public function delete($id)
    {
        $store = Store::query()->find($id);
        if (is_null($store))
            return ResponseFormatter::error('The Store Not Found',null,404);
        if (Auth::id() != $store->user_id)
            return ResponseFormatter::error('This user has no permission to delete',null,403);
        if ($store->store_picture) {
            $this->destroyPicture($store->store_picture);
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
        $isFavorite = Favorite::query()->where('user_id', $store->user_id)
            ->where('product_id', $id)
            ->exists();

        // الحصول على متوسط تقييمات المتجر
        $rating = StoreRating::query()->where('store_id', $id)->avg('rating');

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
    public function getMyStore()
    {
        $store = Store::query()->where('user_id', Auth::id())->first();
    
        if (!$store) {
            return ResponseFormatter::error('The Store Not Found', null, 404);
        }
    
        return ResponseFormatter::success('Get My Store successfully', $store, 200);
    }
    

}
