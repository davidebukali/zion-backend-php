<?php

namespace Tests\Feature;

use App\Mail\BetaInviteMail;
use App\Models\WaitlistEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendBetaInvitesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.google_play.opt_in_url', 'https://play.google.com/apps/testing/com.tsiyon.app');
    }

    public function test_invite_queues_exact_batch_and_updates_invited_at(): void
    {
        Mail::fake();

        // Create 8 uninvited waitlist entries (mixture of gmail and non-gmail)
        for ($i = 1; $i <= 8; $i++) {
            WaitlistEntry::create([
                'email' => "user{$i}@gmail.com",
                'status' => 'pending',
                'source' => 'website',
                'created_at' => now()->subMinutes(10 - $i),
            ]);
        }

        // Run waitlist:invite with limit=5
        $this->artisan('waitlist:invite', ['limit' => 5])
            ->expectsOutputToContain('Queueing 5 beta invite email(s)...')
            ->expectsOutputToContain('Successfully queued 5 beta invite(s).')
            ->assertSuccessful();

        // Assert exactly 5 emails were queued
        Mail::assertQueued(BetaInviteMail::class, 5);

        // Verify database: 5 rows have invited_at set and status='invited'
        $this->assertSame(5, WaitlistEntry::whereNotNull('invited_at')->count());
        $this->assertSame(5, WaitlistEntry::where('status', 'invited')->count());

        // Remaining 3 rows are untouched
        $this->assertSame(3, WaitlistEntry::whereNull('invited_at')->count());

        // Subsequent run with limit=5 only invites the remaining 3 rows
        $this->artisan('waitlist:invite', ['limit' => 5])
            ->expectsOutputToContain('Queueing 3 beta invite email(s)...')
            ->expectsOutputToContain('Successfully queued 3 beta invite(s).')
            ->assertSuccessful();

        Mail::assertQueued(BetaInviteMail::class, 8);
        $this->assertSame(8, WaitlistEntry::whereNotNull('invited_at')->count());

        // Third run finds 0 uninvited entries
        $this->artisan('waitlist:invite', ['limit' => 5])
            ->expectsOutputToContain('No uninvited waitlist entries found')
            ->assertSuccessful();
    }

    public function test_dry_run_queues_zero_emails_and_leaves_database_untouched(): void
    {
        Mail::fake();

        WaitlistEntry::create([
            'email' => 'dryrun_tester@gmail.com',
            'status' => 'pending',
        ]);

        $this->artisan('waitlist:invite', ['limit' => 10, '--dry-run' => true])
            ->expectsOutputToContain('DRY RUN: No emails will be queued')
            ->expectsOutputToContain('Found 1 recipient(s) ready for invite.')
            ->assertSuccessful();

        Mail::assertNothingQueued();

        $this->assertDatabaseHas('waitlist_entries', [
            'email' => 'dryrun_tester@gmail.com',
            'invited_at' => null,
            'status' => 'pending',
        ]);
    }

    public function test_invite_with_specific_emails_filter(): void
    {
        Mail::fake();

        WaitlistEntry::create(['email' => 'alpha@gmail.com', 'status' => 'pending']);
        WaitlistEntry::create(['email' => 'beta@gmail.com', 'status' => 'pending']);
        WaitlistEntry::create(['email' => 'gamma@gmail.com', 'status' => 'pending']);

        $this->artisan('waitlist:invite', [
            '--emails' => ['alpha@gmail.com', 'gamma@gmail.com'],
        ])
            ->expectsOutputToContain('Successfully queued 2 beta invite(s).')
            ->assertSuccessful();

        Mail::assertQueued(BetaInviteMail::class, function (BetaInviteMail $mail) {
            return $mail->hasTo('alpha@gmail.com');
        });
        Mail::assertQueued(BetaInviteMail::class, function (BetaInviteMail $mail) {
            return $mail->hasTo('gamma@gmail.com');
        });
        Mail::assertNotQueued(BetaInviteMail::class, function (BetaInviteMail $mail) {
            return $mail->hasTo('beta@gmail.com');
        });

        $this->assertDatabaseHas('waitlist_entries', [
            'email' => 'alpha@gmail.com',
            'status' => 'invited',
        ]);
        $this->assertDatabaseHas('waitlist_entries', [
            'email' => 'beta@gmail.com',
            'invited_at' => null,
        ]);
    }

    public function test_invite_email_not_previously_on_waitlist_creates_record_and_sends(): void
    {
        Mail::fake();

        $this->assertDatabaseMissing('waitlist_entries', [
            'email' => 'external_user@gmail.com',
        ]);

        $this->artisan('waitlist:invite', [
            '--emails' => ['external_user@gmail.com'],
        ])
            ->expectsOutputToContain('Successfully queued 1 beta invite(s).')
            ->assertSuccessful();

        Mail::assertQueued(BetaInviteMail::class, function (BetaInviteMail $mail) {
            return $mail->hasTo('external_user@gmail.com');
        });

        $this->assertDatabaseHas('waitlist_entries', [
            'email' => 'external_user@gmail.com',
            'source' => 'manual_invite',
            'status' => 'invited',
        ]);
        $this->assertNotNull(WaitlistEntry::where('email', 'external_user@gmail.com')->value('invited_at'));
    }

    public function test_invite_already_invited_email_skips_unless_force_flag_passed(): void
    {
        Mail::fake();

        WaitlistEntry::create([
            'email' => 'veteran@gmail.com',
            'status' => 'invited',
            'invited_at' => now()->subDay(),
        ]);

        // Attempting to invite without --force skips with a notice
        $this->artisan('waitlist:invite', [
            '--emails' => ['veteran@gmail.com'],
        ])
            ->expectsOutputToContain('Skipping veteran@gmail.com: already invited')
            ->expectsOutputToContain('No uninvited waitlist entries found matching the criteria.')
            ->assertSuccessful();

        Mail::assertNothingQueued();

        // Attempting to invite with --force re-sends
        $this->artisan('waitlist:invite', [
            '--emails' => ['veteran@gmail.com'],
            '--force' => true,
        ])
            ->expectsOutputToContain('Successfully queued 1 beta invite(s).')
            ->assertSuccessful();

        Mail::assertQueued(BetaInviteMail::class, function (BetaInviteMail $mail) {
            return $mail->hasTo('veteran@gmail.com');
        });
    }

    public function test_missing_google_play_opt_in_url_fails(): void
    {
        Config::set('services.google_play.opt_in_url', null);

        $this->artisan('waitlist:invite')
            ->expectsOutputToContain('GOOGLE_PLAY_OPT_IN_URL is not configured')
            ->assertFailed();
    }

    public function test_export_waitlist_testers_command(): void
    {
        WaitlistEntry::create(['email' => 'android1@gmail.com', 'status' => 'pending', 'created_at' => now()->subMinutes(5)]);
        WaitlistEntry::create(['email' => 'android2@gmail.com', 'status' => 'pending', 'created_at' => now()->subMinutes(4)]);
        WaitlistEntry::create(['email' => 'ios_user@yahoo.com', 'status' => 'pending', 'created_at' => now()->subMinutes(3)]);
        WaitlistEntry::create(['email' => 'already_invited@gmail.com', 'status' => 'invited', 'invited_at' => now()]);

        $this->artisan('waitlist:export-testers', ['limit' => 10])
            ->expectsOutputToContain('android1@gmail.com, android2@gmail.com')
            ->assertSuccessful();

        $this->artisan('waitlist:export-testers', ['limit' => 10, '--lines' => true])
            ->expectsOutput('android1@gmail.com')
            ->expectsOutput('android2@gmail.com')
            ->assertSuccessful();
    }

    public function test_beta_invite_mail_serializes_and_renders_cleanly(): void
    {
        $mailable = new BetaInviteMail('https://play.google.com/apps/testing/com.tsiyon.app', 'Tester');

        // Test queue serialization
        $serialized = serialize($mailable);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(BetaInviteMail::class, $unserialized);
        $this->assertSame('https://play.google.com/apps/testing/com.tsiyon.app', $unserialized->optInUrl);
        $this->assertSame('Tester', $unserialized->recipientName);
        $this->assertSame('default', $unserialized->queue);

        // Test envelope subject
        $this->assertSame("You're invited: Early Access Beta for Tsiyon on Android", $mailable->envelope()->subject);

        // Test view rendering
        $mailable->assertSeeInHtml('Join the Android Beta & Install');
        $mailable->assertSeeInHtml('https://play.google.com/apps/testing/com.tsiyon.app');
        $mailable->assertSeeInHtml('Keep Installed for 14 Days');
    }
}
