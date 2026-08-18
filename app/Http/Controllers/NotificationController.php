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

    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->latest()->get();
        return $this->success('الإشعارات', $notifications);
    }

    public function unread(Request $request)
    {
        $notifications = $request->user()->unreadNotifications()->latest()->get();
        return $this->success('الإشعارات غير المقروءة', $notifications);
    }

    public function read(Request $request)
    {
        $notifications = $request->user()->readNotifications()->latest()->get();
        return $this->success('الإشعارات المقروءة', $notifications);
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->notifications()->find($id);

        if (!$notification) {
            return $this->error('الإشعار غير موجود', 404);
        }

        $notification->markAsRead();
        return $this->success('تم تحديد الإشعار كمقروء');
    }
}