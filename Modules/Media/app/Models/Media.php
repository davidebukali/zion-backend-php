<?php

namespace Modules\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Media\Database\Factories\MediaFactory;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
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
