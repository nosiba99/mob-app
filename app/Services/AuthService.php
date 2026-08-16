<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    // تسجيل مستخدم جديد
    public function register(array $data)
    {
        return User::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null, // اختياري
            'password'   => Hash::make($data['password']),
            'is_active'  => 0,
        ]);
    }

    // تسجيل الدخول
    public function login(array $data)
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return null;
        }

        return [
            'user'  => $user,
            'token' => $user->createToken('auth_token')->plainTextToken,
        ];
    }
}
