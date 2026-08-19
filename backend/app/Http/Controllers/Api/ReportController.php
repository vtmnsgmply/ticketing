<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReportFilterRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function overview(ReportFilterRequest $request, ReportService $reports): JsonResponse
    {
        try {
            $data = $reports->overview($request->user(), $request->validated());
        } catch (BusinessRuleException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 403);
        }

        return response()->json(['success' => true, 'message' => 'Report retrieved successfully.', 'data' => $data]);
    }
}
