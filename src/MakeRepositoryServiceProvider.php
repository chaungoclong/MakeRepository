<?php

namespace Longtnt\MakeRepository;

use Illuminate\Support\ServiceProvider;
use Longtnt\MakeRepository\Commands\MakeRepository;
use Longtnt\MakeRepository\Commands\MakeRepositoryContract;

class MakeRepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/mkrepo.php',
            'mkrepo'
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/mkrepo.php' => config_path('mkrepo.php'),
        ]);

        $this->commands([
            MakeRepository::class,
            MakeRepositoryContract::class,
        ]);
    }
}
