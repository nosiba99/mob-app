<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\SizeService;
use App\Models\Size;
use Illuminate\Http\Request;

class SizeController extends Controller
{
    public function __construct(private SizeService $sizeService) {}

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

    // عرض كل المقاسات
    public function index()
    {
        $sizes = $this->sizeService->getAll();
        return $this->success('Sizes fetched successfully', $sizes);
    }

    // إضافة مقاس جديد
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:sizes,name',
        ], [
            'name.required' => 'اسم المقاس مطلوب.',
            'name.unique'   => 'هذا المقاس موجود مسبقًا، لا يمكن إضافته مرة أخرى.',
            'name.max'      => 'اسم المقاس طويل جدًا.',
        ]);

        $size = $this->sizeService->create($request->all());

        return $this->success('تم إضافة المقاس بنجاح', $size);
    }

    // حذف مقاس
    public function destroy($id)
{
    $size = Size::find($id);

    if (!$size) {
        return $this->error('المقاس غير موجود', 404);
    }

    $this->sizeService->delete($size);

    return $this->success('تم حذف المقاس بنجاح');
}

}
