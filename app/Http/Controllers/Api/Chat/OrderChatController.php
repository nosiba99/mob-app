<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Events\AdminNewMessage;
use App\Http\Resources\OrderMessageResource; 
class OrderChatController extends Controller
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

    // إرسال رسالة
   // إرسال رسالة
public function send(Request $request, $orderId)
{
    $user = $request->user();

    $request->validate([
        'message' => 'required|string',
    ]);

    $order = Order::find($orderId);

    if (!$order) {
        return $this->error('الطلب غير موجود', 404);
    }

    // منع المراسلة قبل قبول الطلب
    if ($order->status !== Order::STATUS_ACCEPTED) {
        return $this->error('لا يمكن بدء المحادثة قبل قبول الطلب من المندوب', 403);
    }

    // منع المراسلة بعد تسليم الطلب
    if ($order->status === Order::STATUS_DELIVERED) {
        return $this->error('تم تسليم الطلب، انتهت صلاحية المحادثة', 403);
    }

    // منع المراسلة إذا الطلب ملغي أو مرفوض
    if (in_array($order->status, [
        Order::STATUS_CANCELED,
        Order::STATUS_REJECTED,
        Order::STATUS_RETURNED
    ])) {
        return $this->error('لا يمكن المراسلة على هذا الطلب', 403);
    }

    // تحديد المستقبل
    if ($user->role === 'user') {
        $receiverId = $order->delivery_id;
    } elseif ($user->role === 'delivery') {
        $receiverId = $order->user_id;
    } else {
        return $this->error('غير مسموح', 403);
    }

    if (!$receiverId) {
        return $this->error('لا يوجد مندوب مرتبط بهذا الطلب بعد', 400);
    }

    $msg = OrderMessage::create([
        'order_id'   => $order->id,
        'sender_id'  => $user->id,
        'receiver_id'=> $receiverId,
        'message'    => $request->message,
    ]);

    event(new AdminNewMessage($msg));

    return $this->success('تم إرسال الرسالة', $msg);
}


    // جلب الرسائل
    public function messages(Request $request, $orderId)
    {
        $user = $request->user();

        $order = Order::find($orderId);

        if (!$order) {
            return $this->error('الطلب غير موجود', 404);
        }

        if (!in_array($user->id, [$order->user_id, $order->delivery_id])) {
            return $this->error('غير مسموح', 403);
        }

       $messages = OrderMessage::where('order_id', $orderId)
    ->with(['sender', 'receiver'])
    ->orderBy('id', 'asc')
    ->get();

return $this->success('تم جلب الرسائل', OrderMessageResource::collection($messages));

}

}