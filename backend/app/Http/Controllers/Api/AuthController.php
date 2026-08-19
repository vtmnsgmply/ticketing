<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AccountDisabledException;
use App\Exceptions\InvalidCredentialsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AuthenticatedUserRequest;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function login(LoginRequest $request, AuthService $authService): JsonResponse
    {
        try {
            $data = $authService->login($request->validated(), $request);
        } catch (InvalidCredentialsException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 401);
        } catch (AccountDisabledException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => $data,
        ]);
    }

    public function me(AuthenticatedUserRequest $request, AuthService $authService): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Authenticated user retrieved successfully.',
            'data' => [
                'user' => $authService->profile($request->user()),
            ],
        ]);
    }

    public function logout(AuthenticatedUserRequest $request, AuthService $authService): JsonResponse
    {
        $authService->logout($request->user(), $request);

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
            'data' => [],
        ]);
    }
}
