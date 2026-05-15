<?php

namespace App\Http\Controllers;

use App\Services\MatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MatchController extends Controller
{
    public function __construct(private readonly MatchService $matchService)
    {

    }

    public function getMatches(Request $request): JsonResponse
    {
        return response()->json(['matches' => $this->matchService->getMatches($request->user())], Response::HTTP_OK);
    }
}
