<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerNotificationRequest;
use App\Services\CustomerNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerNotificationController extends Controller
{
    public function index(CustomerNotificationRequest $request, CustomerNotificationService $notifications): JsonResponse
    {
        $paginator = $notifications->paginate($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Notifications retrieved successfully.',
            'data' => [
                'notifications' => $paginator->items(),
                'unread_count' => $notifications->unreadCount($request->user()),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
        ]);
    }

    public function markRead(Request $request, CustomerNotificationService $notifications, int $notification): JsonResponse
    {
        $record = $notifications->markRead($request->user(), $notification);

        if ($record === null) {
            return response()->json(['success' => false, 'message' => 'Notification not found.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Notification marked as read.', 'data' => ['notification' => $record]]);
    }

    public function markAllRead(Request $request, CustomerNotificationService $notifications): JsonResponse
    {
        $notifications->markAllRead($request->user());

        return response()->json(['success' => true, 'message' => 'Notifications marked as read.', 'data' => []]);
    }
}
