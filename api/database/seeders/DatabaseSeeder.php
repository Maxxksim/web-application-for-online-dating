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
        ['latitude' => 56.53302457817058, 'longitude' => 21.06067053777407],
        ['latitude' => 56.50558217894939, 'longitude' => 21.013424615931065],
        ['latitude' => 56.499237324770334, 'longitude' => 21.005022638735692],
        ['latitude' => 56.48766984009108, 'longitude' => 21.011785009825118],
        ['latitude' => 56.54983412830958, 'longitude' => 21.05254274679326],
        ['latitude' => 56.543137242870806, 'longitude' => 21.087346377088007],
        ['latitude' => 56.58327540530102, 'longitude' => 21.021457365216566],
        ['latitude' => 56.49033013918047, 'longitude' => 20.998805601572645],
        ['latitude' => 56.53818486186969, 'longitude' => 21.00517055352318],
        ['latitude' => 56.51255675115504, 'longitude' => 20.994805691813077],
        ['latitude' => 56.473320797953924, 'longitude' => 21.003002627292364],
        ['latitude' => 56.4659760395277, 'longitude' => 21.016522997683623],
        ['latitude' => 56.52373654639359, 'longitude' => 21.02020987444396],
        ['latitude' => 56.5476941647161, 'longitude' => 21.02868191995524],
        ['latitude' => 56.55322520380444, 'longitude' => 21.08267971046395],
        ['latitude' => 56.55633276387169, 'longitude' => 21.00219157150465],
        ['latitude' => 56.52756845360318, 'longitude' => 21.044375468527736],
        ['latitude' => 56.515647889547154, 'longitude' => 21.011744212089237],
        ['latitude' => 56.507223448919234, 'longitude' => 21.01607103114513],
        ['latitude' => 56.506761574550595, 'longitude' => 21.000966278015966],
        ['latitude' => 56.5456835496117, 'longitude' => 21.067992617542593]
    ];

    public function run(LocationService $locationService): void
    {
        $coordinates = $this->coordinates;
        User::factory(count($this->coordinates))->create()->each(function (User $user) use ($locationService, &$coordinates) {
            $user->profile()->create([
                'name' => fake()->name(),
                'date_of_birth' => fake()->dateTimeBetween('-40 years', '-18 years')->format('Y-m-d'),
                'gender' => fake()->randomElement(['woman', 'man']),
            ]);
            $user->searchFilter()->create([
                'gender' => fake()->randomElement(['woman', 'man', 'both']),
                'min_age' => random_int(16, 20),
                'max_age' => random_int(20, 35),
                'distance' => random_int(25, 35),
            ]);

            $locationService->updateLocation($user, array_pop($coordinates));
        });


    }
}
