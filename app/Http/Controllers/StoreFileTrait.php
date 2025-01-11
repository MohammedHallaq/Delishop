<?php

namespace App\Http\Controllers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

trait StoreFileTrait
{
    static function storePicture($file, $location)
    {
        if (!$file instanceof UploadedFile) {
            throw new \InvalidArgumentException('The provided file is not valid.');
        }

        // إنشاء اسم ملف فريد
        $fileNameToStore = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        $file->move(public_path('storage/'.$location), $fileNameToStore);

       return  url('storage/uploads/'.$fileNameToStore);


    }
    public  function destroyPicture($oldFilePath)
    {
        // تحويل الرابط الكامل إلى مسار داخل المجلد العام
        $relativePath = str_replace(url('storage/uploads') . '/', '', $oldFilePath);
        $absolutePath = public_path('storage/uploads/' . $relativePath);

        // التحقق من وجود الملف وحذفه
        if (File::exists($absolutePath)) {
            File::delete($absolutePath);
        }

    }

    public function updatePicture( $file, $oldFilePath)
    {
        // حذف الملف القديم إذا وُجد
        // حذف الملف القديم إذا كان موجودًا
        if ($oldFilePath) {
            self::destroyPicture($oldFilePath);
        }

        return self::storePicture($file, 'uploads');
    }


}
