<?php

namespace LaravelQueueManager\Providers;

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
            $nextQueues = $this->getNextQueues($event->job, 'onSuccess');

            if (!$nextQueues) {
                return;
            }

            $this->dispatchNextQueues($event->job, $nextQueues);
        });

        \Queue::failing(function(JobFailed $event) {
            $nextQueues = $this->getNextQueues($event->job, 'onError');

            if (!$nextQueues) {
                return;
            }

            $this->dispatchNextQueues($event->job, $nextQueues);
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

    protected function getDatabaseNextQueues($queueName, $onSuccessOrError) 
    {
        $cacheKey = 'DATABASE_NEXT_QUEUES_' . $queueName;
        $cacheStore = config('queue_manager.cache_store');
        
        try {

            /** @var QueueConfig $queueConfig */
            $queueConfig = QueueConfigRepository::findOneByName($queueName);

            if (!$queueConfig) {
                throw new \Exception(sprintf('Queue config for %s not found', $queueName));
            }

            $config = json_decode($queueConfig->config);

            $nextQueues = null;

            if (isset($config->nextQueues) && isset($config->nextQueues->$onSuccessOrError)) {
                $nextQueues = $config->nextQueues->$onSuccessOrError;
            }

            \Cache::store($cacheStore)->add($cacheKey, $nextQueues, 60 * 24); // 1 day

            return $nextQueues;

        } catch (\Throwable $e) {
            if (!\Cache::store($cacheStore)->has($cacheKey)) {
                throw $e;
            }

            return \Cache::store($cacheStore)->get($cacheKey);
        }    
    }


    /**
     * @param $job
     * @param $onSuccessError 'onSuccess|onError'
     */
    protected function getNextQueues($job, $onSuccessOrError = 'onSuccess')
    {
        if (!$job) {
            return;
        }

        $queueName = $job->getQueue();

        $nextQueues = $this->getDatabaseNextQueues($queueName, $onSuccessOrError);

        $payload = json_decode($job->getRawBody());
        $data = unserialize($payload->data->command);

        if (!isset($data->data)) {
            return $nextQueues;
        }

        $data = json_decode(json_encode($data->data));

        if (isset($data->nextQueues) && isset($data->nextQueues->$onSuccessOrError)) {
            $nextQueues = $data->nextQueues->$onSuccessOrError;
        }

        return $nextQueues;
    }

    /**
     * @param $parentJob
     * @param $queues
     */
    protected function dispatchNextQueues($parentJob, $queues)
    {
        if (!$parentJob || !$queues) {
            return;
        }

        foreach ($queues as $queue) {
            $queue = is_array($queue) ? (object) $queue : $queue;

            try {                

                $parentJobPayload = json_decode($parentJob->getRawBody());
                $parentJobData = unserialize($parentJobPayload->data->command);

                if ($parentJob->getQueue() === $queue->name) {
                    $parentJobDataCompare = md5(json_encode(array_except((array) $parentJobData->data, ['nextQueues'])));

                    $queueData = (array) $queue->data;
                    $queueDataCompare = md5(json_encode(array_except($queueData, ['nextQueues'])));

                    if ($queueDataCompare === $parentJobDataCompare) {
                        throw new \Exception('Next queue cannot be the same as parent with the same data params');
                    }
                }

                try {
                    $queueConfig = QueueConfigRepository::findOneByName($queue->name);
                    if (!$queueConfig) {
                        throw new \Exception('Queue config not found');
                    }

                    $className = $queueConfig->class_name;

                    /** @var AbstractJob $job */
                    $job = new $className();

                } catch (\Throwable $e) {
                    $defaultJobClassName = config('queue_manager.default_job_class_name');
                    $job = new $defaultJobClassName();
                }
                
                $queue->id = $parentJobData->id;

                foreach ($queue as $key => $config) {
                    $method = 'set' . ucfirst($key);

                    if (!method_exists($job, "{$method}")) {
                        continue;
                    }

                    $job->$method($config);
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