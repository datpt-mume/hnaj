<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email chứa liên kết setup tài khoản Sub-admin dùng một lần.
 * Token plaintext chỉ tồn tại trong email, database chỉ lưu hash.
 */
class AccountSetupMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $setupUrl,
        public readonly int $expiresInHours,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kích hoạt tài khoản quản lý địa điểm HNAJ',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.account-setup',
            text: 'emails.auth.account-setup-text',
        );
    }
}
