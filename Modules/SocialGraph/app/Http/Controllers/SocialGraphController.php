<?php

namespace Modules\SocialGraph\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Auth\Models\User;
use Modules\SocialGraph\Actions\AcceptFollowRequest;
use Modules\SocialGraph\Actions\FollowUser;
use Modules\SocialGraph\Actions\RejectFollowRequest;
use Modules\SocialGraph\Actions\UnfollowUser;
use Modules\SocialGraph\Models\Follow;
use Modules\SocialGraph\Transformers\FollowResource;

class SocialGraphController extends Controller
{
    use RespondsWithApi;

    /**
     * Follow a user
     */
    public function follow(
        Request $request,
        User $user,
        FollowUser $followUser
    ) {
        $follow = $followUser(
            $request->user(),
            $user
        );

        return $this->success(
            new FollowResource($follow),
            'User followed successfully.',
            201
        );
    }

    /**
     * Unfollow a user
     */
    public function unfollow(
        Request $request,
        User $user,
        UnfollowUser $unfollowUser
    ) {
        $unfollowUser(
            $request->user(),
            $user
        );

        return $this->success(
            null,
            'User unfollowed successfully.'
        );
    }

    /**
     * Accept a follow request
     */
    public function acceptFollowRequest(
        Request $request,
        Follow $follow,
        AcceptFollowRequest $acceptFollowRequest
    ) {
        $follow = $acceptFollowRequest(
            $request->user(),
            $follow
        );

        return $this->success(
            new FollowResource($follow),
            'Follow request accepted successfully.'
        );
    }

    /**
     * Reject a follow request
     */
    public function rejectFollowRequest(
        Request $request,
        Follow $follow,
        RejectFollowRequest $rejectFollowRequest
    ) {
        $follow = $rejectFollowRequest(
            $request->user(),
            $follow
        );

        return $this->success(
            new FollowResource($follow),
            'Follow request rejected successfully.'
        );
    }

    /**
     * Get all followers for a user
     */
    public function getFollowers(
        Request $request,
        User $user
    ) {
        $perPage = (int) $request->query('per_page', 15);
        $followers = Follow::with('follower')
            ->where('following_id', $user->id)
            ->paginate($perPage);

        $paginated = FollowResource::collection($followers)->response()->getData(true);

        return $this->success(
            data: $paginated['data'] ?? [],
            message: 'Followers retrieved successfully.',
            status: 200,
            meta: $paginated['meta'] ?? []
        );
    }

    /**
     * Get all users following a user
     */
    public function getFollowing(
        Request $request,
        User $user
    ) {
        $perPage = (int) $request->query('per_page', 15);
        $following = Follow::with('following')
            ->where('follower_id', $user->id)
            ->paginate($perPage);

        $paginated = FollowResource::collection($following)->response()->getData(true);

        return $this->success(
            data: $paginated['data'] ?? [],
            message: 'Following retrieved successfully.',
            status: 200,
            meta: $paginated['meta'] ?? []
        );
    }
}
