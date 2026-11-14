<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Modules\Auth\Models\User;
use Tests\TestCase;

class BetaPurgeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_user_is_persistent_helper_returns_true_when_flag_is_set(): void
    {
        $user = new User([
            'email' => 'tester@example.com',
            'is_persistent' => true,
        ]);

        $this->assertTrue($user->isPersistent());
    }

    public function test_user_is_persistent_helper_respects_protected_emails_config(): void
    {
        Config::set('beta.protected_emails', ['vip@specialchurch.org']);
        Config::set('beta.protected_domains', []);

        $user = new User([
            'email' => 'vip@specialchurch.org',
            'is_persistent' => false,
        ]);

        $this->assertTrue($user->isPersistent());
    }

    public function test_user_is_persistent_helper_respects_protected_domains_config(): void
    {
        Config::set('beta.protected_emails', []);
        Config::set('beta.protected_domains', ['tsiyonapp.com']);

        $teamUser = new User([
            'email' => 'dev@tsiyonapp.com',
            'is_persistent' => false,
        ]);

        $this->assertTrue($teamUser->isPersistent());
    }

    public function test_user_is_persistent_helper_returns_false_for_normal_tester(): void
    {
        Config::set('beta.protected_emails', ['admin@tsiyonapp.com']);
        Config::set('beta.protected_domains', ['tsiyonapp.com']);

        $testerUser = new User([
            'email' => 'general_tester@gmail.com',
            'is_persistent' => false,
        ]);

        $this->assertFalse($testerUser->isPersistent());
    }

    public function test_purge_posts_command_requires_at_least_one_filter_option(): void
    {
        $exitCode = Artisan::call('beta:purge-posts');

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Please specify at least one filter option', Artisan::output());
    }

    public function test_purge_posts_command_validates_before_date_format(): void
    {
        $exitCode = Artisan::call('beta:purge-posts', [
            '--before' => 'invalid-date-string',
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Invalid date format for --before', Artisan::output());
    }

    public function test_protect_command_fails_gracefully_when_user_does_not_exist(): void
    {
        $exitCode = Artisan::call('beta:protect', [
            'email' => 'nonexistent_account@example.com',
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('User not found with email', Artisan::output());
    }

    public function test_unprotect_command_fails_gracefully_when_user_does_not_exist(): void
    {
        $exitCode = Artisan::call('beta:unprotect', [
            'email' => 'nonexistent_account@example.com',
        ]);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('User not found with email', Artisan::output());
    }

    public function test_beta_config_values_are_loaded(): void
    {
        $emails = config('beta.protected_emails');
        $domains = config('beta.protected_domains');

        $this->assertIsArray($emails);
        $this->assertIsArray($domains);
        $this->assertContains('tsiyonapp.com', $domains);
    }

    public function test_new_user_gets_is_persistent_false_when_beta_mode_is_active(): void
    {
        Config::set('beta.mode', true);

        $user = User::create([
            'name' => 'Beta Tester',
            'email' => 'beta_signup_'.uniqid().'@example.com',
            'password' => 'secret1234',
        ]);

        $this->assertFalse($user->is_persistent);
    }

    public function test_new_user_gets_is_persistent_true_when_beta_mode_is_inactive(): void
    {
        Config::set('beta.mode', false);

        $user = User::create([
            'name' => 'Prod User',
            'email' => 'prod_signup_'.uniqid().'@example.com',
            'password' => 'secret1234',
        ]);

        $this->assertTrue($user->is_persistent);
    }

    public function test_beta_graduate_command_promotes_all_beta_users(): void
    {
        Config::set('beta.mode', true);

        $user = User::create([
            'name' => 'Graduate Tester',
            'email' => 'tester_to_graduate_'.uniqid().'@example.com',
            'password' => 'secret1234',
            'is_persistent' => false,
        ]);

        $this->assertFalse($user->fresh()->is_persistent);

        $exitCode = Artisan::call('beta:graduate', ['--force' => true]);

        $this->assertEquals(0, $exitCode);
        $this->assertTrue($user->fresh()->is_persistent);
    }
}
