<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Models\Profile;
use App\Services\ProfileService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;


class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profileService)
    {
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $profile = $request->user()->profile;

        $profile->update($request->validated());
        $this->profileService->updateCompletionPercentage($profile);

        return response()->json(['message' => 'Profile updated successfully.'], Response::HTTP_OK);
    }

    public function getProfile(Profile $profile): JsonResponse
    {
        return response()->json(['profile' => new ProfileResource($profile->load('photos'))], Response::HTTP_OK);
    }

    public function getMyProfile(Request $request): JsonResponse
    {
        return response()->json(['profile' => new ProfileResource($request->user()->profile->loadMissing('photos'))], Response::HTTP_OK);
    }

    public function enableProfile(Request $request): JsonResponse
    {
        if ($this->profileService->isProfileReadyForSearching($request->user()->profile)) {
            $request->user()->profile->update(['is_enabled' => true]);

            return response()->json(['message' => 'Profile enabled successfully.'], Response::HTTP_OK);
        }

        return response()->json([
            'message' => 'Please fill in all missing required fields and add at least one photo to enable profile.',
            'missing_fields' => $this->profileService->getMissingRequiredFields($request->user()->profile),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function disableProfile(Request $request): JsonResponse
    {
        if ($request->user()->profile->is_enabled) {
            $request->user()->profile->update(['is_enabled' => false]);
            return response()->json(['message' => 'Profile disabled successfully.'], Response::HTTP_OK);
        }

        return response()->json(['message' => 'Your profile is already disabled.'], Response::HTTP_OK);
    }


}
