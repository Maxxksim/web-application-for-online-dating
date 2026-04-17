<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfilePhotoRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Jobs\ProcessValidatePhoto;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Services\PhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;


class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request): ProfileResource
    {
        $profile = $request->user()->profile->update($request->validated());

        return new ProfileResource($profile);
    }

    public function getProfile(Profile $profile): ProfileResource
    {
        return new ProfileResource($profile->with('photos')->first());
    }


}
