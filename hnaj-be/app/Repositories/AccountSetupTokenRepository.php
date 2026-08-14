<?php

namespace App\Repositories;

use App\Models\AccountSetupToken;
use App\Models\User;
use Illuminate\Support\Carbon;

class AccountSetupTokenRepository
{
    /**
     * Vô hiệu hóa mọi token chưa dùng của user, gọi trước khi phát hành token mới.
     */
    public function invalidateActiveTokensFor(User $user): void
    {
        AccountSetupToken::query()
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => Carbon::now()]);
    }

    public function create(User $user, string $tokenHash, Carbon $expiresAt): AccountSetupToken
    {
        return AccountSetupToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Chỉ trả token chưa dùng và chưa hết hạn.
     */
    public function lockUsableByHash(string $tokenHash): ?AccountSetupToken
    {
        return AccountSetupToken::query()
            ->with('user')
            ->where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->where('expires_at', '>', Carbon::now())
            ->lockForUpdate()
            ->first();
    }

    public function markUsed(AccountSetupToken $token): void
    {
        $token->used_at = Carbon::now();
        $token->save();
    }
}
