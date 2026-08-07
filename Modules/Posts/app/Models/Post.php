<?php

namespace Modules\Posts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Auth\Models\User;
// use Modules\Posts\Database\Factories\PostFactory;

class Post extends Model
{
    use HasFactory;
    use HasUlids;
    use SoftDeletes;

    protected $fillable = [
        'content',
        'visibility'
    ];

    // protected static function newFactory(): PostFactory
    // {
    //     // return PostFactory::new();
    // }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
