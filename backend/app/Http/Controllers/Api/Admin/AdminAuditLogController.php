<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuditLogListRequest;
use App\Services\AdminAuditService;
use Illuminate\Http\JsonResponse;

class AdminAuditLogController extends Controller
{
    public function index(AuditLogListRequest $request, AdminAuditService $audit): JsonResponse
    {
        $paginator = $audit->paginate($request->validated());
        return response()->json(['success' => true, 'message' => 'Audit logs retrieved successfully.', 'data' => ['audit_logs' => $paginator->items(), 'pagination' => ['current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total(), 'last_page' => $paginator->lastPage()]]]);
    }

    public function show(AdminAuditService $audit, int $auditLog): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Audit log retrieved successfully.', 'data' => ['audit_log' => $audit->find($auditLog)]]);
    }

    public function actions(AdminAuditService $audit): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Audit actions retrieved successfully.', 'data' => ['actions' => $audit->actions()]]);
    }

    public function entityTypes(AdminAuditService $audit): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Audit entity types retrieved successfully.', 'data' => ['entity_types' => $audit->entityTypes()]]);
    }
}
