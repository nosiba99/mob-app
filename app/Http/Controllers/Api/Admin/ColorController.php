<?php

namespace App\Http\Controllers\Api\Admin;


use App\Http\Controllers\Controller;
use App\Models\Color;
use Illuminate\Http\Request;


class ColorController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => true,
            'colors' => Color::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
    'name' => 'required|string|max:255',
    'code' => 'required|string|max:255',
]);

$exists = Color::where('name', $request->name)
               ->where('code', $request->code)
               ->exists();

if ($exists) {
    return response()->json([
        'status'  => false,
        'message' => 'هذا اللون بهذا الكود مضاف مسبقًا',
    ], 422);
}

        $color = Color::create($request->only(['name', 'code']));

        return response()->json([
            'status' => true,
            'message' => 'Color added successfully',
            'color' => $color
        ]);
    }

    public function destroy(Color $color)
    {
        $color->delete();

        return response()->json([
            'status' => true,
            'message' => 'Color deleted successfully'
        ]);
    }
}
