<?php

namespace Modules\Messages\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\User;
use Modules\Messages\Events\MessageSent;
use Modules\Messages\Models\Conversation;
use Modules\Messages\Models\Message;

class SendMessage
{
    public function __construct(
        private readonly AttachMediaToMessage $attachMediaToMessage
    ) {}

    public function __invoke(User $sender, Conversation $conversation, array $data): Message
    {
        if (Gate::forUser($sender)->denies('sendMessage', $conversation)) {
            abort(403, 'You cannot send messages in this conversation.');
        }

        $body = isset($data['body']) ? trim((string) $data['body']) : '';
        $mediaIds = $data['media_ids'] ?? [];

        if ($body === '' && empty($mediaIds)) {
            throw ValidationException::withMessages([
                'body' => ['A message must contain text or at least one image.'],
            ]);
        }

        if (! empty($data['reply_to_message_id'])) {
            $replyExists = Message::query()
                ->where('id', $data['reply_to_message_id'])
                ->where('conversation_id', $conversation->id)
                ->exists();

            if (! $replyExists) {
                throw ValidationException::withMessages([
                    'reply_to_message_id' => ['The message you are replying to does not exist in this conversation.'],
                ]);
            }
        }

        return DB::transaction(function () use ($sender, $conversation, $body, $mediaIds, $data) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'body' => $body !== '' ? $body : null,
                'reply_to_message_id' => $data['reply_to_message_id'] ?? null,
            ]);

            if (! empty($mediaIds)) {
                $this->attachMediaToMessage->handle($message, $mediaIds, $sender->id);
            }

            $conversation->update([
                'last_message_at' => $message->created_at,
            ]);

            $senderParticipant = $conversation->participants()
                ->where('user_id', $sender->id)
                ->first();

            $senderParticipant?->update([
                'last_read_message_id' => $message->id,
                'last_read_at' => $message->created_at,
            ]);

            $conversation->participants()
                ->where('user_id', '!=', $sender->id)
                ->whereNotNull('hidden_at')
                ->update(['hidden_at' => null]);

            $message->load([
                'sender.profile.avatar',
                'media',
                'replyTo.sender.profile',
                'reactions',
            ]);

            event(new MessageSent($message));

            return $message;
        });
    }
}
