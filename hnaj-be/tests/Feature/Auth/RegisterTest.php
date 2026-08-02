<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Mail\VerifyEmailMail;
use App\Models\EmailVerificationToken;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class RegisterTest extends AuthTestCase
{
    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'username' => 'thu.nguyen',
            'full_name' => 'Nguyen Minh Thu',
            'email' => 'thu.nguyen@example.com',
            'password' => 'canhcam2026',
            'password_confirmation' => 'canhcam2026',
        ], $overrides);
    }

    public function test_registration_creates_unverified_user_with_user_role(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/register', $this->payload());

        $response
            ->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'username' => 'thu.nguyen',
                        'full_name' => 'Nguyen Minh Thu',
                        'email' => 'thu.nguyen@example.com',
                        'email_verified' => false,
                        'roles' => [RoleName::User->value],
                    ],
                ],
            ]);

        $this->assertArrayNotHasKey('token', $response->json('data'), 'Đăng ký chưa được phát hành token.');

        $user = User::query()->where('username', 'thu.nguyen')->firstOrFail();

        $this->assertNull($user->email_verified_at, 'Tài khoản mới chưa xác thực email.');
        $this->assertNotSame('canhcam2026', $user->password, 'Password phải được hash.');
        $this->assertDatabaseCount('email_verification_tokens', 1);

        Mail::assertSent(VerifyEmailMail::class);
    }

    public function test_registration_stores_only_hashed_verification_token(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/register', $this->payload())->assertCreated();

        $tokenHash = (string) EmailVerificationToken::query()->value('token_hash');

        $this->assertSame(64, strlen($tokenHash), 'Token phải lưu dưới dạng sha256 hash.');
    }

    public function test_registration_rejects_duplicate_username_and_email(): void
    {
        Mail::fake();

        $this->createUserWithRole(RoleName::User, [
            'username' => 'thu.nguyen',
            'email' => 'thu.nguyen@example.com',
        ]);

        $this->postJson('/api/auth/register', $this->payload())
            ->assertStatus(422)
            ->assertJson(['success' => false, 'code' => 'VALIDATION_ERROR'])
            ->assertJsonValidationErrors(['username', 'email']);
    }

    public function test_registration_rejects_invalid_username_format(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/register', $this->payload(['username' => 'Thu Nguyen!']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_registration_requires_confirmed_password_of_minimum_length(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/register', $this->payload([
            'password' => 'short',
            'password_confirmation' => 'mismatch',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_username_is_normalised_to_lowercase(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/register', $this->payload(['username' => 'Thu.Nguyen']))
            ->assertCreated();

        $this->assertDatabaseHas('users', ['username' => 'thu.nguyen']);
    }

    public function test_registration_rolls_back_user_role_and_token_when_mail_fails(): void
    {
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new RuntimeException('Mail transport failed.'));

        $this->postJson('/api/auth/register', $this->payload())
            ->assertStatus(500)
            ->assertJson(['success' => false, 'code' => 'INTERNAL_SERVER_ERROR']);

        $this->assertDatabaseMissing('users', ['email' => 'thu.nguyen@example.com']);
        $this->assertDatabaseCount('email_verification_tokens', 0);
        $this->assertDatabaseCount('user_roles', 0);
    }
}
