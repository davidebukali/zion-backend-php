<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountDeletionRequest;
use App\Mail\AccountDeletionSupportNotificationMail;
use App\Mail\AccountDeletionUserConfirmationMail;
use App\Models\AccountDeletionRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Models\User;

class AccountDeletionController extends Controller
{
    /**
     * Show the public account deletion request form.
     */
    public function showForm(): View
    {
        return view('legal.delete-account');
    }

    /**
     * Handle the account deletion request submission.
     */
    public function submit(StoreAccountDeletionRequest $request): RedirectResponse
    {
        // Silently deflect bots if the hidden honeypot field is filled
        if ($request->filled('website_url')) {
            return redirect()
                ->route('account.delete.form')
                ->with('success', 'Your request has been received.');
        }

        $email = strtolower(trim($request->validated('email')));
        $scopeType = $request->validated('data_scope_type');
        $dataScope = $scopeType === 'all'
            ? ['all']
            : (array) $request->validated('data_scope_items', []);

        $user = User::where('email', $email)->first();

        $deletionRequest = AccountDeletionRequest::create([
            'email' => $email,
            'user_id' => $user?->id,
            'data_scope' => $dataScope,
            'reason' => $request->validated('reason'),
            'details' => $request->validated('details'),
            'status' => 'pending',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Send support notification email
        Mail::to('support@tsiyonapp.com')->queue(
            new AccountDeletionSupportNotificationMail($deletionRequest)
        );

        // Send confirmation receipt to user
        Mail::to($email)->queue(
            new AccountDeletionUserConfirmationMail($deletionRequest)
        );

        return redirect()
            ->route('account.delete.form')
            ->with('success', 'Your account deletion request has been submitted successfully. A confirmation email has been sent to your inbox.');
    }
}

