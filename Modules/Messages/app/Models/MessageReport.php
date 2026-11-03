<?php

namespace Modules\Messages\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;
use Modules\Interactions\Enums\ReportReason;
use Modules\Interactions\Enums\ReportStatus;

class MessageReport extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'reporter_id',
        'conversation_id',
        'message_id',
        'reason',
        'description',
        'status',
    ];

    protected $casts = [
        'reason' => ReportReason::class,
        'status' => ReportStatus::class,
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'message_id');
    }
}

