<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ColorService;
use App\Models\Color;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    public function __construct(private ColorService $colorService) {}

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

    // عرض كل الألوان
    public function index()
    {
        $colors = $this->colorService->getAll();
        return $this->success('Colors fetched successfully', $colors);
    }

    // إضافة لون جديد
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255',
        ]);

        $color = $this->colorService->create($request->all());

        if (!$color) {
            return $this->error('هذا اللون بهذا الكود مضاف مسبقًا', 422);
        }

        return $this->success('Color added successfully', $color);
    }

    // حذف لون
    public function destroy($id)
{
    $color = Color::find($id);

    if (!$color) {
        return $this->error('اللون غير موجود', 404);
    }

    $this->colorService->delete($color);

    return $this->success('تم حذف اللون بنجاح');
}

}
