<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Keyword;
use App\Models\Product;
use App\Models\ProductRating;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    use StoreFileTrait;
   public function create(Request $request)
   {
       $store = Store::query()->where('user_id',Auth::id())->first();
       if (is_null($store) )
           return ResponseFormatter::error('This user has no permission to create product', 'Access denied', 403);

       $validator = Validator::make($request->all(),[
           'name' => 'required|string|unique:products,name',
           'product_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
           'description' => 'required|string|max:255',
           'price' => 'required|numeric',
           'discount' => 'numeric',
           'quantity' => 'required|numeric'
       ]);
       if ($validator->fails())
           return ResponseFormatter::error('Validation Error',$validator->errors(),422);
       $fileUrl = $this->storePicture($request['product_picture'], 'uploads');
       $product = Product::query()->create([
           'store_id' => $store->id,
           'name' => $request['name'],
           'product_picture' =>$fileUrl,
           'description' => $request['description'],
           'price' => $request['price'],
           'discount' => $request['discount'],
           'quantity' => $request['quantity']
       ]);
       $keyword = Keyword::query()->where('keyword',$request['name'])->pluck('user_id')->toArray();
       $users = User::query()->whereIn('id',$keyword)->get();
       foreach ($users as $user){
           ( new NotificationController )->sendNotification($user,'New Product','Dear'.$user->first_name.'the product you
           previously searched for has been added',$product);
       }
       return  ResponseFormatter::success('The product created successfully', $product, 201);
   }
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'name' => 'nullable|string|unique:products,name',
            'product_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string|max:255',
            'price' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'quantity' => 'nullable|numeric',
            'product_id' => 'required|exists:products,id'
        ]);

        if ($validator->fails())
            return ResponseFormatter::error('Validation Error',$validator->errors(),422);

        $store = Store::query()->where('user_id',Auth::id())->first();

        $product =Product::query()->find($request['product_id']);

        if (is_null($product))
            return ResponseFormatter::error('The Product Not Found',null,404);

        if (is_null($store) || $store->id != $product->store_id )
            return ResponseFormatter::error('This user has no permission to update product', 'Access denied', 403);


        if ($request->hasFile('product_picture')) {

            $fileUrl = $this->updatePicture($request['product_picture'], $product->product_picture);
            $product->product_picture = $fileUrl;
        }
        // we had a problem here, when we pass the same name....
        // if ($request->filled('name')){
        //     $product->name = $request->input('name');
        // }

        // Solution
        if ($request->filled('name')) {
            // Check if the new name exists in the database for another product
            if (Product::query()->where('name', $request['name'])->exists() && $product->name != $request['name']) {
                return ResponseFormatter::error('Validation Error', ["name" => [
                    "The name is already used by another product."
                ]], 422);
            }
        
            // Update the name only if it's different
            if ($product->name != $request['name']) {
                $product->name = $request->input('name');
            }
        }

        if ($request->filled('description')){
            $product->description = $request->input('description');
        }
        if ($request->filled('price')){
            $product->price = $request->input('price');
        }
        if ($request->filled('discount')){
            $product->discount = $request->input('discount');
        }
        if ($request->filled('quantity')){
            $product->quantity = $request->input('quantity');
        }

        $product->save();
        return  ResponseFormatter::success('The product updated successfully', $product, 200);
    }
    public function delete($id)
    {


        $store = Store::query()->where('user_id',Auth::id())->first();

        $product = Product::query()->find($id);

        if (is_null($product))
            return ResponseFormatter::error('The Product Not Found',null,404);

        if (is_null($store)  || $store->id != $product->store_id )
            return ResponseFormatter::error('This user has no permission to delete product', 'Access denied', 403);
        if ($product->product_picture){
            $this->destroyPicture($product->product_picture);
        }
        $product->delete();

        return ResponseFormatter::success('The Product Deleted Successfully',$product,200);
    }
    public function getProductsByStore($store_id)
    {
        // Check if the store exists
        $store = Store::query()->find($store_id);

        if (!$store) {
            return ResponseFormatter::error('Store not found', null, 404);
        }

        // Fetch products by store_id
        $products = Product::query()->where('store_id', $store_id)->get();

        if ($products->isEmpty()) {
            return ResponseFormatter::error('No products found for this store', [], 404);
        }

        return ResponseFormatter::success('Products retrieved successfully', $products, 200);
    }
    public function getProductsMyStore()
    {
        // Check if the store exists
        $store = Store::query()->where('user_id',Auth::id())->first ();

        if (!$store) {
            return ResponseFormatter::error('Store not found', null, 404);
        }

        // Fetch products by store_id
        $products = Product::query()->where('store_id', $store->id)->get();

        if ($products->isEmpty()) {
            return ResponseFormatter::error('No products found for this store', [], 404);
        }

        return ResponseFormatter::success('Products retrieved successfully', $products, 200);
    }

    public function getProduct($id)
    {
        $product = Product::query()->with('store')->find($id);
        if (is_null($product))
            return ResponseFormatter::error('The Product Not Found',null,404);
        $isFavorite = Favorite::query()->where('user_id', Auth::id())
            ->where('product_id', $id)
            ->exists();

        // Get the average rating of the product
        $rating = ProductRating::query()->where('product_id', $id)->avg('rating');

        // Prepare the response data
        $data = [
            'id' => $product->id,
            'store_id' => $product->store->id,
            'name' => $product->name,
            'description' => $product->description,
            'product_picture' => $product->product_picture,
            'price' => $product->price,
            'discount' => $product->discount,
            'quantity' => $product->quantity,
            'is_favorite' => $isFavorite,
            'rating' => $rating ?? null,
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
        ];

        return ResponseFormatter::success('The Product Got Successfully',$data,200);
    }

    public function search(Request $request)
    {
        // التحقق من وجود الاسم في الطلب
        $productName = $request->input('name');
        $storeId = $request->input('store_id');

        // استعلام البحث الأساسي
        $query = Product::query();

        // إضافة شرط البحث بالاسم
        if ($productName) {
            $query->where('name', 'LIKE', '%' . $productName . '%');
        }

        // إضافة شرط البحث برقم المتجر إذا كان موجودًا
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        // تنفيذ الاستعلام
        $products = $query->get();
        // التحقق من النتائج
        if ($products->isEmpty()) {
            return ResponseFormatter::error('No products found', null, 404);
        }

        return ResponseFormatter::success('Products retrieved successfully', $products, 200);
    }

    public function getProductsByIds(Request $request)
    {
        // Get the list of store IDs from the request input
        $productIds = $request->input('products_ids');

        // Validate that store_ids is an array
        if (!is_array($productIds)) {
            return ResponseFormatter::error('product IDs must be provided as an array', null, 400);
        }

        // If the array is empty, return an empty success response
        if (empty($productIds)) {
            return ResponseFormatter::success('No Product Found', [], 200);
        }

        if (empty($request->input('products_ids')))
            return ResponseFormatter::success('the products not found',[],404);

        $products = Product::query()->whereIn('id',$request->input('products_ids'))->get();
        return  ResponseFormatter::success('get Products successfully',$products,200);
    }




}
