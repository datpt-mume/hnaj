<?php

namespace Tests\Feature\Auth;

use App\Actions\Auth\IssueEmailVerificationToken;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Mail\VerifyEmailMail;
use App\Models\EmailVerificationToken;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class EmailVerificationTest extends AuthTestCase
{
    /**
     * Phát hành token thật và trả về token plaintext lấy từ email đã gửi.
     */
    private function issueTokenFor(User $user): string
    {
        Mail::fake();

        app(IssueEmailVerificationToken::class)->handle($user);

        $plainToken = null;

        Mail::assertSent(VerifyEmailMail::class, function (VerifyEmailMail $mail) use (&$plainToken): bool {
            $query = parse_url($mail->verificationUrl, PHP_URL_QUERY) ?: '';
            parse_str($query, $params);
            $plainToken = $params['token'] ?? null;

            return $plainToken !== null;
        });

        return (string) $plainToken;
    }

    private function unverifiedUser(): User
    {
        return $this->createUserWithRole(RoleName::User, ['email_verified_at' => null]);
    }

    public function test_valid_token_verifies_the_email_and_marks_token_used(): void
    {
        $user = $this->unverifiedUser();
        $token = $this->issueTokenFor($user);

        $this->postJson('/api/auth/email/verify', ['token' => $token])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['user' => ['email_verified' => true]],
            ]);

        $this->assertNotNull($user->refresh()->email_verified_at, 'Email phải được đánh dấu đã xác thực.');
        $this->assertNotNull(
            EmailVerificationToken::query()->value('used_at'),
            'Token phải được đánh dấu đã dùng.',
        );
    }

    public function test_token_cannot_be_reused(): void
    {
        $user = $this->unverifiedUser();
        $token = $this->issueTokenFor($user);

        $this->postJson('/api/auth/email/verify', ['token' => $token])->assertOk();

        $this->postJson('/api/auth/email/verify', ['token' => $token])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'code' => 'INVALID_VERIFICATION_TOKEN']);
    }

    public function test_valid_token_for_an_already_verified_account_returns_conflict_without_consuming_it(): void
    {
        $user = $this->unverifiedUser();
        $token = $this->issueTokenFor($user);
        $user->forceFill(['email_verified_at' => Carbon::now()])->save();

        $this->postJson('/api/auth/email/verify', ['token' => $token])
            ->assertConflict()
            ->assertJson([
                'success' => false,
                'code' => 'EMAIL_ALREADY_VERIFIED',
            ]);

        $this->assertNull(
            EmailVerificationToken::query()->value('used_at'),
            'Token phải còn chưa dùng khi account đã được xác thực bởi luồng khác.',
        );
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = $this->unverifiedUser();
        $token = $this->issueTokenFor($user);

        // Token có hiệu lực 24 giờ; dịch thời gian qua mốc đó.
        $this->travelTo(Carbon::now()->addHours(IssueEmailVerificationToken::EXPIRES_IN_HOURS + 1));

        $this->postJson('/api/auth/email/verify', ['token' => $token])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'code' => 'INVALID_VERIFICATION_TOKEN']);

        $this->assertNull($user->refresh()->email_verified_at, 'Token hết hạn không được xác thực email.');
    }

    public function test_unknown_token_is_rejected(): void
    {
        $this->postJson('/api/auth/email/verify', ['token' => str_repeat('a', 64)])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'code' => 'INVALID_VERIFICATION_TOKEN']);
    }

    public function test_verify_requires_a_token(): void
    {
        $this->postJson('/api/auth/email/verify', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }

    public function test_resend_invalidates_the_previous_token(): void
    {
        $user = $this->unverifiedUser();
        $firstToken = $this->issueTokenFor($user);

        Mail::fake();

        $this->postJson('/api/auth/email/resend', ['email' => $user->email])->assertOk();

        Mail::assertSent(VerifyEmailMail::class);

        $this->postJson('/api/auth/email/verify', ['token' => $firstToken])
            ->assertStatus(422)
            ->assertJson(['code' => 'INVALID_VERIFICATION_TOKEN']);

        $this->assertSame(
            2,
            EmailVerificationToken::query()->count(),
            'Token cũ được giữ lại nhưng đã vô hiệu hóa.',
        );
    }

    public function test_resend_does_not_reveal_whether_the_email_exists(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/email/resend', ['email' => 'khong-ton-tai@example.com'])
            ->assertOk()
            ->assertJson(['success' => true]);

        Mail::assertNothingSent();
    }

    public function test_resend_returns_neutral_success_for_an_already_verified_email(): void
    {
        Mail::fake();

        $user = $this->createUserWithRole(RoleName::User);

        $this->postJson('/api/auth/email/resend', ['email' => $user->email])
            ->assertOk()
            ->assertJson(['success' => true]);

        Mail::assertNothingSent();
    }

    public function test_resend_returns_neutral_success_for_a_suspended_account(): void
    {
        Mail::fake();

        $user = $this->createUserWithRole(RoleName::User, [
            'email_verified_at' => null,
            'status' => UserStatus::Suspended,
        ]);

        $this->postJson('/api/auth/email/resend', ['email' => $user->email])
            ->assertOk()
            ->assertJson(['success' => true]);

        Mail::assertNothingSent();
    }

    public function test_resend_only_sends_mail_for_active_unverified_accounts(): void
    {
        Mail::fake();

        $user = $this->unverifiedUser();

        $this->postJson('/api/auth/email/resend', ['email' => $user->email])
            ->assertOk()
            ->assertJson(['success' => true]);

        Mail::assertSent(VerifyEmailMail::class, 1);
    }
}
