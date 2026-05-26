<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileInterestRequest;
use App\Models\Interest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProfileInterestController extends Controller
{
    public function addInterest(ProfileInterestRequest $request): JsonResponse
    {
        if ($request->user()->profile->interests()->count() >= 10) {
            return response()->json(['message' => 'You cannot have more than 10 Interests'], Response::HTTP_BAD_REQUEST);
        }

        $request->user()->profile->interests()->create($request->validated());

        return response()->json(['message' => 'Interests have added successfully.'], Response::HTTP_CREATED);
    }

    public function deleteInterest(Interest $interest): JsonResponse
    {
        $interest->delete();

        return response()->json(['message' => 'Interest has deleted successfully.'], Response::HTTP_OK);
    }
}
