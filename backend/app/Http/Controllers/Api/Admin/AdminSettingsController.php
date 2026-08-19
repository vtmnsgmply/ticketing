<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
use App\Http\Requests\Admin\TestEmailRequest;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;

class AdminSettingsController extends Controller
{
    public function system(SettingsService $settings): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'System settings retrieved successfully.', 'data' => ['settings' => $settings->system()]]);
    }

    public function updateSystem(SettingsRequest $request, SettingsService $settings): JsonResponse
    {
        try {
            $updated = $settings->update($request->user(), $request->validated(), $request);
        } catch (BusinessRuleException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 403);
        }

        return response()->json(['success' => true, 'message' => 'System settings updated successfully.', 'data' => ['settings' => $updated]]);
    }

    public function email(SettingsService $settings): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Email settings retrieved successfully.', 'data' => ['settings' => $settings->email()]]);
    }

    public function updateEmail(SettingsRequest $request, SettingsService $settings): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Email settings updated successfully.', 'data' => ['settings' => $settings->update($request->user(), $request->validated(), $request, true)]]);
    }

    public function testEmail(TestEmailRequest $request): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Test email accepted. Configure mail delivery for production sending.', 'data' => []]);
    }
}
