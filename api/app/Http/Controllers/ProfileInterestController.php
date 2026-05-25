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
        $request->user->profile->interests()->createMany(
            array_map(
                fn($interest) => ['interest' => $interest],
                $request->validated('interests')
            )
        );

        return response()->json(['message' => 'Interests have added successfully.'], Response::HTTP_CREATED);
    }

    public function deleteInterest(Interest $interest): JsonResponse
    {
        $interest->delete();

        return response()->json(['message' => 'Interest has deleted successfully.'], Response::HTTP_OK);
    }
}
