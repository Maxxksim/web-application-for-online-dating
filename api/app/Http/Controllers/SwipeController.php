<?php

namespace App\Http\Controllers;

use App\Http\Requests\SwipeRequest;
use App\Services\MatchService;
use App\Services\SubscriptionService;
use App\Services\SwipeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SwipeController extends Controller
{
    public function __construct(private readonly SwipeService $swipeService, private readonly MatchService $matchService, private readonly SubscriptionService $subscriptionService)
    {

    }

    public function swipe(SwipeRequest $request, int $swiped_id): JsonResponse
    {
        if ($this->swipeService->isSwiped($request->user()->id, $swiped_id)) {
            return response()->json(['message' => 'Already swiped.'], Response::HTTP_CONFLICT);
        }

        $this->swipeService->swipe($request->user()->id, $swiped_id, $request->boolean('is_liked'));

        return response()->json(['message' => 'Swiped successfully.'], Response::HTTP_OK);
    }

    public function rollbackSwipe(Request $request, int $swiped_id): JsonResponse
    {
        if (!$this->subscriptionService->isActive($request->user(), 'premium')) {
            return response()->json(['messages' => 'You must be a premium subscriber.'], Response::HTTP_FORBIDDEN);
        }

        if ($this->matchService->haveMatch($request->user()->id, $swiped_id)) {
            return response()->json(['message' => 'You cannot cancel match.'], Response::HTTP_CONFLICT);
        }

        $this->swipeService->rollbackSwipe($request->user()->id, $swiped_id);

        return response()->json(['message' => 'Swipe has been rolled back'], Response::HTTP_OK);
    }
}
