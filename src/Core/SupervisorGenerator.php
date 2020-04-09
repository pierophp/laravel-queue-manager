<?php

namespace LaravelQueueManager\Core;

use Illuminate\Support\Facades\View;
use LaravelQueueManager\Events\ScheduleError;
use LaravelQueueManager\Repository\QueueConfigRepository;
use Symfony\Component\Process\Process;

class SupervisorGenerator
{
    protected $queueConfigRepository;

    protected $filename;

    public function __construct(QueueConfigRepository $queueConfigRepository)
    {
        $this->queueConfigRepository = $queueConfigRepository;
        $this->filename = config('queue_manager.supervisor_config_file');
    }

    public function generate()
    {
        $configs = $this->queueConfigRepository->findAll();
        $fallbackConnections = array_filter(config('queue_manager.fallback_connections'), function ($name) {
            return $name !== 'sync';
        });

        $hostname = gethostname();

        $configs = $configs->filter(function ($config) use ($hostname) {
            if (!$config->server_group) {
                return true;
            }
            
            return substr($hostname, 0, strlen($config->server_group)) === $config->server_group;
        });

        $instancesFactor = 5;

        foreach ($configs as $config) {
            $connectionName = config('queue.default');
            if ($config->connection && $config->connection !== 'default') {
                $connectionName = $config->connection;
            }

            $size = \Queue::size($config->name);
            $calculatedInstances = 1;
            if ($size > 0) {
                $calculatedInstances = round($size / $instancesFactor);
            }

            $config->current_instances = max(min($calculatedInstances, $config->max_instances ?? 1), $config->min_instances ?? 1);
            $config->save();

            $config->fallback_connections = array_filter($fallbackConnections, function ($name) use ($config) {
                $configConnection = $config->connection;
                if (!$configConnection) {
                    $configConnection = config('queue.default');
                }

                return $name !== $configConnection;
            });
        }

        $supervisorConfig = View::make('laravel_queue_manager::supervisor-generator', [
            'configs' => $configs,
            'fallbackConnections' => array_filter($fallbackConnections, function ($name) {
                return $name !== config('queue.default');
            }),
        ]);

        $supervisorConfigOld = '';
        if (file_exists($this->filename)) {
            $supervisorConfigOld = file_get_contents($this->filename);
        }

        if ($supervisorConfigOld != $supervisorConfig) {

            file_put_contents($this->filename, $supervisorConfig);

            $supervisorBin = config('queue_manager.supervisor_bin');

            $process = new Process([$supervisorBin, 'reread']);
            $process->run();

            if (!$process->isSuccessful()) {
                event(new ScheduleError($process->getOutput(), ['command' => 'reread']));
                throw new \Exception('Supervisor Reread error ' . $process->getOutput());
            }

            $process = new Process([$supervisorBin, 'update']);
            $process->setTimeout(config('queue_manager.supervisor_update_timeout'));
            $process->run();

            if (!$process->isSuccessful()) {
                event(new ScheduleError($process->getOutput(), ['command' => 'update']));
                throw new \Exception('Supervisor Update error ' . $process->getOutput());
            }

        }
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

}
