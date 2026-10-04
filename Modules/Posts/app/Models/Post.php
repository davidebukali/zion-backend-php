<?php

namespace Modules\Posts\Models;

use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Models\User;
use Modules\Comments\Models\Comment;
use Modules\Interactions\Models\PostLike;
use Modules\Media\Models\Media;
use Modules\Posts\Enums\PostModerationStatus;
use Modules\Posts\Enums\PostVisibility;
use Modules\Posts\Scopes\PostModerationScope;
use Modules\Interactions\Models\Bookmark;
use Modules\Interactions\Models\PostReports;

#[ScopedBy([PostModerationScope::class])]
class Post extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'content',
        'visibility',
        'moderation_status',
        'moderated_at',
        'moderated_by_type',
        'is_flagged_manually',
        'moderation_metadata',
    ];

    protected function casts(): array
    {
        return [
            'visibility' => PostVisibility::class,
            'moderation_status' => PostModerationStatus::class,
            'moderated_at' => 'datetime',
            'is_flagged_manually' => 'boolean',
            'moderation_metadata' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeFeed($query)
    {
        return $query
            ->with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->hasMany(PostLike::class);
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(
            Media::class,
            'post_media'
        )
        ->withPivot('sort_order')
        ->orderByPivot('sort_order');
    }

    public function moderationRequests(): HasMany
    {
        return $this->hasMany(ModerationRequest::class);
    }

    public function latestModerationRequest(): HasOne
    {
        return $this->hasOne(ModerationRequest::class)->latestOfMany();
    }

    public function reports(): HasMany
    {
        return $this->hasMany(PostReports::class, 'post_id');
    }
}
