<?php

namespace LaravelQueueManager\Core;

use Illuminate\Support\Facades\View;
use LaravelQueueManager\Events\ScheduleError;
use LaravelQueueManager\Repository\QueueConfigRepository;
use Symfony\Component\Process\Process;

class SupervisorGenerator
{
    protected $queueConfigRepository;

    public function __construct(QueueConfigRepository $queueConfigRepository)
    {
        $this->queueConfigRepository = $queueConfigRepository;
    }

    public function generate()
    {
        $supervisorConfig = View::make('laravel_queue_manager::supervisor-generator', ['configs' => $this->queueConfigRepository->findAll()]);

        $filename = config('queue_manager.supervisor_config_file');

        $supervisorConfigOld = '';
        if (file_exists($filename)) {
            $supervisorConfigOld = file_get_contents($filename);
        }

        //if ($supervisorConfigOld != $supervisorConfig) {

            file_put_contents($filename, $supervisorConfig);

            $supervisorBin = config('queue_manager.supervisor_bin');

            $process = new Process($supervisorBin . ' reread');
            $process->run();

            if (!$process->isSuccessful()) {

                event(new ScheduleError($process->getOutput(), ['command' => 'reread']));

                //\BusinessLogger::error('supervisor_generator_error', );
            }

            $process = new Process($supervisorBin . ' update');
            $process->setTimeout(600);
            $process->run();

           // if (!$process->isSuccessful()) {
                new ScheduleError($process->getOutput(), ['command' => 'update']);
                //event();

            //}

       // }
    }
}