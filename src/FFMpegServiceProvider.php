<?php

namespace Pbmedia\LaravelFFMpeg;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class FFMpegServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/laravel-ffmpeg.php' => config_path('laravel-ffmpeg.php'),
        ], 'config');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laravel-ffmpeg.php', 'laravel-ffmpeg');

        $this->app->singleton('laravel-ffmpeg', function (Container $app) {
            return $app->make(FFMpeg::class);
        });
    }
}
