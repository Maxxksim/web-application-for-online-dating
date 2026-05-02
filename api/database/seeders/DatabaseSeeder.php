<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\LocationService;
use App\Services\ProfileService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    private array $coordinates = [
        ["longitude" => 21.06067053777407, "latitude" => 56.53302457817058],
        ["longitude" => 21.013424615931065, "latitude" => 56.50558217894939],
        ["longitude" => 21.005022638735692, "latitude" => 56.499237324770334],
        ["longitude" => 21.011785009825118, "latitude" => 56.48766984009108],
        ["longitude" => 21.05254274679326, "latitude" => 56.54983412830958],
        ["longitude" => 21.087346377088007, "latitude" => 56.543137242870806],
        ["longitude" => 21.021457365216566, "latitude" => 56.58327540530102],
        ["longitude" => 20.998805601572645, "latitude" => 56.49033013918047]
    ];


    public function run(LocationService $locationService, ProfileService $profileService): void
    {
        User::factory(10)->create()->each(function (User $user) use ($locationService, $profileService) {
            $user->profile()->create([
                'name' => fake()->name(),
                'date_of_birth' => fake()->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
                'gender' => fake(['woman', 'man']),
            ]);
            $user->searchFilter()->create([
                'gender' => fake(['woman', 'man', 'both']),
                'age' => random_int(16, 30),
                'distance' => random_int(5, 20),
            ]);
            $locationService->updateLocation($user, array_pop($this->coordinates), $profileService);
        });


    }
}
