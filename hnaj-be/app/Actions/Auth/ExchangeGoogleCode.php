<?php

namespace App\Actions\Auth;

use App\Enums\RoleName;
use App\Exceptions\AuthFlowException;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Cache;

/**
 * Bước 3 của luồng Google: frontend đổi exchange code một lần sang bearer token.
 */
class ExchangeGoogleCode
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly IssueAccessToken $issueAccessToken,
    ) {}

    /**
     * @return array{user: User, token: string}
     */
    public function handle(string $exchangeCode, ?string $flowCookie): array
    {
        if ($flowCookie === null || $flowCookie === '') {
            throw AuthFlowException::googleAuthFailed('This Google sign-in session is invalid or has expired.');
        }

        $payload = Cache::pull(HandleGoogleCallback::exchangeCacheKey($exchangeCode));

        if (! is_array($payload)
            || ! isset($payload['user_id'], $payload['flow_hash'])
            || ! is_numeric($payload['user_id'])
            || ! is_string($payload['flow_hash'])
            || ! hash_equals($payload['flow_hash'], RedirectToGoogle::hashFlowCookie($flowCookie))) {
            throw AuthFlowException::googleAuthFailed('This Google sign-in code is invalid or has expired.');
        }

        $user = $this->users->findById((int) $payload['user_id']);

        if ($user === null) {
            throw AuthFlowException::googleAuthFailed();
        }

        if (! $user->isActive()) {
            throw AuthFlowException::accountNotActive();
        }

        if (! $user->hasRole(RoleName::User)) {
            throw AuthFlowException::forbiddenRole();
        }

        return [
            'user' => $user,
            'token' => $this->issueAccessToken->handle($user),
        ];
    }
}
