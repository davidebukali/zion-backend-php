<?php

namespace Modules\SocialGraph\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Models\User;
use Modules\Posts\Models\Post;

class FeedItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'post_id',
        'published_at',
    ];

    protected $casts = [
        'user_id' => 'string',
        'post_id' => 'string',
        'published_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
