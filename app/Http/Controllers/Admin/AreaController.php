<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:areas,name'
        ]);

        $area = Area::create([
            'name' => $request->name
        ]);

        return response()->json([
            'message' => 'Area created successfully',
            'data' => $area
        ], 201);
    }

    public function index()
{
    $areas = Area::all();

    return response()->json([
        'message' => 'Areas retrieved successfully',
        'data' => $areas
    ], 200);
}


   public function destroy($id)
{
    $area = Area::find($id);

    if (!$area) {
        return response()->json([
            'message' => 'Area not found'
        ], 404);
    }

    // تحقق إذا في مندوبين مرتبطين بالمنطقة
    $hasDeliveryUsers = \App\Models\User::where('area_id', $id)
                                        ->where('role', 'delivery')
                                        ->exists();

    if ($hasDeliveryUsers) {
        return response()->json([
            'message' => 'Cannot delete area because delivery employees are assigned to it'
        ], 400);
    }

    // حذف المنطقة
    $area->delete();

    return response()->json([
        'message' => 'Area deleted successfully'
    ], 200);
}


}
