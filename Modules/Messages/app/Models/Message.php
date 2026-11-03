<?php

namespace Modules\Messages\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Auth\Models\User;
use Modules\Media\Models\Media;

class Message extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'reply_to_message_id',
        'edited_at',
        'deleted_for_everyone_at',
    ];

    protected $casts = [
        'edited_at' => 'datetime',
        'deleted_for_everyone_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_message_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'reply_to_message_id');
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'message_media', 'message_id', 'media_id')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class, 'message_id');
    }

    public function userDeletions(): HasMany
    {
        return $this->hasMany(MessageUserDeletion::class, 'message_id');
    }

    public function scopeVisibleTo(Builder $query, User $user, ?DateTimeInterface $clearedAt = null): Builder
    {
        return $query
            ->when($clearedAt !== null, function (Builder $q) use ($clearedAt) {
                $q->where('messages.created_at', '>', $clearedAt);
            })
            ->whereDoesntHave('userDeletions', function (Builder $q) use ($user) {
                $q->where('user_id', $user->id);
            });
    }

    public function isDeletedForEveryone(): bool
    {
        return $this->deleted_for_everyone_at !== null;
    }

    public function isEdited(): bool
    {
        return $this->edited_at !== null;
    }
}

