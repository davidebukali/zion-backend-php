<?php

namespace Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Notifications\Http\Requests\DeleteDeviceTokenRequest;
use Modules\Notifications\Http\Requests\StoreDeviceTokenRequest;
use Modules\Notifications\Models\DeviceToken;

class DeviceTokenController extends Controller
{
    /**
     * Register or update a device token for the authenticated user.
     */
    public function store(StoreDeviceTokenRequest $request): JsonResponse
    {
        $validated = $request->validated();

        DeviceToken::updateOrCreate(
            ['token' => $validated['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $validated['platform'] ?? null,
                'device_name' => $validated['device_name'] ?? null,
                'last_active_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Device token registered successfully.',
        ], 200);
    }

    /**
     * Remove a device token for the authenticated user.
     */
    public function destroy(DeleteDeviceTokenRequest $request): JsonResponse
    {
        $token = $request->validated('token');

        DeviceToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token', $token)
            ->delete();

        return response()->json([
            'message' => 'Device token removed successfully.',
        ], 200);
    }
}
