<?php

namespace App\Http\Controllers;

use App\Http\Requests\SwipeRequest;
use App\Services\SwipeService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SwipeController extends Controller
{
    public function __construct(private readonly SwipeService $swipeService)
    {

    }

    public function swipe(SwipeRequest $request, int $swiped_id): JsonResponse
    {
        $this->swipeService->swipe($request->user()->profile->id, $swiped_id, $request->boolean('is_liked'));

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
