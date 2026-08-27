<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Phase 5 Part 1 Task 14 — the in-app notification bell.
 *
 * Reads only the signed-in user's own notifications; there is no route that can
 * reach anyone else's, so no ownership check is needed beyond the relation.
 */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return view('notifications.index', [
            'notifications' => $request->user()->notifications()->paginate(30),
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /** Bell dropdown contents, fetched on open so the count is never stale. */
    public function recent(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $user->notifications()->limit(8)->get()->map(fn ($notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Notification',
                'body' => $notification->data['body'] ?? '',
                'url' => $notification->data['url'] ?? route('notifications.index'),
                'icon' => $notification->data['icon'] ?? 'bell',
                'read' => $notification->read_at !== null,
                'ago' => $notification->created_at->diffForHumans(),
            ]),
        ]);
    }

    public function markRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->whereKey($id)->first();

        if (! $notification) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Marked as read',
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'All marked as read', 'unread_count' => 0]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }
}
