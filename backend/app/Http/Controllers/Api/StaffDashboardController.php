<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StaffDashboardRequest;
use App\Services\StaffDashboardService;
use Illuminate\Http\JsonResponse;

class StaffDashboardController extends Controller
{
    public function __invoke(StaffDashboardRequest $request, StaffDashboardService $dashboard): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Staff dashboard retrieved successfully.',
            'data' => $dashboard->dashboard($request->user()),
        ]);
    }
}
