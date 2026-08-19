<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TicketCatalogService;
use Illuminate\Http\JsonResponse;

class TicketCatalogController extends Controller
{
    public function __invoke(TicketCatalogService $catalog): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Ticket options retrieved successfully.',
            'data' => $catalog->options(),
        ]);
    }
}
