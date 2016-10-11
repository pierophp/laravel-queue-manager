<?php

namespace LaravelQueueManager\Console\Commands;

use LaravelQueueManager\AbstractJob;
use LaravelQueueManager\Core\SupervisorGenerator;
use Illuminate\Console\Command;
use LaravelQueueManager\Repository\QueueConfigRepository;

class GenerateQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue-manager:generate-queue {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a queue';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');
        $queueConfig = QueueConfigRepository::findOneByName($name);
        $className = $queueConfig->class_name;

        /** @var AbstractJob $queue */
        $queue = new $className;
        $queue->dispatch();
    }
}
