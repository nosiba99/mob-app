<?php
// app/Jobs/SendOtpEmailJob.php
namespace App\Jobs;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOtpEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3; // يعيد المحاولة 3 مرات لو فشل

    public function __construct(
        private User $user,
        private string $code
    ) {}

    public function handle(): void
    {
        Mail::to($this->user->email)
            ->send(new OtpMail($this->user, $this->code));
    }
}