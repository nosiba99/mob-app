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
            'name' => ['required', 'string'],
            'code' => ['nullable', 'string'],
        ]);

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
