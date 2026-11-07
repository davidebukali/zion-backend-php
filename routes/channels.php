<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Messages\Models\Conversation;

Broadcast::channel('users.{userId}', function (User $user, string $userId): bool {
    return (string) $user->id === (string) $userId;
}, ['guards' => ['sanctum', 'web']]);

Broadcast::channel('conversations.{conversationId}', function (User $user, string $conversationId): array|bool {
    $conversation = Conversation::find($conversationId);

    if (! $conversation) {
        return false;
    }

    if (! Gate::forUser($user)->allows('sendMessage', $conversation)) {
        return false;
    }

    return [
        'id' => (string) $user->id,
        'name' => $user->name,
        'username' => $user->profile?->username,
    ];
}, ['guards' => ['sanctum', 'web']]);

Broadcast::channel('online', function (User $user): array {
    return [
        'id' => (string) $user->id,
        'name' => $user->name,
        'username' => $user->profile?->username,
    ];
}, ['guards' => ['sanctum', 'web']]);
