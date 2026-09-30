<?php

namespace Modules\Notifications\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Modules\Notifications\Actions\MarkAllAsRead;
use Modules\Notifications\Actions\MarkAsRead;
use Modules\Notifications\Models\Notification;
use Modules\Notifications\Transformers\NotificationResource;

class NotificationsController extends Controller
{
    /**
     * Get paginated notifications for the authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return NotificationResource::collection(
            $request->user()
                ->notifications()
                ->with('actor')
                ->latest()
                ->paginate(20)
        );
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Notification $notification, MarkAsRead $action, Request $request): Response
    {
        $action->execute($notification, $request->user());

        return response()->noContent();
    }

    /**
     * Mark all unread notifications as read for the authenticated user.
     */
    public function markAllAsRead(Request $request, MarkAllAsRead $action): Response
    {
        $action->execute($request->user());

        return response()->noContent();
    }
}
