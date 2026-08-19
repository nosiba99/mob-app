<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    private function success($message, $data = null)
    {
        return response()->json([
            'status'  => true,
            'message' => $message,
            'data'    => $data
        ]);
    }

    private function error($message, $code = 400)
    {
        return response()->json([
            'status'  => false,
            'message' => $message,
            'data'    => null
        ], $code);
    }

    /*
    |--------------------------------------------------------------------------
    | تحديد الإشعار كمقروء
    |--------------------------------------------------------------------------
    */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->find($id);

        if (!$notification) {
            return $this->error(__('الإشعار غير موجود'), 404);
        }

        $notification->markAsRead();

        return $this->success(__('تم تحديد الإشعار كمقروء'));
    }

    /*
    |--------------------------------------------------------------------------
    | إشعارات غير مقروءة
    |--------------------------------------------------------------------------
    */
    public function unread(Request $request)
    {
        $notifications = $request->user()->unreadNotifications()->latest()->get();

        return $this->success(__('الإشعارات غير المقروءة'), $notifications);
    }

    /*
    |--------------------------------------------------------------------------
    | إشعارات مقروءة
    |--------------------------------------------------------------------------
    */
    public function read(Request $request)
    {
        $notifications = $request->user()->readNotifications()->latest()->get();

        return $this->success(__('الإشعارات المقروءة'), $notifications);
    }

    /*
    |--------------------------------------------------------------------------
    | إشعارات المستخدم
    |--------------------------------------------------------------------------
    */
    public function userNotifications(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'user') {
            return $this->error(__('غير مصرح لك بعرض هذه الإشعارات'), 403);
        }

        $notifications = $user->notifications()->latest()->get();

        return $this->success(__('إشعارات المستخدم'), $notifications);
    }

    /*
    |--------------------------------------------------------------------------
    | إشعارات المندوب
    |--------------------------------------------------------------------------
    */
    public function deliveryNotifications(Request $request)
    {
        $delivery = $request->user();

        if ($delivery->role !== 'delivery') {
            return $this->error(__('غير مصرح لك بعرض هذه الإشعارات'), 403);
        }

        $notifications = $delivery->notifications()->latest()->get();

        return $this->success(__('إشعارات المندوب'), $notifications);
    }

    /*
    |--------------------------------------------------------------------------
    | إشعارات الأدمن
    |--------------------------------------------------------------------------
    */
    public function adminNotifications(Request $request)
    {
        $admin = $request->user();

        if ($admin->role !== 'admin') {
            return $this->error(__('غير مصرح لك بعرض هذه الإشعارات'), 403);
        }

        $notifications = $admin->notifications()->latest()->get();

        return $this->success(__('إشعارات الأدمن'), $notifications);
    }
}
