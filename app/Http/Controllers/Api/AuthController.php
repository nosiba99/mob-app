<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\AuthService;
use App\Services\OtpService;
use App\Models\User;
use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordOtpMail;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private OtpService $otpService
    ) {}

    // ─── تسجيل ───────────────────────────────────────
    public function register(RegisterRequest $request)
    {
        $this->authService->register($request->validated());

        return response()->json([
            'status'  => true,
            'message' => 'تم التسجيل! تحقق من بريدك الإلكتروني.',
        ], 201);
    }

    // ─── التحقق من OTP ────────────────────────────────
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp'   => 'required|digits:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$this->otpService->verify($user, $request->otp, 'email_verification')) {
            return response()->json([
                'status'  => false,
                'message' => 'رمز غير صالح أو منتهي.'
            ], 422);
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        if ($user->is_active == 0) {
            $user->update(['is_active' => 1]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'تم التحقق وتفعيل الحساب بنجاح',
            'token'   => $token,
            'user'    => [
                'id'         => $user->id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'email'      => $user->email,
                'role'       => $user->getRoleNames()->first(),
            ],
        ]);
    }

    // ─── تسجيل الدخول ────────────────────────────────
    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated());

        if (!$result) {
            return response()->json([
                'status'  => false,
                'message' => 'بيانات الدخول غير صحيحة.'
            ], 401);
        }

        $user = $result['user'];

        if ($user->is_banned) {
            return response()->json([
                'status'  => false,
                'message' => 'تم حظر حسابك من قبل الإدارة'
            ], 403);
        }

        if (!$user->hasVerifiedEmail()) {
            $this->otpService->generate($user, 'email_verification');
            return response()->json([
                'status'  => false,
                'message' => 'حسابك غير مفعّل. أُرسل رمز جديد لبريدك.'
            ], 403);
        }

        return response()->json([
            'status'  => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'token'   => $result['token'],
            'user'    => [
                'id'         => $user->id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'email'      => $user->email,
                'role'       => $user->getRoleNames()->first(),
            ],
        ]);
    }

    // ─── تسجيل الخروج ────────────────────────────────
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'تم تسجيل الخروج.'
        ]);
    }

    // ─── إعادة إرسال OTP ─────────────────────────────
    public function resendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();
        $this->otpService->generate($user, 'email_verification');

        return response()->json([
            'status'  => true,
            'message' => 'تم إرسال رمز جديد.'
        ]);
    }

    // ─── نسيان كلمة المرور ───────────────────────────
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();
        $otp  = rand(100000, 999999);

        Otp::create([
            'user_id'    => $user->id,
            'code'       => $otp,
            'type'       => 'password_reset',
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($user->email)->send(new ResetPasswordOtpMail($otp));

        return response()->json([
            'status'  => true,
            'message' => 'تم إرسال رمز إعادة التعيين إلى بريدك.'
        ]);
    }

    // ─── التحقق من OTP لإعادة التعيين ────────────────
    public function verifyResetOtp(Request $request)
    {
        $request->validate(['otp' => 'required']);

        $otpRecord = Otp::where('code', $request->otp)
            ->where('type', 'password_reset')
            ->where('expires_at', '>', now())
            ->whereNull('used_at')
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'status'  => false,
                'message' => 'رمز غير صالح أو منتهي.'
            ], 400);
        }

        $otpRecord->update(['used_at' => now()]);

        $user  = User::find($otpRecord->user_id);
        $token = $user->createToken('reset-password')->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'تم التحقق من الرمز',
            'token'   => $token,
        ]);
    }

    // ─── إعادة تعيين كلمة المرور ─────────────────────
  

public function resetPassword(ResetPasswordRequest $request)
{
    $user = $request->user();

    $user->update([
        'password' => bcrypt($request->password),
    ]);

    return response()->json([
        'status'  => true,
        'message' => 'تم تغيير كلمة المرور بنجاح'
    ]);
}

}
