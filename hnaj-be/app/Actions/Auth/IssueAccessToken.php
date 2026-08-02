<?php

namespace App\Actions\Auth;

use App\Models\User;

/**
 * Phát hành Sanctum personal access token cho client.
 *
 * Token không mang role dưới dạng ability: role có thể bị Admin thay đổi sau khi
 * token đã phát hành, nên authorization luôn đọc role từ database tại thời điểm
 * request. Tên token chỉ để phân biệt loại client khi cần thu hồi.
 */
class IssueAccessToken
{
    public function handle(User $user, string $tokenName = 'spa'): string
    {
        return $user->createToken($tokenName)->plainTextToken;
    }
}
