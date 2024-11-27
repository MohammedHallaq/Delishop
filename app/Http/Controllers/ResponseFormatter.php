<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\JsonResponse;

class ResponseFormatter
{
    /**
     * صيغة الاستجابة الموحدة للنجاح.
     */
    public static function success($message, $data = null, $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * صيغة الاستجابة الموحدة للأخطاء.
     */
    public static function error($message, $errors = null, $statusCode = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }

}
