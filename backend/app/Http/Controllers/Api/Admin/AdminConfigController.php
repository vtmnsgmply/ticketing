<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Http\Requests\Admin\DepartmentRequest;
use App\Http\Requests\Admin\PriorityRequest;
use App\Http\Requests\Admin\SlaRequest;
use App\Http\Requests\Admin\StatusRequest;
use App\Models\Category;
use App\Models\Department;
use App\Models\Priority;
use App\Models\SlaRule;
use App\Services\AdminConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminConfigController extends Controller
{
    public function roles(AdminConfigService $config): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Roles retrieved successfully.', 'data' => ['roles' => $config->roles()]]);
    }

    public function departments(Request $request, AdminConfigService $config): JsonResponse
    {
        return $this->paginated($config->departments($request->query()), 'Departments retrieved successfully.', 'departments');
    }

    public function saveDepartment(DepartmentRequest $request, AdminConfigService $config, ?Department $department = null): JsonResponse
    {
        $saved = $config->saveDepartment($request->user(), $request->validated(), $request, $department);
        return response()->json(['success' => true, 'message' => 'Department saved successfully.', 'data' => ['department' => $saved]]);
    }

    public function departmentStatus(StatusRequest $request, AdminConfigService $config, Department $department): JsonResponse
    {
        $saved = $config->saveDepartment($request->user(), $department->only(['name', 'slug', 'description']) + ['is_active' => (bool) $request->validated('is_active')], $request, $department);
        return response()->json(['success' => true, 'message' => 'Department status updated successfully.', 'data' => ['department' => $saved]]);
    }

    public function categories(Request $request, AdminConfigService $config): JsonResponse
    {
        return $this->paginated($config->categories($request->query()), 'Categories retrieved successfully.', 'categories');
    }

    public function saveCategory(CategoryRequest $request, AdminConfigService $config, ?Category $category = null): JsonResponse
    {
        $saved = $config->saveCategory($request->user(), $request->validated(), $request, $category);
        return response()->json(['success' => true, 'message' => 'Category saved successfully.', 'data' => ['category' => $saved]]);
    }

    public function categoryStatus(StatusRequest $request, AdminConfigService $config, Category $category): JsonResponse
    {
        $saved = $config->saveCategory($request->user(), $category->only(['department_id', 'name', 'slug', 'description']) + ['is_active' => (bool) $request->validated('is_active')], $request, $category);
        return response()->json(['success' => true, 'message' => 'Category status updated successfully.', 'data' => ['category' => $saved]]);
    }

    public function priorities(AdminConfigService $config): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Priorities retrieved successfully.', 'data' => ['priorities' => $config->priorities()]]);
    }

    public function updatePriority(PriorityRequest $request, AdminConfigService $config, Priority $priority): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Priority updated successfully.', 'data' => ['priority' => $config->updatePriority($request->user(), $priority, $request->validated(), $request)]]);
    }

    public function priorityStatus(StatusRequest $request, AdminConfigService $config, Priority $priority): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Priority status updated successfully.', 'data' => ['priority' => $config->priorityStatus($request->user(), $priority, (bool) $request->validated('is_active'), $request)]]);
    }

    public function sla(AdminConfigService $config): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'SLA rules retrieved successfully.', 'data' => ['sla_rules' => $config->slaRules()]]);
    }

    public function updateSla(SlaRequest $request, AdminConfigService $config, SlaRule $slaRule): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'SLA rule updated successfully.', 'data' => ['sla_rule' => $config->updateSla($request->user(), $slaRule, $request->validated(), $request)]]);
    }

    private function paginated($paginator, string $message, string $key): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => [$key => $paginator->items(), 'pagination' => ['current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total(), 'last_page' => $paginator->lastPage()]]]);
    }
}
