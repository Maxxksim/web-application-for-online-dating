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

        if ($profile->is_enabled || !$profile->manually_disabled) {
            $this->profileService->enableIfReady($profile);
        }

        return response()->json(['message' => 'Profile updated successfully.'], Response::HTTP_OK);
    }

    public function getProfile(Profile $profile): JsonResponse
    {
        return response()->json(['profile' => new ProfileResource($profile->load('photos'))], Response::HTTP_OK);
    }

    public function getMyProfile(Request $request): JsonResponse
    {
        $this->profileService->updateCompletionPercentage($request->user()->profile);

        return response()->json(['profile' => new ProfileResource($request->user()->profile->loadMissing('photos'))], Response::HTTP_OK);
    }

    public function enableProfile(Request $request): JsonResponse
    {
        $profile = $request->user()->profile;

        if ($profile->is_enabled) {
            return response()->json(['message' => 'Profile is already enabled.'], Response::HTTP_OK);
        }

        if ($this->profileService->enableIfReady($profile)) {
            $profile->update(['manually_disabled' => false]);
            return response()->json(['message' => 'Profile enabled successfully.'], Response::HTTP_OK);
        }

        return response()->json([
            'message' => 'Please fill in all missing required fields and add at least one photo to enable profile.',
            'missing_fields' => $this->profileService->getMissingRequiredFields($profile),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function disableProfile(Request $request): JsonResponse
    {
        $profile = $request->user()->profile;

        if ($profile->is_enabled) {
            $profile->update(['is_enabled' => false, 'manually_disabled' => true]);
            return response()->json(['message' => 'Profile disabled successfully.'], Response::HTTP_OK);
        }

        return response()->json(['message' => 'Your profile is already disabled.'], Response::HTTP_OK);
    }


}
