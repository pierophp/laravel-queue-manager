<?php

namespace LaravelQueueManager\Core;

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
        $supervisorConfig = view('system/supervisor-generator', ['configs' => $this->queueConfigRepository->findBy([])]);

        $filename = '/etc/supervisor/conf.d/laravel-queue.conf';

        $supervisorConfigOld = file_get_contents($filename);

        if ($supervisorConfigOld != $supervisorConfig) {

            file_put_contents($filename, $supervisorConfig);

            $process = new Process('/usr/bin/supervisorctl reread');
            $process->run();

            if (!$process->isSuccessful()) {
                \BusinessLogger::error('supervisor_generator_error', $process->getOutput(), ['command' => 'reread']);
            }

            $process = new Process('/usr/bin/supervisorctl update');
            $process->setTimeout(600);
            $process->run();

            if (!$process->isSuccessful()) {
                \BusinessLogger::error('supervisor_generator_error', $process->getOutput(), ['command' => 'reread']);
            }

        }
    }
}