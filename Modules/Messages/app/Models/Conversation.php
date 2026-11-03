<?php

namespace Modules\Messages\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Auth\Models\User;

class Conversation extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'pair_key',
        'created_by',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public static function pairKeyFor(string $userA, string $userB): string
    {
        $ids = [$userA, $userB];
        sort($ids, SORT_STRING);

        return implode(':', $ids);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class, 'conversation_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants', 'conversation_id', 'user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class, 'conversation_id')->latestOfMany();
    }

    public function participantFor(User|string $user): ?ConversationParticipant
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->participants->firstWhere('user_id', $userId);
    }

    public function otherParticipant(User|string $user): ?ConversationParticipant
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->participants->firstWhere('user_id', '!==', $userId);
    }

    public function otherUser(User|string $user): ?User
    {
        return $this->otherParticipant($user)?->user;
    }
}

