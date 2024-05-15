<?php

namespace App\Services;

use Hash;
use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Services\ApiService;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {}

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     * @throws AffiliateCreateException
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        // Check if a user with the given email already exists with affiliate

        $user = User::where('email', $email)->with('affiliate')->first();

        if (!$user) {
            // Create a new user
            $user = new User();
            $user->name = $name;
            $user->email = $email;
            $user->password = Hash::make('123456');
            $user->type = User::TYPE_AFFILIATE;
            $user->save();
        }

        if ($user && $user->affiliate) {
            return $existingAffiliate;
        }

        // Create a new affiliate
        $affiliate = new Affiliate();
        $affiliate->user_id = $user->id;
        $affiliate->merchant_id = $merchant->id;
        $affiliate->commission_rate = $commissionRate;
        $affiliate->discount_code = $this->apiService->createDiscountCode($merchant);
        $affiliate->save();

        // Check creation of affiliate
        if (!$affiliate->id) {
            // Handle affiliate creation failure
            throw new AffiliateCreateException('Failed to create affiliate.');
        }

        // Send an email to notify about affiliate creation
        $this->sendAffiliateCreatedEmail($affiliate);

        return $affiliate;
    }

    /**
     * Send an email notification about affiliate creation.
     *
     * @param  Affiliate $affiliate
     * @return void
     */
    protected function sendAffiliateCreatedEmail(Affiliate $affiliate)
    {
        Mail::to($affiliate->user->email)->send(new AffiliateCreated($affiliate));
        // You can customize the email logic based on your application requirements
    }
}

