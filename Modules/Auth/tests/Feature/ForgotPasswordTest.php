<?php

namespace Modules\Auth\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Emails\PasswordResetCodeMail;
use Modules\Auth\Models\User;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'oldpassword123',
        ]);
    }

    public function test_forgot_password_fails_if_email_does_not_exist(): void
    {
        $response = $this->postJson(route('api.password.forgot'), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_sends_email_and_saves_hashed_code(): void
    {
        Mail::fake();

        $response = $this->postJson(route('api.password.forgot'), [
            'email' => $this->user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Password reset code has been sent to your email.',
            ]);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $this->user->email,
        ]);

        Mail::assertQueued(PasswordResetCodeMail::class, function ($mail) {
            return $mail->hasTo($this->user->email) && strlen($mail->code) === 6;
        });
    }

    public function test_verify_code_fails_with_invalid_code(): void
    {
        DB::table('password_reset_tokens')->insert([
            'email' => $this->user->email,
            'token' => Hash::make('123456'),
            'created_at' => now(),
        ]);

        $response = $this->postJson(route('api.password.verify'), [
            'email' => $this->user->email,
            'code' => '999999',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_verify_code_fails_when_expired_after_two_minutes(): void
    {
        DB::table('password_reset_tokens')->insert([
            'email' => $this->user->email,
            'token' => Hash::make('123456'),
            'created_at' => Carbon::now()->subMinutes(3),
        ]);

        $response = $this->postJson(route('api.password.verify'), [
            'email' => $this->user->email,
            'code' => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        // Check that expired token was deleted
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $this->user->email,
        ]);
    }

    public function test_verify_code_succeeds_and_caches_verified_session(): void
    {
        DB::table('password_reset_tokens')->insert([
            'email' => $this->user->email,
            'token' => Hash::make('123456'),
            'created_at' => now(),
        ]);

        $response = $this->postJson(route('api.password.verify'), [
            'email' => $this->user->email,
            'code' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Verification code confirmed. You may now reset your password.',
            ]);

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $this->user->email,
        ]);

        $this->assertTrue(Cache::has('password_reset_verified:' . $this->user->email));
    }

    public function test_reset_password_fails_if_code_not_verified(): void
    {
        $response = $this->postJson(route('api.password.reset'), [
            'email' => $this->user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_reset_password_fails_when_passwords_do_not_match(): void
    {
        Cache::put('password_reset_verified:' . $this->user->email, true, now()->addMinutes(5));

        $response = $this->postJson(route('api.password.reset'), [
            'email' => $this->user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_reset_password_succeeds_and_allows_login_with_new_password(): void
    {
        Cache::put('password_reset_verified:' . $this->user->email, true, now()->addMinutes(5));

        $response = $this->postJson(route('api.password.reset'), [
            'email' => $this->user->email,
            'password' => 'brandnewpassword123',
            'password_confirmation' => 'brandnewpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Password has been reset successfully.',
            ]);

        // Verify cache was consumed
        $this->assertFalse(Cache::has('password_reset_verified:' . $this->user->email));

        // Verify login works with new password
        $loginResponse = $this->postJson(route('api.login'), [
            'email' => $this->user->email,
            'password' => 'brandnewpassword123',
        ]);

        $loginResponse->assertStatus(200);
    }
}
