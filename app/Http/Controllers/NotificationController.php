<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Exception\FirebaseException;

class NotificationController extends Controller
{
    public function sendNotification($user,$title,$body,$data)
    {
        // Path to the service account key JSON file
        $serviceAccountPath = storage_path('delishop-5bd8e-firebase-adminsdk-lvnij-279d324850.json');

        // Initialize the Firebase Factory with the service account
        $factory = (new Factory)->withServiceAccount($serviceAccountPath);

        // Create the Messaging instance
        $messaging = $factory->createMessaging();

        // Get the authenticated user

        // Check if FCM token exists
        if (empty($user->fcm_token)) {
            Log::error('FCM token is missing for user: ' . $user->id);
            return ResponseFormatter::error('FCM token is missing for user: ' . $user->id,null,400);
        }

        // Prepare the notification array
        $notification = [
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'sound' => 'default',
        ];


        // Create the CloudMessage instance
        $cloudMessage = CloudMessage::withTarget('token', $user->fcm_token)
            ->withNotification($notification);

        try {
            // Send the notification
            $messaging->send($cloudMessage);

            // Save the notification to the database
            Notification::query()->create([
                'user_id' => $user->id,
                'title' => $title,
                'body' => $body,
                'is_read' => false, // الإشعار غير مقروء افتراضيًا
                'data' => $data,
            ]);

            return response()->json(['message' => 'Notification sent successfully'], 200);
        } catch (MessagingException $e) {
            Log::error('Failed to send notification: ' . $e->getMessage());
            return ResponseFormatter::error('Failed to send notification: ' . $e->getMessage(),null,500);
        } catch (FirebaseException $e) {
            Log::error('Firebase error: ' . $e->getMessage());
            return ResponseFormatter::error('Firebase error: ' . $e->getMessage(),null,500);
        }
    }
    public function unreadCount()
    {

        $count = Notification::query()->where('user_id',Auth::id())->where('is_read',false)->count();
       return ResponseFormatter::success('get count notification is not read successfully',['count' => $count],200);
    }
    public function index()
    {
        // استرجاع الإشعارات الخاصة بالمستخدم الحالي
        $notifications = Notification::query()->where('user_id',Auth::id())->get();

        // تحديث حالة الإشعارات إلى "تمت القراءة"
        foreach ($notifications as $notification) {
            $notification->is_read = true;
            $notification->save();
        }

        return ResponseFormatter::success('get notification successfully ',['notification'=>$notifications],200);
    }
}
