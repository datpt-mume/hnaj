<?php

namespace App\Actions\Auth;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Exceptions\AuthFlowException;
use App\Models\User;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use App\Services\Auth\GoogleOAuthClient;
use App\Services\Auth\UsernameGenerator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Validate OAuth state, fetch the Google profile, then create or link a user account.
 *
 * Returns a single-use exchange code so the bearer token never appears in redirect URLs,
 * browser history, or access logs.
 */
class HandleGoogleCallback
{
    private const EXCHANGE_TTL_SECONDS = 60;

    public function __construct(
        private readonly GoogleOAuthClient $google,
        private readonly UserRepository $users,
        private readonly RoleRepository $roles,
        private readonly UsernameGenerator $usernames,
    ) {}

    public function handle(string $code, string $state, ?string $flowCookie): string
    {
        if ($flowCookie === null || $flowCookie === '') {
            throw AuthFlowException::googleAuthFailed('The Google sign-in session has expired.');
        }

        $stateKey = RedirectToGoogle::cacheKey($state);

        // Pull immediately so an OAuth state cannot be reused.
        $statePayload = Cache::pull($stateKey);

        if (! is_array($statePayload)
            || ! isset($statePayload['flow_hash'])
            || ! is_string($statePayload['flow_hash'])
            || ! hash_equals($statePayload['flow_hash'], RedirectToGoogle::hashFlowCookie($flowCookie))) {
            throw AuthFlowException::googleAuthFailed('The Google sign-in session has expired.');
        }

        $profile = $this->google->fetchProfile($code);

        if (! $profile['email_verified']) {
            throw AuthFlowException::googleAuthFailed('This Google account has an unverified email address.');
        }

        $user = $this->resolveUser($profile);

        if (! $user->isActive()) {
            throw AuthFlowException::accountNotActive();
        }

        return $this->issueExchangeCode($user, $statePayload['flow_hash']);
    }

    /**
     * @param  array{google_id: string, email: string, name: string, avatar_url: ?string, email_verified: bool}  $profile
     */
    private function resolveUser(array $profile): User
    {
        $existingByGoogleId = $this->users->findByGoogleId($profile['google_id']);

        if ($existingByGoogleId !== null) {
            $this->ensureRegularUser($existingByGoogleId);

            return $this->users->update($existingByGoogleId, [
                'avatar_url' => $profile['avatar_url'],
            ]);
        }

        $existingByEmail = $this->users->findByEmail($profile['email']);

        // Link a matching local user rather than creating a duplicate account.
        if ($existingByEmail !== null) {
            $this->ensureRegularUser($existingByEmail);

            if ($existingByEmail->google_id !== null
                && $existingByEmail->google_id !== $profile['google_id']) {
                throw AuthFlowException::googleAuthFailed('This email address is already linked to another Google account.');
            }

            return DB::transaction(function () use ($existingByEmail, $profile): User {
                $this->users->update($existingByEmail, [
                    'google_id' => $profile['google_id'],
                    'avatar_url' => $profile['avatar_url'] ?? $existingByEmail->avatar_url,
                ]);

                if (! $existingByEmail->hasVerifiedEmailAddress()) {
                    $this->users->markEmailVerified($existingByEmail);
                }

                return $existingByEmail;
            });
        }

        return $this->createUser($profile);
    }

    /**
     * @param  array{google_id: string, email: string, name: string, avatar_url: ?string, email_verified: bool}  $profile
     */
    private function createUser(array $profile): User
    {
        $role = $this->roles->findByNameOrFail(RoleName::User);
        $maxRetries = 3;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            try {
                return DB::transaction(function () use ($profile, $role): User {
                    $user = $this->users->create([
                        'name' => $profile['name'],
                        'username' => $this->usernames->fromEmail($profile['email']),
                        'email' => $profile['email'],
                        // Google-only accounts receive an unguessable password that is never disclosed.
                        'password' => Str::random(64),
                        'status' => UserStatus::Active,
                        'google_id' => $profile['google_id'],
                        'avatar_url' => $profile['avatar_url'],
                    ]);

                    // Google has already verified the email address.
                    $this->users->markEmailVerified($user);
                    $this->users->assignRole($user, $role);

                    return $user->fresh(['roles']) ?? $user;
                });
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === $maxRetries) {
                    throw AuthFlowException::googleAuthFailed(
                        'Unable to complete Google sign-in. Please try again.',
                    );
                }

                if (! str_contains($exception->getMessage(), 'users_username_unique')) {
                    throw $exception;
                }
            }
        }

        // @codeCoverageIgnoreStart
        throw AuthFlowException::googleAuthFailed('Unable to complete Google sign-in. Please try again.');
        // @codeCoverageIgnoreEnd
    }

    private function issueExchangeCode(User $user, string $flowHash): string
    {
        $exchangeCode = Str::random(64);

        Cache::put(
            self::exchangeCacheKey($exchangeCode),
            [
                'user_id' => $user->id,
                'flow_hash' => $flowHash,
            ],
            self::EXCHANGE_TTL_SECONDS,
        );

        return $exchangeCode;
    }

    public static function exchangeCacheKey(string $exchangeCode): string
    {
        return 'auth:google:exchange:'.hash('sha256', $exchangeCode);
    }

    private function ensureRegularUser(User $user): void
    {
        if (! $user->hasRole(RoleName::User)) {
            throw AuthFlowException::forbiddenRole();
        }
    }
}
