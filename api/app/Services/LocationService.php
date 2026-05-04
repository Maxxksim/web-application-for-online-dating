<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class LocationService
{
    public function updateLocation(User $user, array $coordinates): void
    {
        $location = $this->getLocation($coordinates);

        $user->geolocation()->updateOrCreate([], [
            'geo_point' => DB::raw("ST_SetSRID(ST_MakePoint($coordinates[longitude], $coordinates[latitude]), 4326)")
        ]);

        $user->profile()->update([
            'city' => $location['city'],
            'country' => $location['country'],
        ]);
    }

    private function getLocation(array $coordinates): array
    {
        try {
            $response = Http::withHeaders(['User-Agent' => config('app.name')])
                ->get(config('services.reverse_geocoding.url'), [
                    'lat' => $coordinates['latitude'],
                    'lon' => $coordinates['longitude'],
                    'format' => 'json',
                ]);
        } catch (ConnectionException $e) {
            throw new ServiceUnavailableHttpException(message: $e->getMessage());
        }

        $address = $response->json('address');

        return [
            'city' => $address['city'] ?? $address['town'] ?? $address['village'] ?? null,
            'country' => $address['country'] ?? null,
        ];
    }
}
