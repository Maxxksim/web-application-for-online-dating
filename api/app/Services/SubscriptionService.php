<?php

namespace App\Services;

use App\Models\User;


class SubscriptionService
{
    public function checkout(User $user, string $planName): string
    {
        $checkout = $user
            ->newSubscription($planName, $this->priceId($planName))
            ->checkout([
                'success_url' => config('app.frontend_url') . '/subscription/success?plan=' . $planName,
                'cancel_url' => config('app.frontend_url') . '/subscription/cancel?plan=' . $planName,
            ]);

        return $checkout->url;
    }

    public function cancel(User $user, string $planName): void
    {
        $user->subscription($planName)->cancel();
    }

    public function isActive(User $user, string $planName): bool
    {
        return $user->subscribed($planName);
    }

    public function priceId(string $planName): string
    {
        return config('cashier.' . $planName . '_price_id');
    }
}
