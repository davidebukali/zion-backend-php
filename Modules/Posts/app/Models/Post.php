<?php

namespace Modules\Posts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Models\User;
use Modules\Posts\Enums\PostVisibility;
use Modules\Comments\Models\Comment;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Media\Models\Media;
use Modules\Interactions\Models\PostLike;

class Post extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'content',
        'visibility'
    ];

    protected function casts(): array
    {
        return [
            'visibility' => PostVisibility::class,
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
        return $this->hasMany(\Modules\Interactions\Models\Bookmark::class);
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
}
