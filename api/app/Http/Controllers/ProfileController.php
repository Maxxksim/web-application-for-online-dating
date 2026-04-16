<?php

namespace App\Http\Controllers;

use App\Http\Requests\PhotoRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Jobs\ProcessValidatePhoto;
use App\Services\PhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;


class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request): ProfileResource
    {
        $profile = $request->user()->profile()->update($request->validated());

        return new ProfileResource($profile->fresh());
    }

    public function addPhoto(PhotoRequest $request, PhotoService $photoService): JsonResponse
    {
        $photos = $request->validated()['photos'];;

        foreach ($photos as $photo) {
            $photoName = $photoService->buildFileName();
            ($profile = $request->user()->profile)->photos()->create([
                'path' => 'profile_photos/' . $photoName,
            ]);

            Storage::disk('public')->put('profile_photos/' . $photoName, $photoService->compressImage($photo));
        }

        ProcessValidatePhoto::dispatch($profile);

        return response()->json(['message' => 'Photos are being validated'], Response::HTTP_ACCEPTED);
    }


}
