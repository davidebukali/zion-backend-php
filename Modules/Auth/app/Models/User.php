<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Interactions\Models\Repost;
use Modules\Notifications\Models\Notification;
use Modules\Posts\Models\Post;
use Modules\SocialGraph\Enums\FollowStatus;
use Modules\SocialGraph\Models\Follow;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUlids, Notifiable;

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    // protected static function newFactory(): UserFactory
    // {
    //     // return UserFactory::new();
    // }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function reposts()
    {
        return $this->hasMany(Repost::class);
    }

    public function followers(): HasMany
    {
        return $this->hasMany(Follow::class, 'following_id');
    }

    public function following(): HasMany
    {
        return $this->hasMany(Follow::class, 'follower_id');
    }

    public function isFollowing(User|string|null $user): bool
    {
        if (! $user) {
            return false;
        }

        $userId = $user instanceof User ? $user->id : $user;

        return $this->following()
            ->where('following_id', $userId)
            ->where('status', FollowStatus::ACCEPTED)
            ->exists();
    }

    public function isFollowedBy(User|string|null $user): bool
    {
        if (! $user) {
            return false;
        }

        $userId = $user instanceof User ? $user->id : $user;

        return $this->followers()
            ->where('follower_id', $userId)
            ->where('status', FollowStatus::ACCEPTED)
            ->exists();
    }

    public function scopeWithViewerFollowStatus($query, ?User $viewer)
    {
        if (! $viewer) {
            return $query;
        }

        return $query->withExists([
            'followers as is_following' => fn ($q) => $q
                ->where('follower_id', $viewer->id)
                ->where('status', FollowStatus::ACCEPTED),
        ]);
    }
}
