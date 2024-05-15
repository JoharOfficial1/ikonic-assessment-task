<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Order;

class MerchantController extends Controller
{
    public function __construct(
        protected MerchantService $merchantService
    ) {}

    /**
     * Useful order statistics for the merchant API.
     * 
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request): JsonResponse
    {
        // Validate the request parameters (from and to dates)
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        // Extract the dates from the request
        $fromDate = Carbon::parse($request->input('from'));
        $toDate = Carbon::parse($request->input('to'))->endOfDay();

        // Retrieve orders within the specified date range
        $orders = Order::whereBetween('created_at', [$fromDate, $toDate])->get();

        // Calculate the total number of orders
        $orderCount = $orders->count();

        // Calculate the total revenue
        $revenue = $orders->sum('subtotal');
        
        $commissionRate = 0.1;

        // Filter orders with an affiliate
        $affiliateOrders = $orders->filter(function ($order) {
            return !is_null($order->affiliate_id);
        });

        // Calculate the total commission owed
        $commissionsOwed = $affiliateOrders->sum(function ($order) use ($commissionRate) {
            return $order->subtotal * $commissionRate;
        });

        // Return the results in JSON format
        return response()->json([
            'revenue' => $revenue,
            'count' => $orderCount,
            'commissions_owed' => round($commissionsOwed),
        ]);
    }
}