<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class GeolocationService
{
    public function updateGeolocation(User $user, array $coordinates): void
    {
        $location = $this->getLocation($coordinates);

        $user->geolocation()->updateOrCreate([], [
            'latitude' => $coordinates['latitude'],
            'longitude' => $coordinates['longitude'],
        ]);

        $user->profile()->update([
            'city' => $location['city'],
            'country' => $location['country'],
        ]);
    }

    private function getLocation(array $coordinates): array
    {
        $response = Http::get('https://nominatim.openstreetmap.org/reverse', [
            'lat' => $coordinates['latitude'],
            'lon' => $coordinates['longitude'],
            'format' => 'json',
        ]);

        $address = $response->json('address');

        return [
            'city' => $address['city'] ?? $address['town'] ?? $address['village'],
            'country' => $address['country'],
        ];
    }
}
