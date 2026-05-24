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
        return response()->json([
            'is_active' => $this->subscriptionService->isActive($request->user(), $request->validated()['plan']),
        ], Response::HTTP_OK);
    }

    public function cancel(SubscriptionRequest $request): JsonResponse
    {
        $plan = $request->validated()['plan'];

        if (!$this->subscriptionService->isActive($request->user(), $plan)) {
            return response()->json(['message' => 'No active ' . $plan . ' subscription'], Response::HTTP_NOT_FOUND);
        }

        $this->subscriptionService->cancel($request->user(), $plan);

        return response()->json(['message' => ucfirst($plan) . ' subscription cancelled'], Response::HTTP_OK);
    }
}
