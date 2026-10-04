<?php

namespace Modules\Posts\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyModerationWebhookSignature
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $timestamp = (int) $request->header('X-Timestamp');
        $signature = (string) $request->header('X-Signature');

        // Replay prevention: reject timestamps older than 5 minutes or in future
        if (! $timestamp || ! $signature || abs(time() - $timestamp) > 300) {
            abort(401, 'Invalid timestamp');
        }

        $secret = config('services.moderation.webhook_secret');
        if (! $secret) {
            abort(500, 'Webhook secret not configured');
        }

        $expected = hash_hmac(
            'sha256',
            $timestamp.'.'.$request->getContent(),
            $secret
        );

        if (! hash_equals($expected, $signature)) {
            // Check previous secret for graceful key rotation
            $prevSecret = config('services.moderation.webhook_previous_secret');
            if (! $prevSecret || ! hash_equals(hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $prevSecret), $signature)) {
                abort(401, 'Invalid signature');
            }
        }

        return $next($request);
    }
}
