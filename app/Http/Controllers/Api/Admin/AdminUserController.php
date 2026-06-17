<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    // عرض كل المستخدمين
    public function index()
    {
        $users = User::select('id', 'name', 'email','created_at')
                     ->orderBy('id', 'desc')
                     ->paginate(20);

        return response()->json([
            'status' => true,
            'message' => 'تم جلب المستخدمين بنجاح',
            'data' => $users,
        ]);
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
        'status' => true,
        'message' => 'تم جلب بيانات المستخدم بنجاح',
        'data' => $user
    ]);
}


    // حظر مستخدم
    public function ban($id)
    {
        $user = User::findOrFail($id);
        $user->is_banned = true;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'تم حظر المستخدم بنجاح',
        ]);
    }

    // إلغاء الحظر
    public function unban($id)
    {
        $user = User::findOrFail($id);
        $user->is_banned = false;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'تم إلغاء حظر المستخدم بنجاح',
        ]);
    }
}
