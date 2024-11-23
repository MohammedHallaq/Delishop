<?php

namespace App\Http\Controllers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait StoreFileTrait
{
    static function store($file, $location): string
    {
        // تأكد من أن $file هو كائن UploadedFile
        if (!$file instanceof UploadedFile) {
            throw new \InvalidArgumentException('The provided file is not valid.');
        }

        // إنشاء اسم ملف فريد
        $fileNameToStore = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        // تخزين الملف في الموقع المحدد ضمن التخزين العام
        $file->storeAs($location, $fileNameToStore, 'public');

        // إرجاع الرابط الكامل للملف
        $publicUrl = asset('storage/' . $location . '/' . $fileNameToStore);
        return $publicUrl;
    }
}
