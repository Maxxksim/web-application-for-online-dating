<?php

namespace App\Providers;

use App\Events\MatchCreated;
use App\Listeners\SendMatchNotification;
use Illuminate\Support\Facades\Event;
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

    }
}
