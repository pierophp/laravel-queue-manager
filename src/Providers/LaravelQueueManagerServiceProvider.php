<?php

namespace LaravelQueueManager\Providers;

use App\Jobs\RunJob;
use App\Jobs\SshJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobFailed;
use LaravelQueueManager\AbstractJob;
use LaravelQueueManager\Console\Commands\GenerateConfigCommand;
use LaravelQueueManager\Console\Commands\GenerateQueueCommand;
use LaravelQueueManager\Console\Commands\ShowJobsCommand;
use LaravelQueueManager\Console\Commands\WorkCommand;
use LaravelQueueManager\Core\Scheduler;
use LaravelQueueManager\Core\Worker;
use LaravelQueueManager\Events\AfterQueueError;
use LaravelQueueManager\Model\QueueConfig;
use LaravelQueueManager\Repository\QueueConfigRepository;

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

        $this->publishes([
            __DIR__ . '/../migrations/2018_03_02_153409_queue_config_add_aggregator.php' => database_path('migrations/2018_03_02_153409_queue_config_add_aggregator.php'),
        ]);

        $this->publishes([
            __DIR__ . '/../migrations/2018_09_19_153409_queue_config_add_config.php' => database_path('migrations/2018_09_19_153409_queue_config_add_config.php'),
        ]);

        \View::addNamespace('laravel_queue_manager', __DIR__ . '/../resources/views');

        $this->schedule();
        $this->commands('queue-manager.generate-config');
        $this->commands('queue-manager.generate-queue');
        $this->commands('queue-manager.show-jobs');
        $this->commands('queue-manager.work');

        \Queue::after(function(JobProcessed $event) {
            $queueName = $event->job->getQueue();

            /** @var QueueConfig $queueConfig */
            $queueConfig = QueueConfigRepository::findOneByName($queueName);

            if(!$queueConfig) {
                throw new \Exception(sprintf('Queue config for %s not found', $queueName));
            }

            $config = json_decode($queueConfig->config);

            if (!isset($config->nextQueues) || !isset($config->nextQueues->onSuccess)) {
                return;
            }

            $this->dispatchNextQueues($queueName, $config->nextQueues->onSuccess);
        });

        \Queue::failing(function(JobFailed $event) {
            $queueName = $event->job->getQueue();

            /** @var QueueConfig $queueConfig */
            $queueConfig = QueueConfigRepository::findOneByName($queueName);

            if(!$queueConfig) {
                throw new \Exception(sprintf('Queue config for %s not found', $queueName));
            }

            $config = json_decode($queueConfig->config);

            if (!isset($config->nextQueues) || !isset($config->nextQueues->onError)) {
                return;
            }

            $this->dispatchNextQueues($queueName, $config->nextQueues->onError);
        });
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
        $this->app->singleton('queue-manager.generate-config', function () {
            return new GenerateConfigCommand();
        });

        $this->app->singleton('queue-manager.generate-queue', function () {
            return new GenerateQueueCommand();
        });

        $this->app->singleton('queue-manager.show-jobs', function () {
            return new ShowJobsCommand();
        });

        $this->app->singleton('queue-manager.work', function () {
            return new WorkCommand(new Worker(resolve('Illuminate\Queue\QueueManager'), resolve('Illuminate\Contracts\Events\Dispatcher'), resolve('Illuminate\Contracts\Debug\ExceptionHandler')));
        });
    }

    /**
     * @param $parentQueue
     * @param $queues
     */
    protected function dispatchNextQueues($parentQueue, $queues)
    {
        if (!$parentQueue || !$queues) {
            return;
        }

        foreach ($queues as $queue) {
            try {
                $queueConfig = QueueConfigRepository::findOneByName($queue->name);

                if (!$queueConfig) {
                    throw new \Exception('Queue config not found');
                }

                if ($parentQueue === $queue->name) {
                    throw new \Exception('Next queue cannot be the same as parent');
                }

                $className = $queueConfig->class_name;

                /** @var AbstractJob $job */
                $job = new $className();
                $job->setName($queue->name);

                if ($job instanceof RunJob) {
                    $job->setService($queue->service);
                    $job->setData($queue->data);
                    $job->setUrl($queue->url);
                    $job->setMethod($queue->method);
                    $job->setId($queue->id ?? null);
                }

                if ($job instanceof SshJob) {
                    if (empty($queue->command)) {
                        throw new \Exception('Command can not be empty');
                    }

                    $job->setCommand($queue->command);
                    $job->setHost($queue->host);
                    $job->setId($queue->id ?? null);
                }

                $delaySeconds = 0;
                if (!empty($queue->delay_seconds)) {
                    $delaySeconds = $queue->delay_seconds;
                }

                $job->dispatch($delaySeconds);
            } catch (\Throwable $e) {
                event(new AfterQueueError('After Queue Error - ' . $queue->name, ['exception' => $e]));
            }
        }
    }
}