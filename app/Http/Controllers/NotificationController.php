<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    /**
     * Display a listing of user's notifications.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Notification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->has('type')) {
            $query->forType($request->type);
        }

        // Filter by read status
        if ($request->has('unread')) {
            if ($request->boolean('unread')) {
                $query->unread();
            } else {
                $query->read();
            }
        }

        $notifications = $query->paginate(15);

        return NotificationResource::collection($notifications);
    }

    /**
     * Get unread notification count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->unread()
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    /**
     * Display the specified notification.
     */
    public function show(Notification $notification): NotificationResource
    {
        $this->authorize('view', $notification);

        return new NotificationResource($notification);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(Notification $notification): NotificationResource
    {
        $this->authorize('update', $notification);

        $notification->markAsRead();

        return new NotificationResource($notification);
    }

    /**
     * Mark all user's notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'All notifications marked as read',
            'count' => $count
        ]);
    }

    /**
     * Remove the specified notification.
     */
    public function destroy(Notification $notification): JsonResponse
    {
        $this->authorize('delete', $notification);

        $notification->delete();

        return response()->json(null, 204);
    }
}
