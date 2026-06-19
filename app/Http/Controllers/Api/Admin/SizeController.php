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
        'name' => 'required|string|max:255|unique:sizes,name',
    ], [
        'name.required' => 'اسم المقاس مطلوب.',
        'name.unique'   => 'هذا المقاس موجود مسبقًا، لا يمكن إضافته مرة أخرى.',
        'name.max'      => 'اسم المقاس طويل جدًا.',
    ]);

    $size = Size::create($request->only(['name']));

    return response()->json([
        'status' => true,
        'message' => 'تم إضافة المقاس بنجاح',
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
