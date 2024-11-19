<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function notificationReadAll()
    {
        $user = Auth::user();
        if ($user->unreadNotifications->isNotEmpty()) {
            $user->unreadNotifications->markAsRead();
            return response()->json([
                'message' => 'All notifications marked as read.',
                'status' => true
            ]);
        }
        return response()->json([
            'message' => 'No unread notifications found.',
            'status' => false
        ]);
    }

    public function notifications()
    {
        $user = Auth::user();
        $notifications = $user->notifications;
        $notificationCount = $notifications->count();

        return response()->json([
            'success' => true,
            'count' => $notificationCount,
            'data' => $notifications,
        ]);
    }
    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Notification not found.',
        ], 404);
    }
    public function muteNotifications(Request $request)
    {
        try {
            $user = auth()->user();
            $user->update(['mute_notifications' => true]);
            return $this->sendResponse(null, 'Notifications muted successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to mute notifications.', ['error' => $e->getMessage()], 500);
        }
    }
    public function unmuteNotifications(Request $request)
    {
        try {
            $user = auth()->user();
            $user->update(['mute_notifications' => false]);
            return $this->sendResponse(null, 'Notifications unmuted successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to unmute notifications.', ['error' => $e->getMessage()], 500);
        }
    }
}
