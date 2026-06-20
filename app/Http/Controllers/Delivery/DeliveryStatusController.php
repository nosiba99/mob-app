<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeliveryStatusController extends Controller
{
    public function toggleAvailability(Request $request)
    {
        $delivery = auth()->user();

        if ($delivery->role !== 'delivery') {
            return response()->json([
                'message' => 'Only delivery employees can change availability'
            ], 403);
        }

        $delivery->is_available = !$delivery->is_available;
        $delivery->save();

        return response()->json([
            'message' => 'Availability updated successfully',
            'is_available' => $delivery->is_available
        ], 200);
    }
}
