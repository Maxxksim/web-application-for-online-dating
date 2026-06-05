<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;


class PhotoService
{
    private const int IMAGE_QUALITY = 60;
    private const int MAX_WIDTH = 720;
    private const int MAX_HEIGHT = 960;

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

        try {
            $response = $request->post(config('services.face_validator.url'))->json();
        } catch (ConnectionException $e) {
            throw new ServiceUnavailableHttpException(message: 'Face validator service is unavailable');
        }

        return $response;
    }

    public function buildFileName(): string
    {
        return Str::uuid7() . '.webp';
    }

    public function compressImage($photo): string
    {
        return (string)$this->imageManager
            ->decodeSplFileInfo($photo)
            ->scaleDown(self::MAX_WIDTH, self::MAX_HEIGHT)
            ->encode(new WebpEncoder(self::IMAGE_QUALITY));
    }

}
