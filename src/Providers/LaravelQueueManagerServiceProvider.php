<?php

namespace LaravelQueueManager\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use LaravelQueueManager\Console\Commands\GenerateConfigCommand;
use LaravelQueueManager\Console\Commands\GenerateQueueCommand;
use LaravelQueueManager\Console\Commands\ShowJobsCommand;
use LaravelQueueManager\Console\Commands\WorkCommand;
use LaravelQueueManager\Core\Scheduler;
use LaravelQueueManager\Core\Worker;

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

        $this->publishes([
            __DIR__ . '/../migrations/2016_10_21_153409_queue_config_add_connection.php' => database_path('migrations/2016_10_21_153409_queue_config_add_connection.php'),
        ]);

        View::addNamespace('laravel_queue_manager', __DIR__ . '/../resources/views');

        $this->schedule();
        $this->commands('queue-manager.generate-config');
        $this->commands('queue-manager.generate-queue');
        $this->commands('queue-manager.show-jobs');
        $this->commands('queue-manager.work');
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
        $this->app['queue-manager.generate-config'] = $this->app->share(function () {
            return new GenerateConfigCommand();
        });

        $this->app['queue-manager.generate-queue'] = $this->app->share(function () {
            return new GenerateQueueCommand();
        });

        $this->app['queue-manager.show-jobs'] = $this->app->share(function () {
            return new ShowJobsCommand();
        });

        $this->app['queue-manager.work'] = $this->app->share(function () {
            return new WorkCommand(new Worker(resolve('Illuminate\Queue\QueueManager'), resolve('Illuminate\Contracts\Events\Dispatcher'), resolve('Illuminate\Contracts\Debug\ExceptionHandler')));
        });

    }
}