<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AreaService;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function __construct(private AreaService $areaService) {}

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

    // إنشاء منطقة جديدة
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:areas,name'
        ]);

        $area = $this->areaService->create($request->all());

        return $this->success('Area created successfully', $area);
    }

    // عرض كل المناطق
    public function index()
    {
        $areas = $this->areaService->getAll();
        return $this->success('Areas retrieved successfully', $areas);
    }

    // حذف منطقة
    public function destroy($id)
    {
        $area = $this->areaService->getById($id);

        if (!$area) {
            return $this->error('Area not found', 404);
        }

        $deleted = $this->areaService->delete($area);

        if (!$deleted) {
            return $this->error('Cannot delete area because delivery employees are assigned to it', 400);
        }

        return $this->success('Area deleted successfully');
    }
}
