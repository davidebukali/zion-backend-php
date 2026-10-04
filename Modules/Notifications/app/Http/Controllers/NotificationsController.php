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
     * @group Notifications
     * @authenticated
     * 
     * List Notifications
     * 
     * Retrieve a paginated list of notifications for the authenticated user with actor details.
     * 
     * @queryParam page integer The page number for pagination. Example: 1
     * @queryParam per_page integer Number of items per page. Example: 20
     * 
     * @response 200 {
     *   "data": [
     *     {
     *       "id": "01j98z3q4x6y7w8v9u0t1s2r3q",
     *       "user_id": "01j98z3q4x6y7w8v9u0t1s2r01",
     *       "actor_id": "01j98z3q4x6y7w8v9u0t1s2r02",
     *       "actor": {
     *         "id": "01j98z3q4x6y7w8v9u0t1s2r02",
     *         "name": "Jane Doe",
     *         "email": "jane@example.com",
     *         "profile": {
     *           "id": "01j98z3q4x6y7w8v9u0t1s2r03",
     *           "username": "janedoe",
     *           "bio": "Software Engineer",
     *           "website": "https://example.com",
     *           "location": "San Francisco, CA"
     *         }
     *       },
     *       "type": "post_liked",
     *       "notifiable_type": "post",
     *       "notifiable_id": "01j98z3q4x6y7w8v9u0t1s2r04",
     *       "data": {
     *         "post_id": "01j98z3q4x6y7w8v9u0t1s2r04"
     *       },
     *       "read_at": null,
     *       "created_at": "2026-10-04T00:00:00.000000Z",
     *       "updated_at": "2026-10-04T00:00:00.000000Z"
     *     }
     *   ],
     *   "links": {
     *     "first": "http://localhost:8000/api/v1/notifications?page=1",
     *     "last": "http://localhost:8000/api/v1/notifications?page=1",
     *     "prev": null,
     *     "next": null
     *   },
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 1,
     *     "path": "http://localhost:8000/api/v1/notifications",
     *     "per_page": 20,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
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
     * @group Notifications
     * @authenticated
     * 
     * Mark Notification as Read
     * 
     * Mark a specific unread notification belonging to the authenticated user as read.
     * 
     * @urlParam notification string required The ULID of the notification to mark as read. Example: 01j98z3q4x6y7w8v9u0t1s2r3q
     * 
     * @response 204 scenario="Notification marked as read successfully" {}
     * @response 403 scenario="Forbidden when notification belongs to another user" {
     *   "message": "This action is unauthorized."
     * }
     * @response 404 scenario="Notification not found" {
     *   "message": "Resource not found."
     * }
     */
    public function markAsRead(Notification $notification, MarkAsRead $action, Request $request): Response
    {
        $action->execute($notification, $request->user());

        return response()->noContent();
    }

    /**
     * @group Notifications
     * @authenticated
     * 
     * Mark All Notifications as Read
     * 
     * Mark all unread notifications for the authenticated user as read.
     * 
     * @response 204 scenario="All notifications marked as read successfully" {}
     */
    public function markAllAsRead(Request $request, MarkAllAsRead $action): Response
    {
        $action->execute($request->user());

        return response()->noContent();
    }
}
