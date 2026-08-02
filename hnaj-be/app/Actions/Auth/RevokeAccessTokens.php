<?php

namespace App\Actions\Auth;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Thu hồi access token của user.
 */
class RevokeAccessTokens
{
    /**
     * Thu hồi token đang dùng cho request hiện tại; dùng cho logout.
     */
    public function handleCurrent(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }
}
