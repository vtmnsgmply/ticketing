<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\AdminAuditLogController;
use App\Http\Controllers\Api\Admin\AdminConfigController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminSettingsController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AdminModerationController;
use App\Http\Controllers\Api\CustomerDashboardController;
use App\Http\Controllers\Api\CustomerNotificationController;
use App\Http\Controllers\Api\CustomerProfileController;
use App\Http\Controllers\Api\StaffDashboardController;
use App\Http\Controllers\Api\StaffNotificationController;
use App\Http\Controllers\Api\StaffProfileController;
use App\Http\Controllers\Api\ManagerDashboardController;
use App\Http\Controllers\Api\ManagerNotificationController;
use App\Http\Controllers\Api\ManagerProfileController;
use App\Http\Controllers\Api\ManagerTeamController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TicketCatalogController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TelegramWebhookController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/telegram/webhook', TelegramWebhookController::class);

Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', UserController::class);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->whereNumber('notification');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::get('/notification-preferences', [NotificationController::class, 'preferences']);
    Route::put('/notification-preferences', [NotificationController::class, 'updatePreferences']);

    Route::get('/admin/auth-check', fn () => response()->json([
        'success' => true,
        'message' => 'Administrator access confirmed.',
        'data' => [],
    ]))->middleware('role:administrator');

    Route::middleware('role:customer')->prefix('customer')->group(function (): void {
        Route::get('/dashboard', CustomerDashboardController::class);
        Route::get('/profile', [CustomerProfileController::class, 'show']);
        Route::put('/profile', [CustomerProfileController::class, 'update']);
        Route::put('/password', [CustomerProfileController::class, 'password']);
        Route::get('/notifications', [CustomerNotificationController::class, 'index']);
        Route::patch('/notifications/{notification}/read', [CustomerNotificationController::class, 'markRead'])->whereNumber('notification');
        Route::post('/notifications/read-all', [CustomerNotificationController::class, 'markAllRead']);
    });

    Route::middleware('role:agent')->prefix('staff')->group(function (): void {
        Route::get('/dashboard', StaffDashboardController::class);
        Route::get('/profile', [StaffProfileController::class, 'show']);
        Route::put('/profile', [StaffProfileController::class, 'update']);
        Route::put('/password', [StaffProfileController::class, 'password']);
        Route::get('/notifications', [StaffNotificationController::class, 'index']);
        Route::patch('/notifications/{notification}/read', [StaffNotificationController::class, 'markRead'])->whereNumber('notification');
        Route::post('/notifications/read-all', [StaffNotificationController::class, 'markAllRead']);
    });

    Route::middleware('role:manager')->prefix('manager')->group(function (): void {
        Route::get('/dashboard', ManagerDashboardController::class);
        Route::get('/team', ManagerTeamController::class);
        Route::get('/reports/overview', [ReportController::class, 'overview']);
        Route::get('/profile', [ManagerProfileController::class, 'show']);
        Route::put('/profile', [ManagerProfileController::class, 'update']);
        Route::put('/password', [ManagerProfileController::class, 'password']);
        Route::get('/notifications', [ManagerNotificationController::class, 'index']);
        Route::patch('/notifications/{notification}/read', [ManagerNotificationController::class, 'markRead'])->whereNumber('notification');
        Route::post('/notifications/read-all', [ManagerNotificationController::class, 'markAllRead']);
        Route::get('/moderation/events', [AdminModerationController::class, 'events']);
        Route::get('/moderation/events/{event}', [AdminModerationController::class, 'event'])->whereNumber('event');
        Route::patch('/moderation/events/{event}/review', [AdminModerationController::class, 'review'])->whereNumber('event');
        Route::patch('/moderation/events/{event}/dismiss', [AdminModerationController::class, 'dismiss'])->whereNumber('event');
        Route::patch('/moderation/events/{event}/escalate', [AdminModerationController::class, 'escalate'])->whereNumber('event');
    });

    Route::middleware('role:administrator')->prefix('admin')->group(function (): void {
        Route::get('/dashboard', AdminDashboardController::class);
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::get('/users/{user}', [AdminUserController::class, 'show'])->whereNumber('user');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->whereNumber('user');
        Route::patch('/users/{user}/status', [AdminUserController::class, 'status'])->whereNumber('user');
        Route::patch('/users/{user}/role', [AdminUserController::class, 'role'])->whereNumber('user');
        Route::put('/users/{user}/departments', [AdminUserController::class, 'departments'])->whereNumber('user');

        Route::get('/roles', [AdminConfigController::class, 'roles']);
        Route::get('/departments', [AdminConfigController::class, 'departments']);
        Route::post('/departments', [AdminConfigController::class, 'saveDepartment']);
        Route::put('/departments/{department}', [AdminConfigController::class, 'saveDepartment'])->whereNumber('department');
        Route::patch('/departments/{department}/status', [AdminConfigController::class, 'departmentStatus'])->whereNumber('department');
        Route::get('/categories', [AdminConfigController::class, 'categories']);
        Route::post('/categories', [AdminConfigController::class, 'saveCategory']);
        Route::put('/categories/{category}', [AdminConfigController::class, 'saveCategory'])->whereNumber('category');
        Route::patch('/categories/{category}/status', [AdminConfigController::class, 'categoryStatus'])->whereNumber('category');
        Route::get('/priorities', [AdminConfigController::class, 'priorities']);
        Route::put('/priorities/{priority}', [AdminConfigController::class, 'updatePriority'])->whereNumber('priority');
        Route::patch('/priorities/{priority}/status', [AdminConfigController::class, 'priorityStatus'])->whereNumber('priority');
        Route::get('/sla', [AdminConfigController::class, 'sla']);
        Route::put('/sla/{slaRule}', [AdminConfigController::class, 'updateSla'])->whereNumber('slaRule');
        Route::get('/settings/system', [AdminSettingsController::class, 'system']);
        Route::put('/settings/system', [AdminSettingsController::class, 'updateSystem']);
        Route::get('/settings/email', [AdminSettingsController::class, 'email']);
        Route::put('/settings/email', [AdminSettingsController::class, 'updateEmail']);
        Route::post('/settings/email/test', [AdminSettingsController::class, 'testEmail']);
        Route::get('/reports/overview', [ReportController::class, 'overview']);
        Route::get('/audit-logs', [AdminAuditLogController::class, 'index']);
        Route::get('/audit-logs/actions', [AdminAuditLogController::class, 'actions']);
        Route::get('/audit-logs/entity-types', [AdminAuditLogController::class, 'entityTypes']);
        Route::get('/audit-logs/{auditLog}', [AdminAuditLogController::class, 'show'])->whereNumber('auditLog');
        Route::get('/moderation/settings', [AdminModerationController::class, 'settings']);
        Route::put('/moderation/settings', [AdminModerationController::class, 'updateSettings']);
        Route::get('/moderation/blocked-words', [AdminModerationController::class, 'blockedWords']);
        Route::post('/moderation/blocked-words', [AdminModerationController::class, 'storeBlockedWord']);
        Route::put('/moderation/blocked-words/{blockedWord}', [AdminModerationController::class, 'updateBlockedWord'])->whereNumber('blockedWord');
        Route::patch('/moderation/blocked-words/{blockedWord}/enable', [AdminModerationController::class, 'enableBlockedWord'])->whereNumber('blockedWord');
        Route::patch('/moderation/blocked-words/{blockedWord}/disable', [AdminModerationController::class, 'disableBlockedWord'])->whereNumber('blockedWord');
        Route::get('/moderation/blocked-words/{blockedWord}/variants', [AdminModerationController::class, 'variants'])->whereNumber('blockedWord');
        Route::post('/moderation/blocked-words/{blockedWord}/variants', [AdminModerationController::class, 'storeVariant'])->whereNumber('blockedWord');
        Route::put('/moderation/blocked-words/{blockedWord}/variants/{variant}', [AdminModerationController::class, 'updateVariant'])->whereNumber(['blockedWord', 'variant']);
        Route::delete('/moderation/blocked-words/{blockedWord}/variants/{variant}', [AdminModerationController::class, 'deleteVariant'])->whereNumber(['blockedWord', 'variant']);
        Route::get('/moderation/events', [AdminModerationController::class, 'events']);
        Route::get('/moderation/events/{event}', [AdminModerationController::class, 'event'])->whereNumber('event');
        Route::patch('/moderation/events/{event}/review', [AdminModerationController::class, 'review'])->whereNumber('event');
        Route::patch('/moderation/events/{event}/dismiss', [AdminModerationController::class, 'dismiss'])->whereNumber('event');
        Route::patch('/moderation/events/{event}/escalate', [AdminModerationController::class, 'escalate'])->whereNumber('event');
    });

    Route::get('/tickets', [TicketController::class, 'index']);
    Route::get('/ticket-options', TicketCatalogController::class);
    Route::post('/tickets', [TicketController::class, 'store']);
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->whereNumber('ticket');
    Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->whereNumber('ticket');
    Route::post('/tickets/{ticket}/reply', [TicketController::class, 'reply'])->whereNumber('ticket');
    Route::post('/tickets/{ticket}/internal-note', [TicketController::class, 'internalNote'])->whereNumber('ticket');
    Route::delete('/tickets/{ticket}/messages/{message}', [TicketController::class, 'deleteMessage'])->whereNumber(['ticket', 'message']);
    Route::post('/tickets/{ticket}/messages/{message}/reactions', [TicketController::class, 'reactToMessage'])->whereNumber(['ticket', 'message']);
    Route::post('/tickets/{ticket}/read', [TicketController::class, 'markRead'])->whereNumber('ticket');
    Route::post('/tickets/{ticket}/assign', [TicketController::class, 'assign'])->whereNumber('ticket');
    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'status'])->whereNumber('ticket');
    Route::patch('/tickets/{ticket}/priority', [TicketController::class, 'priority'])->whereNumber('ticket');
    Route::post('/tickets/{ticket}/reopen', [TicketController::class, 'reopen'])->whereNumber('ticket');
    Route::post('/tickets/{ticket}/close', [TicketController::class, 'close'])->whereNumber('ticket');
    Route::post('/tickets/{ticket}/cancel', [TicketController::class, 'cancel'])->whereNumber('ticket');
    Route::post('/tickets/{ticket}/attachments', [TicketController::class, 'uploadAttachment'])->whereNumber('ticket');
    Route::get('/tickets/{ticket}/attachments/{attachment}', [TicketController::class, 'downloadAttachment'])->whereNumber(['ticket', 'attachment']);
    Route::get('/tickets/{ticket}/attachments/{attachment}/thumbnail', [TicketController::class, 'thumbnailAttachment'])->whereNumber(['ticket', 'attachment']);
    Route::get('/tickets/{ticket}/activity', [TicketController::class, 'activity'])->whereNumber('ticket');
});
