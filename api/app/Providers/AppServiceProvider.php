<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->app->bind(ImageManager::class, fn() => ImageManager::usingDriver(Driver::class));
    }

    public function boot(): void
    {
        JsonResource::withoutWrapping();
    }
}
