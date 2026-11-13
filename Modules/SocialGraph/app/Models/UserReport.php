<?php

namespace Modules\SocialGraph\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Models\User;
use Modules\SocialGraph\Enums\UserReportReason;
use Modules\SocialGraph\Enums\UserReportStatus;

class UserReport extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'reporter_id',
        'reported_user_id',
        'reason',
        'description',
        'status',
    ];

    protected $casts = [
        'reason' => UserReportReason::class,
        'status' => UserReportStatus::class,
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }
}
