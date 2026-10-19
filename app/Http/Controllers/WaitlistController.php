<?php

namespace App\Http\Controllers;

use App\Jobs\SendWaitlistConfirmationJob;
use App\Models\WaitlistEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WaitlistController extends Controller
{
    /**
     * Store a newly created waitlist registration.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
            'source' => ['nullable', 'string', 'max:50'],
        ], [
            'email.required' => 'Please provide an email address.',
            'email.email' => 'Please enter a valid email address.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'validation_error',
                'message' => $validator->errors()->first('email'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = strtolower(trim((string) $request->input('email')));
        $source = (string) $request->input('source', 'hero');

        // Check for existing registration
        $existing = WaitlistEntry::where('email', $email)->first();

        if ($existing) {
            return response()->json([
                'status' => 'already_registered',
                'message' => "You're already on the waitlist! We'll reach out as soon as your invite is ready.",
            ], 200);
        }

        // Create new waitlist entry
        $entry = WaitlistEntry::create([
            'email' => $email,
            'source' => $source,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'status' => 'pending',
        ]);

        // Dispatch queued confirmation email
        SendWaitlistConfirmationJob::dispatch($entry);

        return response()->json([
            'status' => 'success',
            'message' => "You're on the list! Check your inbox for a confirmation email.",
        ], 201);
    }
}
