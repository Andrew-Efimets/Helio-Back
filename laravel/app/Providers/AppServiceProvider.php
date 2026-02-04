<?php

namespace App\Providers;

use App\Models\Photo;
use App\Models\User;
use App\Models\Video;
use App\Observers\PhotoObserver;
use App\Observers\UserObserver;
use App\Observers\VideoObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        Photo::observe(PhotoObserver::class);
        Video::observe(VideoObserver::class);
    }
}
