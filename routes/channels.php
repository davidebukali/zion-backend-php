<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Messages\Models\Conversation;

Broadcast::channel('users.{userId}', function (User $user, string $userId): bool {
    return (string) $user->id === (string) $userId;
}, ['guards' => ['sanctum', 'web']]);

Broadcast::channel('conversations.{conversationId}', function (User $user, string $conversationId): bool {
    $conversation = Conversation::find($conversationId);

    if (! $conversation) {
        return false;
    }

    return Gate::forUser($user)->allows('sendMessage', $conversation);
}, ['guards' => ['sanctum', 'web']]);
