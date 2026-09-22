<?php

namespace Modules\Media\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Auth\Models\User;
use Modules\Media\Enums\MediaStatus;

class Media extends Model
{
    use HasFactory;
    use HasUlids;

    protected $table = 'media';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'user_id',
        'mediable_type',
        'mediable_id',
        'disk',
        'path',
        'type',
        'mime_type',
        'size',
        'width',
        'height',
        'duration',
        'checksum',
        'sort_order',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => MediaStatus::class,
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }
}
