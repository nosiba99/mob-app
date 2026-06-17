<?php
// app/Mail/OtpMail.php
namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OtpMail extends Mailable
{
    public function __construct(
        public User $user,
        public string $code
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'رمز التحقق الخاص بك');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.otp',
            with: ['user' => $this->user, 'code' => $this->code]
        );
    }
}