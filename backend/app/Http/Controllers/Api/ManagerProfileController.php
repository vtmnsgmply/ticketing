<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeManagerPasswordRequest;
use App\Http\Requests\UpdateManagerProfileRequest;
use App\Services\AuthService;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManagerProfileController extends Controller
{
    public function show(Request $request, AuthService $auth): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Manager profile retrieved successfully.',
            'data' => ['user' => $auth->profile($request->user())],
        ]);
    }

    public function update(UpdateManagerProfileRequest $request, ProfileService $profiles, AuthService $auth): JsonResponse
    {
        $user = $profiles->update($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => ['user' => $auth->profile($user)],
        ]);
    }

    public function password(ChangeManagerPasswordRequest $request, ProfileService $profiles): JsonResponse
    {
        try {
            $profiles->changePassword($request->user(), $request->validated());
        } catch (BusinessRuleException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 403);
        }

        return response()->json(['success' => true, 'message' => 'Password changed successfully.', 'data' => []]);
    }
}
