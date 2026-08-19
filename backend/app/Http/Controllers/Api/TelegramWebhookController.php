<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TelegramWebhookRequest;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;

class TelegramWebhookController extends Controller
{
    public function __invoke(TelegramWebhookRequest $request, TelegramService $telegram): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Telegram webhook processed.',
            'data' => $telegram->handleWebhook($request->validated()),
        ]);
    }
}
