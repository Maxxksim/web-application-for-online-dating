<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscriptionRequest;
use App\Services\SubscriptionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptionService)
    {

    }

    public function checkout(SubscriptionRequest $request): JsonResponse
    {
        $plan = $request->validated()['plan'];

        if ($this->subscriptionService->isActive($request->user(), $plan)) {
            return response()->json(['message' => 'Already subscribed to ' . $plan], Response::HTTP_CONFLICT);
        }

        $url = $this->subscriptionService->checkout($request->user(), $plan);

        return response()->json(['checkout_url' => $url], Response::HTTP_OK);
    }

    public function status(SubscriptionRequest $request): JsonResponse
    {
        $plan = $request->validated()['plan'];
        $subscription = $request->user()->subscription($plan);

        return response()->json([
            'is_active' => $this->subscriptionService->isActive($request->user(), $plan),
            'is_canceled' => $subscription?->canceled() ?? false,
            'ends_at' => $subscription?->ends_at?->toDateString(),
        ], Response::HTTP_OK);
    }

    public function cancel(SubscriptionRequest $request): JsonResponse
    {
        $plan = $request->validated()['plan'];
        $subscription = $request->user()->subscription($plan);

        if (!$subscription || !$subscription->active()) {
            return response()->json(['message' => 'No active ' . $plan . ' subscription'], Response::HTTP_NOT_FOUND);
        }

        if ($subscription->canceled()) {
            return response()->json(['message' => 'Subscription already canceled'], Response::HTTP_CONFLICT);
        }

        $this->subscriptionService->cancel($request->user(), $plan);

        return response()->json([
            'message' => ucfirst($plan) . ' subscription cancelled',
            'ends_at' => $request->user()->subscription($plan)->ends_at->toDateString(),
        ], Response::HTTP_OK);
    }

    public function resume(SubscriptionRequest $request): JsonResponse
    {
        $plan = $request->validated()['plan'];
        $subscription = $request->user()->subscription($plan);

        if (!$subscription || !$subscription->canceled()) {
            return response()->json(['message' => 'Subscription is not canceled'], Response::HTTP_CONFLICT);
        }

        $subscription->resume($request->user(), $plan);

        return response()->json(['message' => ucfirst($plan) . ' subscription resumed'], Response::HTTP_OK);
    }
}
