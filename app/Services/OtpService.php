<?php
// app/Services/OtpService.php
namespace App\Services;

use App\Models\User;
use App\Models\Otp;
use App\Jobs\SendOtpEmailJob;

class OtpService
{
    public function generate(User $user, string $type = 'email_verification'): Otp
    {
        // إلغاء أي OTP قديم لنفس النوع
        $user->otps()
             ->where('type', $type)
             ->whereNull('used_at')
             ->delete();

        $otp = $user->otps()->create([
            'code'       => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'type'       => $type,
            'expires_at' => now()->addMinutes(10),
        ]);

        // 🔺 Queue — إرسال الإيميل بدون تأخير للـ request
        SendOtpEmailJob::dispatch($user, $otp->code);

        return $otp;
    }

    public function verify(User $user, string $code, string $type = 'email_verification'): bool
    {
        $otp = $user->otps()
                    ->where('code', $code)
                    ->where('type', $type)
                    ->whereNull('used_at')
                    ->latest()
                    ->first();

        if (!$otp || !$otp->isValid()) {
            return false;
        }

        $otp->markAsUsed();

        return true;
    }
}