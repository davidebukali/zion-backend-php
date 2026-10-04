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
     * Get the profile for a specific user.
     */
    public function show(User $user, GetUserProfile $action): ProfileResource
    {
        $profile = ($action)($user);

        return new ProfileResource($profile);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(UpdateProfileRequest $request, UpdateUserProfile $action): ProfileResource
    {
        $profile = ($action)($request->user(), $request->validated());

        return new ProfileResource($profile);
    }
}
