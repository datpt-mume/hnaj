<?php

namespace App\Actions\Auth;

use App\Enums\RoleName;
use App\Exceptions\AuthFlowException;
use App\Models\User;

/**
 * Sign in a regular user with username and password.
 *
 * @phpstan-type AuthResult array{user: User, token: string}
 */
class LoginUser
{
    public function __construct(
        private readonly AuthenticateCredentials $authenticate,
        private readonly IssueAccessToken $issueAccessToken,
    ) {}

    /**
     * @return array{user: User, token: string}
     */
    public function handle(string $username, string $password): array
    {
        $user = $this->authenticate->handle($username, $password);

        if (! $user->hasAnyRole(RoleName::User, RoleName::SubAdmin)) {
            throw AuthFlowException::forbiddenRole();
        }

        return [
            'user' => $user,
            'token' => $this->issueAccessToken->handle($user),
        ];
    }
}
