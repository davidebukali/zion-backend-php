<?php

namespace Modules\SocialGraph\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Auth\Models\User;
use Modules\SocialGraph\Enums\FollowStatus;

class Follow extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'follower_id',
        'following_id',
        'status',
    ];

    protected $casts = [
        'follower_id' => 'string',
        'following_id' => 'string',
        'status' => FollowStatus::class,
    ];

    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    public function following()
    {
        return $this->belongsTo(User::class, 'following_id');
    }
}
