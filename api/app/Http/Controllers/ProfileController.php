<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Models\Profile;
use Illuminate\Http\Request;


class ProfileController extends Controller
{
    public function updateProfile(UpdateProfileRequest $request): ProfileResource
    {
        $profile = $request->user()->profile;

        $profile->update($request->validated());

        return new ProfileResource($profile);
    }

    public function getProfile(Profile $profile): ProfileResource
    {
        return new ProfileResource($profile->load('photos'));
    }

    public function getOwnProfile(Request $request): ProfileResource
    {
        return new ProfileResource($request->user()->load('profile.photos')->profile);
    }
}
