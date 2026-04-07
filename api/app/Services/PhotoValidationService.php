<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PhotoValidationService
{
    private string $validatorUrl = 'http://localhost:9000/validate-user-photos';

    public function validateUserPhotos(?array $userPhotos): ?array
    {

        if (empty($userPhotos)) {
            return null;
        }

        $request = Http::createPendingRequest();

        foreach ($userPhotos as $photo) {
            $request = $request->attach(
                'user_photos',
                fopen($photo->getRealPath(), 'r'),
                $photo->getClientOriginalName()
            );
        }

        return $request->post($this->validatorUrl)->json();
    }
}
