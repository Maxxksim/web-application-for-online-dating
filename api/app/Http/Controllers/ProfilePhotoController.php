<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfilePhotoRequest;
use App\Jobs\ProcessValidatePhoto;
use App\Models\ProfilePhoto;
use App\Services\PhotoService;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProfilePhotoController extends Controller
{
    public function __construct(private readonly PhotoService $photoService, private readonly ProfileService $profileService)
    {

    }

    public function addPhoto(ProfilePhotoRequest $request): JsonResponse
    {
        $photos = $request->validated()['photos'];;

        foreach ($photos as $photo) {
            $photoName = $this->photoService->buildFileName();
            ($profile = $request->user()->profile)->photos()->create([
                'path' => 'profile_photos/' . $photoName,
            ]);

            Storage::disk('public')->put('profile_photos/' . $photoName, $this->photoService->compressImage($photo));
        }

        ProcessValidatePhoto::dispatch($profile);

        return response()->json(['message' => 'Photos are being validated'], Response::HTTP_ACCEPTED);
    }

    #[Authorize('delete', 'photo')]
    public function deletePhoto(ProfilePhoto $photo): JsonResponse
    {
        DB::transaction(function () use ($photo) {
            $profile = $photo->profile;
            $photo->delete();
            Storage::disk('public')->delete($photo->path);
            $this->profileService->updateCompletionPercentage($profile);
        });

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
