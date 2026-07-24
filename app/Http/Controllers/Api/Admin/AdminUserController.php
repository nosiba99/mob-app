<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\UserService;
use App\Models\User;

class AdminUserController extends Controller
{
    public function __construct(private UserService $userService) {}

    // عرض كل المستخدمين
  
public function index()
{
    $users = User::select('id', 'first_name', 'last_name')
                 ->where('is_banned', false)
                 ->orderBy('id', 'desc')
                 ->paginate(10);

    return response()->json([
        'status'  => true,
        'message' => 'تم جلب المستخدمين العاديين',
        'data'    => $users
    ], 200);
}

    // عرض مستخدم واحد
 public function show($id)
{
    $user = User::find($id);

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => 'المستخدم غير موجود'
        ], 404);
    }

    return response()->json([
        'status'  => true,
        'message' => 'تم جلب بيانات المستخدم',
        'data'    => $user
    ], 200);
}

    // حظر مستخدم
    public function ban($id)
    {
        $done = $this->userService->banUser($id);

        if (!$done) {
            return response()->json([
                'status' => false,
                'message' => 'المستخدم غير موجود'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم حظر المستخدم بنجاح',
        ]);
    }

    // إلغاء الحظر
    public function unban($id)
    {
        $done = $this->userService->unbanUser($id);

        if (!$done) {
            return response()->json([
                'status' => false,
                'message' => 'المستخدم غير موجود'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم إلغاء حظر المستخدم بنجاح',
        ]);
    }
public function banned()
{
    $users = User::select('id', 'first_name', 'last_name')
                 ->where('is_banned', true)
                 ->paginate(10);

    return response()->json([
        'status'  => true,
        'message' => 'تم جلب المستخدمين المحظورين',
        'data'    => $users
    ], 200);
}



}
