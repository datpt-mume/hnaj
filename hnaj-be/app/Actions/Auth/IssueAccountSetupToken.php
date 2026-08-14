<?php

namespace App\Actions\Auth;

use App\Mail\AccountSetupMail;
use App\Models\User;
use App\Repositories\AccountSetupTokenRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Phát hành token setup tài khoản Sub-admin và gửi mail kích hoạt.
 * Database chỉ lưu hash; token plaintext chỉ xuất hiện trong email.
 */
class IssueAccountSetupToken
{
    public const EXPIRES_IN_HOURS = 24;

    public function __construct(
        private readonly AccountSetupTokenRepository $tokens,
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

        Mail::to($user->email)->send(new AccountSetupMail(
            user: $user,
            setupUrl: $this->buildSetupUrl($plainToken),
            expiresInHours: self::EXPIRES_IN_HOURS,
        ));
    }

    private function buildSetupUrl(string $plainToken): string
    {
        return config('app.frontend_url').'/setup-account?token='.urlencode($plainToken);
    }
}
