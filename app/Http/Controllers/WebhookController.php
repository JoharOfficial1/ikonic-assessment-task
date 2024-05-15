<?php

namespace App\Http\Controllers;

use App\Services\AffiliateService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Pass the necessary data to the process order method
     * 
     * @param  Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Get data from the request
        $data = $request->all();

        // Process the order using the OrderService
        $result = $this->orderService->processOrder($data);

        // Return a JSON response based on the result
        return response()->json(['message' => $result]);
    }
}
