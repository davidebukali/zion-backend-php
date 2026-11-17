<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaitlistEntry extends Model
{
    use HasFactory;
    use HasUlids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'waitlist_entries';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'source',
        'ip_address',
        'user_agent',
        'status',
        'invited_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'invited_at' => 'datetime',
        ];
    }

    /**
     * Scope a query to only include uninvited waitlist entries.
     */
    public function scopeUninvited(Builder $query): Builder
    {
        return $query->whereNull('invited_at');
    }

    /**
     * Scope a query to only include pending waitlist entries.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include invited waitlist entries.
     */
    public function scopeInvited(Builder $query): Builder
    {
        return $query->where('status', 'invited');
    }
}
