<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;


class CategoryController extends Controller
{
    use StoreFileTrait;

    public function create(Request $request)
    {
        // Validate incoming request data
        $validator=Validator::make($request->all(),[
            'name' => 'required|string|max:255|unique:categories,name',
            'category_picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        if ($validator->fails())
            return ResponseFormatter::error("Validation Error",$validator->errors(),422);

        //store picture in project
        $fileUrl = $this->store($request['category_picture'], 'uploads');
        // Create a new category
        $category = Category::create([
            'name' => $request->name,
            'category_picture'=> $fileUrl
        ]);

        // Return a response
        return ResponseFormatter::success('Category created successfully.', $category,201);


    }
    public function update(Request $request)
    {
        // البحث عن التصنيف المطلوب
        $category = Category::query()->find($request->id);
        if (is_null($category)) {
            return ResponseFormatter::error('The Category Not Found', null,404);
        }

        // التحقق من صحة البيانات
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
            'category_picture' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'id' => 'required|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error("Validation Error", $validator->errors());
        }

        // تحديث الاسم إذا تم إرساله
        if ($request->has('name')) {
            $category->name = $request->name;
        }

        // تحديث الصورة إذا تم إرسال ملف جديد
        if ($request->hasFile('category_picture')) {
            if ($category->category_picture) {
                // حذف الصورة القديمة
                $oldFilePath = str_replace(asset('storage/'), '', $category->category_picture);
                if (Storage::disk('public')->exists($oldFilePath)) {
                    Storage::disk('public')->delete($oldFilePath);
                }
            }

            // رفع الصورة الجديدة
            $fileUrl = $this->store($request->file('category_picture'), 'uploads');
            $category->category_picture = $fileUrl;
        }

        // حفظ التحديثات
        $category->save();

        // إرسال الاستجابة
        return ResponseFormatter::success('The Category Updated successfully', $category);
    }
    public function delete($id)
    {
        $category = Category::query()->find($id);
        if (is_null($category)) {
            return ResponseFormatter::error('The Category Not Found',null);
        }
        if ($category->category_picture) {
            // حذف الصورة القديمة
            $oldFilePath = str_replace(asset('storage/'), '', $category->category_picture);
            if (Storage::disk('public')->exists($oldFilePath)) {
                Storage::disk('public')->delete($oldFilePath);
            }
        }
        $category->delete();
        return ResponseFormatter::success('The Category Deleted Successfully',$category);
    }
    public function getCategories()
    {
        $categories=Category::query()->latest()->get();
        if ($categories->isEmpty())
            return ResponseFormatter::error('Not Found Categories',null,404);
        return  ResponseFormatter::success('The Categories Got Successfully',$categories,200);
    }
    public function searchByCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'product_name' => 'required|string|max:255',
        ]);
        if ($validator->fails())
            return ResponseFormatter::error("Validation Error",$validator->errors(),422);

        $stores = Store::query()->where('category_id','LIKE','%'.$request['category_id'].'%')->get();
        $product = Product::query()->whereIn('store_id',$stores->pluck('id'))->where('name','LIKE','%'.$request['product_name'].'%')->get();

        return ResponseFormatter::success('searched successfully ',['stores'=>$stores,'products'=>$product],200);

    }


}
