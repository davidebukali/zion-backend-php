<?php

namespace Modules\Comments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Media\Models\Media;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Modules\Posts\Models\Post;
use Modules\Auth\Models\User;
use Modules\Interactions\Models\CommentLike;

class Comment extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'parent_comment_id',
        'content',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_comment_id')->withTrashed();
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_comment_id');
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(
            Media::class,
            'comment_media'
        )
        ->withPivot('sort_order')
        ->orderByPivot('sort_order');
    }

    public function likes()
    {
        return $this->hasMany(CommentLike::class);
    }
}
