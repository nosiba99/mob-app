<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use App\Services\DeliveryService;
use Illuminate\Http\Request;

class DeliveryStatusController extends Controller
{
    public function __construct(private DeliveryService $deliveryService) {}

    private function success($message, $data = null)
    {
        return response()->json([
            'status'  => true,
            'message' => $message,
            'data'    => $data
        ]);
    }

    private function error($message, $code = 403)
    {
        return response()->json([
            'status'  => false,
            'message' => $message,
            'data'    => null
        ], $code);
    }

    // تغيير حالة المندوب (متاح / غير متاح)
    public function toggleAvailability(Request $request)
    {
        $delivery = $request->user();

        if ($delivery->role !== 'delivery') {
            return $this->error('Only delivery employees can change availability');
        }

        $updated = $this->deliveryService->toggleAvailability($delivery);

        return $this->success('Availability updated successfully', [
            'is_available' => $updated->is_available
        ]);
    }
}
