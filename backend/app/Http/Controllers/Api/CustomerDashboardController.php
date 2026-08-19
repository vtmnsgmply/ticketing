<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerDashboardRequest;
use App\Services\CustomerDashboardService;
use Illuminate\Http\JsonResponse;

class CustomerDashboardController extends Controller
{
    public function __invoke(CustomerDashboardRequest $request, CustomerDashboardService $dashboard): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Customer dashboard retrieved successfully.',
            'data' => $dashboard->dashboard($request->user()),
        ]);
    }
}
