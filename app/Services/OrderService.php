<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;

class OrderService
{
    public function __construct(
        protected AffiliateService $affiliateService
    ) {}

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param  array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data)
    {
        // Extract data from the input array
        $orderId = $data['order_id'];
        $subtotalPrice = $data['subtotal_price'];
        $merchantDomain = $data['merchant_domain'];
        $discountCode = $data['discount_code'];
        $customerEmail = $data['customer_email'];
        $customerName = $data['customer_name'];

        // Create a new Merchant
        $merchant = Merchant::where('domain', $merchantDomain)->first();

        // Check if the order already exists based on order_id
        if (Order::where('id', $orderId)->exists()) {
            // Ignore duplicate order
            return;
        }

        // Check if the user exists for the customer_email with affiliate
        $user = User::where('email', $customerEmail)->with('affiliate')->first();

        if (!$user || ($user && !$user->affiliate)) {
            // Create a new affiliate if not exists
            $affiliate = $this->affiliateService->register($merchant, $customerEmail, $customerName, (float)$merchant->default_commission_rate);
        }

        // Log the order details
        $order = new Order();
        $order->merchant_id = $merchant->id;
        $order->affiliate_id = $affiliate->id;
        $order->subtotal = $subtotalPrice;
        $order->discount_code = $discountCode;
        $order->commission_owed = (float)$merchant->default_commission_rate;
        $order->save();

        return 'Order processed successfully';
    }
}