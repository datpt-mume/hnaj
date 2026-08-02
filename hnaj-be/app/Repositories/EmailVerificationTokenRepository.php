<?php

namespace App\Repositories;

use App\Models\EmailVerificationToken;
use App\Models\User;
use Illuminate\Support\Carbon;

class EmailVerificationTokenRepository
{
    /**
     * Vô hiệu hóa mọi token chưa dùng của user, gọi trước khi phát hành token mới.
     */
    public function invalidateActiveTokensFor(User $user): void
    {
        EmailVerificationToken::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => Carbon::now()]);
    }

    public function create(User $user, string $tokenHash, Carbon $expiresAt): EmailVerificationToken
    {
        return EmailVerificationToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Chỉ trả token chưa dùng và chưa hết hạn.
     */
    public function findUsableByHash(string $tokenHash): ?EmailVerificationToken
    {
        return EmailVerificationToken::query()
            ->with('user')
            ->where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }

    public function lockUsableByHash(string $tokenHash): ?EmailVerificationToken
    {
        return EmailVerificationToken::query()
            ->with('user')
            ->where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->where('expires_at', '>', Carbon::now())
            ->lockForUpdate()
            ->first();
    }

    public function markUsed(EmailVerificationToken $token): void
    {
        $token->used_at = Carbon::now();
        $token->save();
    }
}
