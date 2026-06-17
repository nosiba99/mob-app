<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Size;
use Illuminate\Http\Request;

class SizeController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => true,
            'sizes' => Size::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string'],
        ]);

        $size = Size::create($request->only(['name']));

        return response()->json([
            'status' => true,
            'message' => 'Size added successfully',
            'size' => $size
        ]);
    }

    public function destroy(Size $size)
    {
        $size->delete();

        return response()->json([
            'status' => true,
            'message' => 'Size deleted successfully'
        ]);
    }
}
