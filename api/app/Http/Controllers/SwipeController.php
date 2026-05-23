<?php

namespace App\Http\Controllers;

use App\Http\Requests\SwipeRequest;
use App\Services\SwipeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SwipeController extends Controller
{
    public function __construct(private readonly SwipeService $swipeService)
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
}
