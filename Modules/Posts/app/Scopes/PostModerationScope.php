<?php

namespace Modules\Posts\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Modules\Posts\Enums\PostModerationStatus;

class PostModerationScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if ($user) {
            $builder->where(function (Builder $query) use ($user, $model) {
                $query->where($model->qualifyColumn('moderation_status'), PostModerationStatus::APPROVED)
                    ->orWhere($model->qualifyColumn('user_id'), $user->getAuthIdentifier());
            });
        } else {
            $builder->where($model->qualifyColumn('moderation_status'), PostModerationStatus::APPROVED);
        }
    }
}
