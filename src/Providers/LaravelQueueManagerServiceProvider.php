<?php

namespace LaravelQueueManager\Providers;

use Illuminate\Support\ServiceProvider;
use LaravelQueueManager\Console\Commands\GenerateCommand;

class LaravelQueueManagerServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('queue_manager.php'),
        ], 'config');

        $this->bootBindings();

        $this->commands('queue_manager.generate');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommand();

        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'queue_manager');
    }

    /**
     * Register the Artisan command.
     */
    protected function registerCommand()
    {
        $this->app['queue_manager.generate'] = $this->app->share(function () {
            return new GenerateCommand();
        });
    }
}