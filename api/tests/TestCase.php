<?php

namespace Tests;

use App\Models\User;
use App\Services\LocationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    //use RefreshDatabase;

    protected bool $seed = true;
    protected string $seeder = DatabaseSeeder::class;

    private array $coordinates = ["longitude" => 21.068254565590866, "latitude" => 56.54564163326026];
    protected LocationService $locationService;

    protected function setUp(): void
    {
        parent::setUp();
        //$this->seed(DatabaseSeeder::class);
        $this->locationService = app(LocationService::class);
    }

    protected function getUser(): array
    {
        $user = User::factory()->create();

        $user->profile()->create([]);
        $this->locationService->updateLocation($user, $this->coordinates);
        $user->searchFilter()->create();

        return ['token' => $user->createToken('auth', expiresAt: now()->addMonth())->plainTextToken, 'user' => $user];
    }
}
