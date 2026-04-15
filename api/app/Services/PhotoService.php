<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;


class PhotoService
{
    private const int IMAGE_QUALITY = 60;

    public function __construct(private readonly ImageManager $imageManager)
    {

    }

    public function validateUserPhotos($userPhotos): array
    {
        $request = Http::asMultipart()->timeout(30);
        foreach ($userPhotos as $photo) {
            $request->attach(
                'user_photos',
                Storage::disk('public')->get($photo['path']),
                $photo['path']
            );
        }

        return $request->post(config('services.face_validator.url'))->json();
    }

    public function buildFileName(): string
    {
        return Str::uuid7() . '.webp';
    }

    public function compressImage($photo): string
    {
        return (string)$this->imageManager->decodeSplFileInfo($photo)->encode(new WebpEncoder(self::IMAGE_QUALITY));
    }

}
