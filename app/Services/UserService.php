<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /*
    |--------------------------------------------------------------------------
    | دوال الأدمن (موجودة عندك سابقًا)
    |--------------------------------------------------------------------------
    */

    // جلب كل المستخدمين
  public function getAllUsers()
{
    return User::select('id', 'first_name', 'last_name')
        ->orderBy('id', 'desc')
        ->paginate(20);
}



    // جلب مستخدم واحد
    public function getUserById($id)
{
    return User::find($id);
}


    // حظر مستخدم
    public function banUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return false;
        }

        $user->update(['is_banned' => true]);

        return true;
    }

    // إلغاء الحظر
    public function unbanUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return false;
        }

        $user->update(['is_banned' => false]);

        return true;
    }
public function getBannedUsers()
{
    return User::where('is_banned', true)
        ->select('id', 'first_name', 'last_name')
        ->orderBy('id', 'desc')
        ->paginate(20);
}




}
