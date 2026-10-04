<?php

namespace Modules\Posts\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Interactions\Models\PostReports;
use Modules\Posts\Enums\ModerationRequestStatus;

class ModerationRequest extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'moderation_requests';

    protected $fillable = [
        'post_id',
        'report_id',
        'content_hash',
        'status',
        'attempts',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ModerationRequestStatus::class,
            'attempts' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class)->withoutGlobalScope(\Modules\Posts\Scopes\PostModerationScope::class);
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(PostReports::class, 'report_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
