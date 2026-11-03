<?php

namespace Modules\Messages\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Messages\Models\Conversation;
use Modules\Messages\Models\ConversationParticipant;

class StartOrGetConversation
{
    public function __invoke(User $initiator, User $recipient): Conversation
    {
        if ($initiator->id === $recipient->id) {
            abort(422, 'You cannot start a conversation with yourself.');
        }

        if ($initiator->hasBlockRelationshipWith($recipient)) {
            abort(403, 'You cannot start a conversation with this user.');
        }

        $pairKey = Conversation::pairKeyFor($initiator->id, $recipient->id);

        $conversation = Conversation::where('pair_key', $pairKey)->first();

        if ($conversation) {
            $initiatorParticipant = $conversation->participants()
                ->where('user_id', $initiator->id)
                ->first();

            if ($initiatorParticipant && $initiatorParticipant->hidden_at !== null) {
                $initiatorParticipant->update(['hidden_at' => null]);
            }

            return $conversation->load(['participants.user.profile.avatar']);
        }

        if (! $initiator->isMutualFollowWith($recipient)) {
            abort(403, 'You can only start conversations with mutual followers.');
        }

        return DB::transaction(function () use ($initiator, $recipient, $pairKey) {
            $conversation = Conversation::create([
                'pair_key' => $pairKey,
                'created_by' => $initiator->id,
                'last_message_at' => now(),
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $initiator->id,
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $recipient->id,
            ]);

            return $conversation->load(['participants.user.profile.avatar']);
        });
    }
}
