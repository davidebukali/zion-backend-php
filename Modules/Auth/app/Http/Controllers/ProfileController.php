<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Auth\Actions\GetUserProfile;
use Modules\Auth\Actions\UpdateUserProfile;
use Modules\Auth\Http\Requests\UpdateProfileRequest;
use Modules\Auth\Models\User;
use Modules\Auth\Transformers\ProfileResource;

class ProfileController extends Controller
{
    /**
     * @group Profiles
     * 
     * Get User Profile
     * 
     * Retrieve the public profile details for a specific user.
     * 
     * @urlParam user string required The ULID or ID of the user. Example: 01j98z3q4x6y7w8v9u0t1s2r01
     * 
     * @response 200 {
     *   "data": {
     *     "id": "01j98z3q4x6y7w8v9u0t1s2r02",
     *     "user_id": "01j98z3q4x6y7w8v9u0t1s2r01",
     *     "username": "janedoe",
     *     "display_name": "Jane Doe",
     *     "bio": "Building scalable backend microservices.",
     *     "avatar_media_id": null,
     *     "avatar": null,
     *     "cover_media_id": null,
     *     "cover": null,
     *     "website": "https://janedoe.dev",
     *     "created_at": "2026-10-04T00:00:00.000000Z",
     *     "updated_at": "2026-10-04T00:00:00.000000Z"
     *   }
     * }
     * @response 404 scenario="User not found" {
     *   "message": "Resource not found."
     * }
     */
    public function show(User $user, GetUserProfile $action): ProfileResource
    {
        $profile = ($action)($user);

        return new ProfileResource($profile);
    }

    /**
     * @group Profiles
     * @authenticated
     * 
     * Update Profile
     * 
     * Update the authenticated user's profile details.
     * 
     * @bodyParam username string The unique handle for the user. Example: janedoe
     * @bodyParam display_name string The public display name. Example: Jane Doe
     * @bodyParam bio string A short biography (max 1000 chars). Example: Building scalable backend microservices.
     * @bodyParam avatar_media_id string The ULID of an uploaded avatar media asset. Example: 01j98z3q4x6y7w8v9u0t1s2r05
     * @bodyParam cover_media_id string The ULID of an uploaded cover media asset. Example: 01j98z3q4x6y7w8v9u0t1s2r06
     * @bodyParam website string A valid URL link. Example: https://janedoe.dev
     * 
     * @response 200 {
     *   "data": {
     *     "id": "01j98z3q4x6y7w8v9u0t1s2r02",
     *     "user_id": "01j98z3q4x6y7w8v9u0t1s2r01",
     *     "username": "janedoe",
     *     "display_name": "Jane Doe",
     *     "bio": "Building scalable backend microservices.",
     *     "avatar_media_id": "01j98z3q4x6y7w8v9u0t1s2r05",
     *     "avatar": {
     *       "id": "01j98z3q4x6y7w8v9u0t1s2r05",
     *       "url": "https://storage.example.com/avatars/01j98z3q4x6y7w8v9u0t1s2r05.jpg",
     *       "type": "image",
     *       "mime_type": "image/jpeg"
     *     },
     *     "cover_media_id": null,
     *     "cover": null,
     *     "website": "https://janedoe.dev",
     *     "created_at": "2026-10-04T00:00:00.000000Z",
     *     "updated_at": "2026-10-04T00:00:00.000000Z"
     *   }
     * }
     * @response 401 scenario="Unauthenticated" {
     *   "message": "Unauthenticated."
     * }
     * @response 422 scenario="Validation error" {
     *   "message": "The username has already been taken.",
     *   "errors": {
     *     "username": [
     *       "The username has already been taken."
     *     ]
     *   }
     * }
     */
    public function update(UpdateProfileRequest $request, UpdateUserProfile $action): ProfileResource
    {
        $profile = ($action)($request->user(), $request->validated());

        return new ProfileResource($profile);
    }
}
