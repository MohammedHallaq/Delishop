<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Product;
use App\Models\ProductRating;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    use StoreFileTrait;
   public function create(Request $request)
   {
       $user_id= Auth::id();
       $store = Store::query()->where('user_id',$user_id)->first();
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
       $fileUrl = $this->store($request['product_picture'], 'uploads');
       $product = Product::create([
           'store_id' => $store->id,
           'name' => $request->name,
           'product_picture' =>$fileUrl,
           'description' => $request->description,
           'price' => $request->price,
           'discount' => $request->discount,
           'quantity' => $request->quantity
       ]);
       return  ResponseFormatter::success('The product created successfully', $product, 201);
   }
    public function update(Request $request)
    {
        $user_id= Auth::id();

        $store = Store::query()->where('user_id',$user_id)->first();

        $product =Product::query()->find($request->id);

        if (is_null($product))
            return ResponseFormatter::error('The Product Not Found',null,404);

        if (is_null($store) || $store->id != $product->store_id )
            return ResponseFormatter::error('This user has no permission to update product', 'Access denied', 403);

        $validator = Validator::make($request->all(),[
            'name' => 'required|string|unique:products,name',
            'product_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'required|string|max:255',
            'price' => 'required|numeric',
            'discount' => 'numeric',
            'quantity' => 'required|numeric',
            'id' => 'required'
        ]);
        if ($validator->fails())
            return ResponseFormatter::error('Validation Error',$validator->errors(),422);

        if ($request->hasFile('product_picture')) {
            if ($product->product_picture) {
                // حذف الصورة القديمة
                $oldFilePath = str_replace(asset('storage/'), '',$product->product_picture);
                if (Storage::disk('public')->exists($oldFilePath)) {
                    Storage::disk('public')->delete($oldFilePath);
                }
            }

            // رفع الصورة الجديدة
            $fileUrl = $this->store($request->file('product_picture'), 'uploads');
            $product->product_picture = $fileUrl;
        }

        $product->name = $request->name;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->discount = $request->discount;
        $product->quantity = $request->quantity;
        $product->save();
        return  ResponseFormatter::success('The product updated successfully', $product, 200);
    }
    public function delete($id)
    {
        $user_id = Auth::id();

        $store = Store::query()->where('user_id',$user_id)->first();

        $product = Product::query()->find($id);

        if (is_null($product))
            return ResponseFormatter::error('The Product Not Found',null,404);

        if (is_null($store)  || $store->id != $product->store_id )
            return ResponseFormatter::error('This user has no permission to delete product', 'Access denied', 403);

        $product->delete();

        return ResponseFormatter::success('The Product Deleted Successfully',$product,200);
    }
    public  function getProductsByStore($store_id)
    {
        $products = Product::query()->with('store')->where('store_id',$store_id)->get();
        return ResponseFormatter::success('The Products Got By Store Successfully',$products,200);

    }
    public function getProduct($id)
    {
        $product = Product::query()->with('store')->find($id);
        if (is_null($product))
            return ResponseFormatter::error('The Product Not Found',null,404);
        $isFavorite = Favorite::where('user_id', Auth::id())
            ->where('product_id', $id)
            ->exists();

        // Get the average rating of the product
        $rating = ProductRating::where('product_id', $id)->avg('rating');

        // Prepare the response data
        $data = [
            'id' => $product->id,
            'store' => $product->store->id,
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




}
