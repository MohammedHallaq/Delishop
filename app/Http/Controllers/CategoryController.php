<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Keyword;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


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
        $fileUrl = $this->storePicture($request['category_picture'], 'uploads');
        // Create a new category
        $category = Category::query()->create([
            'name' => $request['name'],
            'category_picture'=> $fileUrl
        ]);

        // Return a response
        return ResponseFormatter::success('Category created successfully.', $category,201);


    }
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255|unique:categories,name',
            'category_picture' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'id' => 'required|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error("Validation Error", $validator->errors());
        }
        // البحث عن التصنيف المطلوب
        $category = Category::query()->find($request['id']);
        if (is_null($category)) {
            return ResponseFormatter::error('The Category Not Found', null,404);
        }
        // تحديث الاسم إذا تم إرساله
        if ($request->filled('name')) {
            $category->name = $request['name'];
        }
        // تحديث الصورة إذا تم إرسال ملف جديد
        if ($request->hasFile('category_picture')) {
            $fileUrl = $this->updatePicture($request['category_picture'],$category->category_picture);
            $category->category_picture = $fileUrl;
        }
        // حفظ التحديثات
        $category->save();

        // إرسال الاستجابة
        return ResponseFormatter::success('The Category Updated successfully', $category,200);
    }
    public function delete($id)
    {
        $category = Category::query()->find($id);
        if (is_null($category)) {
            return ResponseFormatter::error('The Category Not Found',null);
        }
        if ($category->category_picture) {
           $this->destroyPicture($category->category_picture);
        }
        $category->delete();
        return ResponseFormatter::success('The Category Deleted Successfully',$category,200);
    }
    public function getCategories()
    {
        $categories=Category::query()->latest()->get();
        if ($categories->isEmpty()){
            return ResponseFormatter::error('Not Found Categories',null,404);
        }
        return  ResponseFormatter::success('The Categories Got Successfully',$categories,200);
    }
    public function searchByCategory(Request $request)
    {
        $category_id = $request->input('category_id');
        $keyword= $request->input('keyword');
        if ($category_id == null ){
           $stores = Store::query()->where('name','LIKE','%'.$keyword.'%')->get();
           $products = Product::query()->where('name','LIKE','%'.$keyword.'%')->get();
           return ResponseFormatter::success('searched successfully',['stores'=>$stores,'products'=>$products],200);
        }
        $stores = Store::query()->where('category_id',$category_id)->get();
        $product = Product::query()->whereIn('store_id',$stores->pluck('id'))->where('name','LIKE','%'.$keyword.'%')->get();

        return ResponseFormatter::success('searched successfully ',['stores'=>$stores,'products'=>$product],200);

    }

    public function keywordSave(Request $request)
    {
        $validator = Validator::make(request()->all(),[
            'keyword' => 'required|string|max:255',
        ]);
        if ($validator->fails()){
            return ResponseFormatter::error("Validation Error",$validator->errors(),422);
        }
        $keyword = Keyword::query()->create([
            'user_id'=> Auth::id(),
            'keyword' => $request['keyword']
        ]);

        return ResponseFormatter::success('Keyword created successfully.',$keyword,201);
    }


}
