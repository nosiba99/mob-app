<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Models\Otp;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;   // ✔ مهم جدًا
use App\Mail\ResetPasswordOtpMail;     // ✔ مهم جدًا

class AuthController extends Controller
{
    public function __construct(private OtpService $otpService) {}

    // ─── تسجيل ───────────────────────────────────────
    public function register(RegisterRequest $request)
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                
                'first_name' => $request->first_name,
                'last_name'  => $request->last_name,
   
                'email'    => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $user->assignRole('user');

            return $user;
        });

        $this->otpService->generate($user, 'email_verification');

        return response()->json([
            'message' => 'تم التسجيل! تحقق من بريدك الإلكتروني.',
        ], 201);
    }

    // ─── التحقق من OTP ────────────────────────────────
  public function verifyOtp(Request $request)
{
    $request->validate([
        'otp' => ['required', 'digits:6'],
    ]);

    $otp = Otp::where('code', $request->otp)
              ->whereNull('used_at')
              ->where('expires_at', '>', now())
              ->latest()
              ->first();

    if (!$otp) {
        return response()->json(['message' => 'رمز غير صالح أو منتهي.'], 422);
    }

    $user = User::find($otp->user_id);

    if (!$user) {
        return response()->json(['message' => 'المستخدم غير موجود.'], 404);
    }

    // تعليم الرمز كمستخدم
    $otp->markAsUsed();

    // تفعيل الإيميل
    if (!$user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
    }

    // ⭐ تفعيل الحساب نفسه
    if ($user->is_active == 0) {
        $user->is_active = 1;
        $user->save();
    }

    // إنشاء توكن الدخول
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'تم التحقق وتفعيل الحساب بنجاح.',
        'token'   => $token,
        'role'    => $user->getRoleNames()->first(),
        'user'    => [
            'id'         => $user->id,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'email'      => $user->email,
        ],
    ]);
}


    // ─── تسجيل الدخول ────────────────────────────────
    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة.'], 401);
        }
if ($user->is_banned) {
    return response()->json([
        'status' => false,
        'message' => 'تم حظر حسابك من قبل الإدارة'
    ], 403);
}

        // استخدام الميثود الجاهزة في لارافيل لـ التحقق من التفعيل
        if (!$user->hasVerifiedEmail()) {
            $this->otpService->generate($user, 'email_verification');
            return response()->json(['message' => 'حسابك غير مفعّل. أُرسل رمز جديد لبريدك.'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'role'  => $user->getRoleNames()->first(),
            'user'  => [
                'id'    => $user->id,
                 'first_name' => $user->first_name,
                 'last_name'  => $user->last_name,
       
                'email' => $user->email,
            ],
        ]);
    }

    // ─── تسجيل الخروج ────────────────────────────────
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'تم تسجيل الخروج.']);
    }

    // ─── إعادة إرسال OTP ─────────────────────────────
    public function resendOtp(Request $request)
    {
        $request->validate(['email' => ['required', 'email', 'exists:users,email']]);

        $user = User::where('email', $request->email)->firstOrFail();

        $this->otpService->generate($user, 'email_verification');

        return response()->json(['message' => 'تم إرسال رمز جديد.']);
 
        }


  public function forgotPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
    ]);

    $user = User::where('email', $request->email)->first();

    $otp = rand(100000, 999999);

    Otp::create([
        'user_id' => $user->id,
        'code' => $otp,
        'type' => 'password_reset',
        'expires_at' => now()->addMinutes(10)
    ]);

    Mail::to($user->email)->send(new ResetPasswordOtpMail($otp));

    return response()->json(['message' => 'OTP sent to your email']);
}

public function verifyResetOtp(Request $request)
{
    $request->validate([
        'otp' => 'required'
    ]);

    $otpRecord = Otp::where('code', $request->otp)
        ->where('expires_at', '>', now())
        ->whereNull('used_at')
        ->first();

    if (!$otpRecord) {
        return response()->json(['message' => 'Invalid or expired OTP'], 400);
    }

    $otpRecord->update(['used_at' => now()]);

    // اعمل تسجيل دخول تلقائي
    $user = User::find($otpRecord->user_id);
    $token = $user->createToken('reset-password')->plainTextToken;

    return response()->json([
        'message' => 'OTP verified',
        'token' => $token
    ]);
}


public function resetPassword(Request $request)
{
    $request->validate([
        'password' => 'required|confirmed|min:6'
    ]);

    $user = $request->user(); // جاي من التوكن

    $user->update([
        'password' => bcrypt($request->password)
    ]);

    return response()->json(['message' => 'Password reset successfully']);
}




}