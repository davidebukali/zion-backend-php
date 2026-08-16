<?php

namespace Modules\Interactions\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Interactions\Enums\ShareType;

class PostShare extends Model
{
    use HasFactory, HasUlids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'type' => ShareType::class,
        ];
    }
}

