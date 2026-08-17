<?php

namespace Modules\Interactions\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Interactions\Enums\ReportReason;
use Modules\Interactions\Enums\ReportStatus;

class PostReports extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'post_reports';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'reason',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'reason' => ReportReason::class,
            'status' => ReportStatus::class,
        ];
    }
}

