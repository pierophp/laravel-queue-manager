<?php

namespace LaravelQueueManager\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use LaravelQueueManager\Console\Commands\GenerateCommand;
use LaravelQueueManager\Core\Scheduler;

class LaravelQueueManagerServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/config.php' => config_path('queue_manager.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../migrations/2016_09_22_172624_create_queue_configs_table.php' => database_path('migrations/2016_09_22_172624_create_queue_configs_table.php'),
        ]);

        View::addNamespace('laravel_queue_manager', __DIR__ . '/../resources/views');

        $this->schedule();
        $this->commands('queue_manager.generate');
    }

    public function schedule()
    {
        $command = \Request::server('argv', null);

        if (!isset($command[1])) {
            return;
        }

        if ($command[1] != 'schedule:run') {
            return;
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            Scheduler::schedule($schedule);
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommand();

        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'queue_manager');
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