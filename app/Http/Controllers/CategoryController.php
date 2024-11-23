<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use StoreFileTrait;

    public function create(Request $request)
    {
        // Validate incoming request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'category_picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        //store picture in project
        $fileUrl = $this->store($request['category_picture'], 'uploads');
        // Create a new category
        $category = Category::create([
            'name' => $validatedData['name'],
            'category_picture'=> $fileUrl
        ]);

        // Return a response
        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data' => $category,
        ], 201);
    }
    public function update(Request $request)
    {
        $category=Category::query()->find($request->id);
        if (is_null($category)){
            return response()->json([
                'success'=>false,
                'massage'=>'Category not found',
                'data'=>$category,
            ],404);
        }
        // Validate incoming request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'category_picture' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'id'=>'required|string'
        ]);
       if ($request->has('name')){
           $category->name = $validatedData['name'];
       }
       if ($request->hasFile('category_picture')){
           if ($category->category_picture){
               $oldFilePath = str_replace(asset(''), '', $category->category_picture);
               \Storage::disk('public')->delete($oldFilePath);
           }
           $fileUrl = $this->store($request['category_picture'], 'uploads');
           $category->category_picture = $fileUrl;
       }

        $category->save();

        // Return a response
        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data' => $category,
        ], 201);
    }

}
