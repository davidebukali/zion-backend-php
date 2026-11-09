<?php

namespace Modules\Notifications\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Notifications\Models\DeviceToken;

class ExpoPushService
{
    protected const EXPO_API_URL = 'https://exp.host/--/api/v2/push/send';

    /**
     * Send push notifications to all registered device tokens of a user.
     *
     * @param  array<string, mixed>  $data
     */
    public function sendToUser(
        string $userId,
        string $title,
        string $body,
        array $data = [],
        ?int $badge = 1
    ): int {
        $tokens = DeviceToken::query()
            ->where('user_id', $userId)
            ->pluck('token')
            ->all();

        if (empty($tokens)) {
            return 0;
        }

        $messages = [];
        foreach ($tokens as $token) {
            $messages[] = [
                'to' => $token,
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'badge' => $badge,
                'data' => ! empty($data) ? $data : (object) [],
            ];
        }

        $this->send($messages);

        return count($messages);
    }

    /**
     * Send an array of push notification messages to Expo.
     *
     * @param  list<array<string, mixed>>  $messages
     * @return list<array<string, mixed>>
     */
    public function send(array $messages): array
    {
        if (empty($messages)) {
            return [];
        }

        $accessToken = config('services.expo.access_token');
        $allTickets = [];

        // Expo limits batch size to 100 messages per request
        foreach (array_chunk($messages, 100) as $chunk) {
            $headers = [
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip, deflate',
                'Content-Type' => 'application/json',
            ];

            if ($accessToken) {
                $headers['Authorization'] = 'Bearer '.$accessToken;
            }

            try {
                $response = Http::connectTimeout(3)
                    ->timeout(5)
                    ->withHeaders($headers)
                    ->post(self::EXPO_API_URL, $chunk);

                if ($response->successful()) {
                    $tickets = $response->json('data') ?? [];
                    $this->handleTickets($chunk, $tickets);
                    $allTickets = array_merge($allTickets, $tickets);
                } else {
                    Log::warning('Expo push notification request failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Expo push notification exception', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $allTickets;
    }

    /**
     * Inspect returned tickets and prune invalid tokens (e.g. DeviceNotRegistered).
     *
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $tickets
     */
    protected function handleTickets(array $messages, array $tickets): void
    {
        foreach ($tickets as $index => $ticket) {
            $status = $ticket['status'] ?? null;
            $error = $ticket['details']['error'] ?? null;

            if ($status === 'error' && $error === 'DeviceNotRegistered') {
                $token = $messages[$index]['to'] ?? null;
                if ($token) {
                    DeviceToken::query()->where('token', $token)->delete();
                    Log::info('Pruned unregistered Expo device token', ['token' => $token]);
                }
            }
        }
    }
}
