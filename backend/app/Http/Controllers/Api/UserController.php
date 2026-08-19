<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthenticatedUserRequest;
use App\Services\CurrentUserService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __invoke(AuthenticatedUserRequest $request, CurrentUserService $currentUserService): JsonResponse
    {
        $user = $currentUserService->getCurrentUser((int) $request->user()->getAuthIdentifier());

        return response()->json([
            'success' => true,
            'message' => 'Authenticated user retrieved successfully.',
            'data' => [
                'user' => $user->safeProfile(),
            ],
        ]);
    }
}
