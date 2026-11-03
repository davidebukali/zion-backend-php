<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Enums\AccountType;
use Modules\Churches\Models\Church;
use Modules\Media\Models\Media;

class Profile extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id',
        'username',
        'display_name',
        'bio',
        'account_type',
        'category',
        'location',
        'church_id',
        'avatar_media_id',
        'cover_media_id',
        'website',
        'is_private',
    ];

    protected $casts = [
        'account_type' => AccountType::class,
        'is_private' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function avatar(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'avatar_media_id');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }
}
