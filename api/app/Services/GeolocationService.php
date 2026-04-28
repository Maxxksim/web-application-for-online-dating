<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GeolocationService
{
    public function updateGeolocation(User $user, array $coordinates): void
    {
        $location = $this->getLocation($coordinates);

        $user->profile->geolocation()->updateOrCreate([], [
            'latitude' => $coordinates['latitude'],
            'longitude' => $coordinates['longitude'],
            'geo_point' => DB::raw("ST_SetSRID(ST_MakePoint($coordinates[longitude], $coordinates[latitude]), 4326)")
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
