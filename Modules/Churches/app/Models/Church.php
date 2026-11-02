<?php

namespace Modules\Churches\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Auth\Models\Profile;
use Modules\Auth\Models\User;

class Church extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'location',
        'created_by_user_id',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class);
    }
}

