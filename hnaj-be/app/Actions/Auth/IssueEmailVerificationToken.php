<?php

namespace App\Actions\Auth;

use App\Mail\VerifyEmailMail;
use App\Models\User;
use App\Repositories\EmailVerificationTokenRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Phát hành token xác thực email và gửi mail.
 * Database chỉ lưu hash; token plaintext chỉ xuất hiện trong email.
 */
class IssueEmailVerificationToken
{
    public const EXPIRES_IN_HOURS = 24;

    public function __construct(
        private readonly EmailVerificationTokenRepository $tokens,
    ) {}

    public function handle(User $user): void
    {
        $this->tokens->invalidateActiveTokensFor($user);

        $plainToken = Str::random(64);

        $this->tokens->create(
            $user,
            hash('sha256', $plainToken),
            Carbon::now()->addHours(self::EXPIRES_IN_HOURS),
        );

        Mail::to($user->email)->send(new VerifyEmailMail(
            user: $user,
            verificationUrl: $this->buildVerificationUrl($plainToken),
            expiresInHours: self::EXPIRES_IN_HOURS,
        ));
    }

    private function buildVerificationUrl(string $plainToken): string
    {
        return config('app.frontend_url').'/verify-email?token='.urlencode($plainToken);
    }
}
