<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManagerDashboardRequest;
use App\Services\ManagerDashboardService;
use Illuminate\Http\JsonResponse;

class ManagerDashboardController extends Controller
{
    public function __invoke(ManagerDashboardRequest $request, ManagerDashboardService $dashboard): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Manager dashboard retrieved successfully.',
            'data' => $dashboard->dashboard($request->user()),
        ]);
    }
}
