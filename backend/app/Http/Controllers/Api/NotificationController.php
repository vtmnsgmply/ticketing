<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationListRequest;
use App\Http\Requests\UpdateNotificationPreferencesRequest;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(NotificationListRequest $request, NotificationService $notifications): JsonResponse
    {
        $paginator = $notifications->paginate($request->user(), $request->validated());

        return response()->json(['success' => true, 'message' => 'Notifications retrieved successfully.', 'data' => ['notifications' => $paginator->items(), 'unread_count' => $notifications->unreadCount($request->user()), 'pagination' => ['current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total(), 'last_page' => $paginator->lastPage()]]]);
    }

    public function unreadCount(Request $request, NotificationService $notifications): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Unread notification count retrieved successfully.', 'data' => ['unread_count' => $notifications->unreadCount($request->user())]]);
    }

    public function markRead(Request $request, NotificationService $notifications, int $notification): JsonResponse
    {
        $marked = $notifications->markRead($request->user(), $notification);
        abort_if($marked === null, 404);

        return response()->json(['success' => true, 'message' => 'Notification marked as read.', 'data' => ['notification' => $marked]]);
    }

    public function markAllRead(Request $request, NotificationService $notifications): JsonResponse
    {
        $notifications->markAllRead($request->user());

        return response()->json(['success' => true, 'message' => 'Notifications marked as read.', 'data' => []]);
    }

    public function preferences(Request $request, NotificationService $notifications): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Notification preferences retrieved successfully.', 'data' => ['preferences' => $notifications->preferences($request->user())]]);
    }

    public function updatePreferences(UpdateNotificationPreferencesRequest $request, NotificationService $notifications): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Notification preferences updated successfully.', 'data' => ['preferences' => $notifications->updatePreferences($request->user(), $request->validated())]]);
    }
}
