<?php

namespace Modules\Interactions\Actions;

use Modules\Auth\Models\User;
use Modules\Interactions\Events\PostReported;
use Modules\Interactions\Models\PostReports;
use Modules\Posts\Models\Post;

class ReportPost
{
    public function __invoke(
        User $user,
        Post $post,
        array $data
    ): PostReports {
        $report = PostReports::firstOrCreate(
            [
                'post_id' => $post->id,
                'user_id' => $user->id,
            ],
            [
                'reason' => $data['reason'],
                'description' => $data['description'] ?? null,
                'status' => 'pending',
            ]
        );

        if (! $report->wasRecentlyCreated) {
            $report->update([
                'reason' => $data['reason'],
                'description' => $data['description'] ?? null,
            ]);
        }

        event(new PostReported($post, $user, $data['reason'], $data['description'] ?? null));

        return $report;
    }
}
