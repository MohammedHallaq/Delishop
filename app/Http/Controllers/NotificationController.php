<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class NotificationController extends Controller
{
    public function sendNotificationToTargets($title,$data,$lang)
    {

        $user = User::query()->find($data['user_id']);
        $store = Store::with('user')->find($data['store_id']);
        $superAdmin = User::query()->where('role_id',1)->get();

        // إرسال للمستخدم
        if ($user) {
            $this->sendNotification($user, $title, $data, $lang);
        }

        // إرسال لصاحب المتجر
        if ($store && $store->user) {
            $this->sendNotification($store->user, $title, $data, $lang);
        }

        // إرسال للمشرفين
        foreach ($superAdmin as $admin) {
            $this->sendNotification($admin, $title, $data, $lang);
        }
    }
    public function sendNotification($user,$title,$data,$lang)
    {


        // Path to the service account key JSON file
        $serviceAccountPath = storage_path('delishop-5bd8e-firebase-adminsdk-lvnij-e47486d103.json');

        // Initialize the Firebase Factory with the service account
        $factory = (new Factory)->withServiceAccount($serviceAccountPath);

        // Create the Messaging instance
        $messaging = $factory->createMessaging();

        // Get the authenticated user

        // Check if FCM token exists
        if (empty($user->fcm_token)) {
            Log::warning("FCM token is missing for user ID: {$user->id}");
            return 0 ;
        }


        // اختيار اللغة من الكونفيج
        $message = config("notification_messages.{$lang}.{$title}.{$user->role_id}");

        // تخصيص الرسالة
        $message = strtr($message, [
            ':user'     => "{$user->first_name}"."{$user->last_name}",
            ':store'    => $data->store->name,
            ':location' => $data->location->location_name,
            ':reason'   => $data->message ?? '',
        ]);


        // Prepare the notification array
        $notification = [
            'title' => $title,
            'body' => $message,
            'data' => $data,
            'sound' => 'default',
        ];


        // Create the CloudMessage instance
        $cloudMessage = CloudMessage::withTarget('token', $user->fcm_token)
            ->withNotification(FirebaseNotification::create($title,$message));

        try {
            // Send the notification
            $messaging->send($cloudMessage);

            // Save the notification to the database
            Notification::query()->create([
                'user_id' => $user->id,
                'title' => $title,
                'body' => $message,
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
