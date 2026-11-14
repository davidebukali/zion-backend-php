<?php

namespace Tests\Feature;

use App\Http\Requests\StoreAccountDeletionRequest;
use App\Mail\AccountDeletionSupportNotificationMail;
use App\Mail\AccountDeletionUserConfirmationMail;
use App\Models\AccountDeletionRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    public function test_account_deletion_page_is_accessible(): void
    {
        $response = $this->get(route('account.delete.form'));

        $response->assertOk();
        $response->assertSee('Request Account &amp; Data Deletion', false);
        $response->assertSee('Full Account &amp; All Data', false);
    }

    public function test_footer_contains_account_deletion_link(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(route('account.delete.form'));
    }

    public function test_privacy_policy_contains_account_deletion_link(): void
    {
        $response = $this->get(route('privacy'));

        $response->assertOk();
        $response->assertSee(route('account.delete.form'));
    }

    public function test_validation_fails_without_required_fields(): void
    {
        $validator = Validator::make([], (new StoreAccountDeletionRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->messages());
        $this->assertArrayHasKey('data_scope_type', $validator->errors()->messages());
        $this->assertArrayHasKey('confirm_deletion', $validator->errors()->messages());
    }

    public function test_validation_requires_data_scope_items_when_custom(): void
    {
        $validator = Validator::make([
            'email' => 'user@example.com',
            'data_scope_type' => 'custom',
            'confirm_deletion' => '1',
        ], (new StoreAccountDeletionRequest)->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('data_scope_items', $validator->errors()->messages());
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $validator = Validator::make([
            'email' => 'user@example.com',
            'data_scope_type' => 'all',
            'confirm_deletion' => '1',
            'reason' => 'privacy_concerns',
            'details' => 'Please purge all my records.',
        ], (new StoreAccountDeletionRequest)->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_honeypot_deflects_bots(): void
    {
        Mail::fake();

        $response = $this->post(route('account.delete.submit'), [
            'email' => 'bot@example.com',
            'data_scope_type' => 'all',
            'confirm_deletion' => '1',
            'website_url' => 'https://spam.example.com',
        ]);

        $response->assertRedirect(route('account.delete.form'));
        Mail::assertNothingQueued();
    }

    public function test_mailables_have_correct_subjects_and_views(): void
    {
        $deletionRequest = new AccountDeletionRequest([
            'email' => 'user@example.com',
            'data_scope' => ['all'],
            'reason' => 'privacy_concerns',
            'details' => 'Test details',
            'status' => 'pending',
        ]);
        $deletionRequest->id = '01JCYTESTREQUEST1234567890';
        $deletionRequest->created_at = now();

        $supportMail = new AccountDeletionSupportNotificationMail($deletionRequest);
        $this->assertEquals('[Account Deletion Request] user@example.com', $supportMail->envelope()->subject);
        $this->assertEquals('emails.account_deletion_support', $supportMail->content()->view);

        $userMail = new AccountDeletionUserConfirmationMail($deletionRequest);
        $this->assertEquals('Tsiyon Account Deletion Request Received', $userMail->envelope()->subject);
        $this->assertEquals('emails.account_deletion_confirmation', $userMail->content()->view);
    }
}

