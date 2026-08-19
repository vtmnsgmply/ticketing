<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManagerTeamRequest;
use App\Services\ManagerDashboardService;
use Illuminate\Http\JsonResponse;

class ManagerTeamController extends Controller
{
    public function __invoke(ManagerTeamRequest $request, ManagerDashboardService $dashboard): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Manager team retrieved successfully.',
            'data' => ['team' => $dashboard->team($request->user())],
        ]);
    }
}
