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
     * @group Social Graph
     * @subgroup Follows
     * @authenticated
     * 
     * Follow User
     * 
     * Follow another user or send a follow request.
     * 
     * @urlParam user string required The ULID of the user to follow. Example: 01j7b9k5v6abcdef0123456789
     * 
     * @response 201 {
     *   "success": true,
     *   "message": "User followed successfully.",
     *   "data": {
     *     "id": 1,
     *     "follower_id": "01j7b9k5v6abcdef0123456789",
     *     "following_id": "01j7b9k5v6abcdef9876543210",
     *     "status": "accepted",
     *     "created_at": "2026-09-26T12:00:00.000000Z",
     *     "updated_at": "2026-09-26T12:00:00.000000Z"
     *   },
     *   "meta": null
     * }
     * @response 422 scenario="Self Follow" {
     *   "success": false,
     *   "message": "You cannot follow yourself.",
     *   "errors": null
     * }
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
     * @group Social Graph
     * @subgroup Follows
     * @authenticated
     * 
     * Unfollow User
     * 
     * Unfollow a previously followed user.
     * 
     * @urlParam user string required The ULID of the user to unfollow. Example: 01j7b9k5v6abcdef0123456789
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "User unfollowed successfully.",
     *   "data": null,
     *   "meta": null
     * }
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
     * @group Social Graph
     * @subgroup Follow Requests
     * @authenticated
     * 
     * Accept Follow Request
     * 
     * Accept a pending follow request received by the authenticated user.
     * 
     * @urlParam follow integer required The ID of the follow record. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Follow request accepted successfully.",
     *   "data": {
     *     "id": 1,
     *     "follower_id": "01j7b9k5v6abcdef0123456789",
     *     "following_id": "01j7b9k5v6abcdef9876543210",
     *     "status": "accepted",
     *     "created_at": "2026-09-26T12:00:00.000000Z",
     *     "updated_at": "2026-09-26T12:00:00.000000Z"
     *   },
     *   "meta": null
     * }
     * @response 403 scenario="Forbidden" {
     *   "success": false,
     *   "message": "This action is unauthorized.",
     *   "errors": null
     * }
     * @response 422 scenario="Not Pending" {
     *   "success": false,
     *   "message": "This follow request is not pending.",
     *   "errors": null
     * }
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
     * @group Social Graph
     * @subgroup Follow Requests
     * @authenticated
     * 
     * Reject Follow Request
     * 
     * Reject a pending follow request received by the authenticated user.
     * 
     * @urlParam follow integer required The ID of the follow record. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Follow request rejected successfully.",
     *   "data": {
     *     "id": 1,
     *     "follower_id": "01j7b9k5v6abcdef0123456789",
     *     "following_id": "01j7b9k5v6abcdef9876543210",
     *     "status": "rejected",
     *     "created_at": "2026-09-26T12:00:00.000000Z",
     *     "updated_at": "2026-09-26T12:00:00.000000Z"
     *   },
     *   "meta": null
     * }
     * @response 403 scenario="Forbidden" {
     *   "success": false,
     *   "message": "This action is unauthorized.",
     *   "errors": null
     * }
     * @response 422 scenario="Not Pending" {
     *   "success": false,
     *   "message": "This follow request is not pending.",
     *   "errors": null
     * }
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
     * @group Social Graph
     * @subgroup Follows
     * @authenticated
     * 
     * Get Followers
     * 
     * Retrieve a paginated list of all users following the specified user.
     * 
     * @urlParam user string required The ULID of the user. Example: 01j7b9k5v6abcdef0123456789
     * @queryParam per_page integer Items per page. Example: 15
     * @queryParam page integer Page number. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Followers retrieved successfully.",
     *   "data": [
     *     {
     *       "id": 1,
     *       "follower_id": "01j7b9k5v6abcdef0123456789",
     *       "following_id": "01j7b9k5v6abcdef9876543210",
     *       "status": "accepted",
     *       "follower": {
     *         "id": "01j7b9k5v6abcdef0123456789",
     *         "name": "Jane Doe",
     *         "email": "jane@example.com"
     *       },
     *       "created_at": "2026-09-26T12:00:00.000000Z",
     *       "updated_at": "2026-09-26T12:00:00.000000Z"
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 1,
     *     "per_page": 15,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
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
     * @group Social Graph
     * @subgroup Follows
     * @authenticated
     * 
     * Get Following
     * 
     * Retrieve a paginated list of all users followed by the specified user.
     * 
     * @urlParam user string required The ULID of the user. Example: 01j7b9k5v6abcdef0123456789
     * @queryParam per_page integer Items per page. Example: 15
     * @queryParam page integer Page number. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Following retrieved successfully.",
     *   "data": [
     *     {
     *       "id": 1,
     *       "follower_id": "01j7b9k5v6abcdef9876543210",
     *       "following_id": "01j7b9k5v6abcdef0123456789",
     *       "status": "accepted",
     *       "following": {
     *         "id": "01j7b9k5v6abcdef0123456789",
     *         "name": "Jane Doe",
     *         "email": "jane@example.com"
     *       },
     *       "created_at": "2026-09-26T12:00:00.000000Z",
     *       "updated_at": "2026-09-26T12:00:00.000000Z"
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 1,
     *     "per_page": 15,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
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
