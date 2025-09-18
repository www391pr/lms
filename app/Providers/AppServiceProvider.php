<?php

namespace App\Providers;

use FFMpeg\FFMpeg;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FFMpeg::class, function () {
            return FFMpeg::create([
                'ffmpeg.binaries'  => base_path('ffmpeg/ffmpeg.exe'),
                'ffprobe.binaries' => base_path('ffmpeg/ffprobe.exe'),
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

}
