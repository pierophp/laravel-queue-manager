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
        $fallbackConnections = array_filter(config('queue_manager.fallback_connections'), function($name) {
            return $name !== 'sync';
        });

        foreach($configs as $config) {
            $connectionName = config('queue.default');
            if ($config->connection && $config->connection !== 'default') {
                $connectionName = $config->connection; 
            }

            $config->fallback_connections = array_filter($fallbackConnections, function($name) use($config) {
                return $name !== $config->connection;
            });
        }

        $supervisorConfig = View::make('laravel_queue_manager::supervisor-generator', [
            'configs' => $configs,
            'fallbackConnections' => $fallbackConnections,
        ]);

        $supervisorConfigOld = '';
        if (file_exists($this->filename)) {
            $supervisorConfigOld = file_get_contents($this->filename);
        }

        if ($supervisorConfigOld != $supervisorConfig) {

            file_put_contents($this->filename, $supervisorConfig);

            $supervisorBin = config('queue_manager.supervisor_bin');

            $process = new Process($supervisorBin . ' reread');
            $process->run();

            if (!$process->isSuccessful()) {
                event(new ScheduleError($process->getOutput(), ['command' => 'reread']));
            }

            $process = new Process($supervisorBin . ' update');
            $process->setTimeout(config('queue_manager.supervisor_update_timeout'));
            $process->run();

            if (!$process->isSuccessful()) {
                event(new ScheduleError($process->getOutput(), ['command' => 'update']));
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