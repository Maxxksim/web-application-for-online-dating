<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Mockery\Exception;
use PHPUnit\Event\Code\Throwable;

class PhotoService
{
    public function __construct(
        private string $validatorUrl
    )
    {
        $this->validatorUrl = config('services.face_validator.url');
    }

    public
    function validateUserPhotos(array $userPhotos): JsonResponse
    {
        $request = Http::asMultipart()->timeout(30);

        foreach ($userPhotos as $photo) {
            $request = $request->attach(
                'user_photos',
                file_get_contents($photo->getRealPath()),
                $photo->getClientOriginalName()
            );
        }

        return $request->post($this->validatorUrl)->json();
    }

    public function buildFileName(string $extension): string
    {
        return Str::uuid7() . $extension;
    }

}
