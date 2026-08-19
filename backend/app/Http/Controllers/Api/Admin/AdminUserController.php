<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Http\Requests\Admin\StatusRequest;
use App\Http\Requests\Admin\UserStoreRequest;
use App\Http\Requests\Admin\UserDepartmentsRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Models\User;
use App\Services\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request, AdminUserService $users): JsonResponse
    {
        return $this->paginated($users->paginate($request->query()), 'Users retrieved successfully.', 'users');
    }

    public function store(UserStoreRequest $request, AdminUserService $users): JsonResponse
    {
        $user = $users->create($request->user(), $request->validated(), $request);
        return response()->json(['success' => true, 'message' => 'User created successfully.', 'data' => ['user' => $user]], 201);
    }

    public function show(AdminUserService $users, int $user): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'User retrieved successfully.', 'data' => ['user' => $users->find($user)]]);
    }

    public function update(UserUpdateRequest $request, AdminUserService $users, User $user): JsonResponse
    {
        try {
            $updated = $users->update($request->user(), $user, $request->validated(), $request);
        } catch (BusinessRuleException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 403);
        }
        return response()->json(['success' => true, 'message' => 'User updated successfully.', 'data' => ['user' => $updated]]);
    }

    public function status(StatusRequest $request, AdminUserService $users, User $user): JsonResponse
    {
        try {
            $updated = $users->status($request->user(), $user, (bool) $request->validated('is_active'), $request);
        } catch (BusinessRuleException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 403);
        }
        return response()->json(['success' => true, 'message' => 'User status updated successfully.', 'data' => ['user' => $updated]]);
    }

    public function role(RoleRequest $request, AdminUserService $users, User $user): JsonResponse
    {
        try {
            $updated = $users->role($request->user(), $user, (int) $request->validated('role_id'), $request);
        } catch (BusinessRuleException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 403);
        }
        return response()->json(['success' => true, 'message' => 'User role updated successfully.', 'data' => ['user' => $updated]]);
    }

    public function departments(UserDepartmentsRequest $request, AdminUserService $users, User $user): JsonResponse
    {
        $updated = $users->departments($request->user(), $user, $request->validated(), $request);

        return response()->json(['success' => true, 'message' => 'User departments updated successfully.', 'data' => ['user' => $updated]]);
    }

    private function paginated($paginator, string $message, string $key): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => [$key => $paginator->items(), 'pagination' => ['current_page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total(), 'last_page' => $paginator->lastPage()]]]);
    }
}
