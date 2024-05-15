<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\ApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class PayoutOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Order $order
    ) {}

    /**
     * Use the API service to send a payout of the correct amount.
     * Note: The order status must be paid if the payout is successful, or remain unpaid in the event of an exception.
     *
     * @return void
     */
    public function handle(ApiService $apiService)
    {
        try {
            $payoutResult = $apiService->sendPayout($this->order->affiliate->user->email, $this->order->amount);

            if ($payoutResult == true) {
                $this->order->payout_status = 'paid';
            } else {
                // Log the payout failure
                Log::error('Payout failed for order ' . $this->order->id . ': ' . $payoutResult['error']);
            }
        } catch (\Exception $e) {
            // Log any exceptions that occur during the payout process
            Log::error('Exception during payout for order ' . $this->order->id . ': ' . $e->getMessage());

            // You may choose to re-throw the exception if needed
            // throw $e;
        }
    }
}
