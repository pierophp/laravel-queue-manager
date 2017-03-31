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
    protected $signature = 'queue-manager:generate-queue {name} {props?} --connection=';

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
        $props = $this->getProps();

        $queueConfig = QueueConfigRepository::findOneByName($name);
        if(!$queueConfig){
            throw new \Exception('Record not found for table queue_config for name ' . $name);
        }

        $className = $queueConfig->class_name;

        /** @var AbstractJob $queue */
        $queue = new $className;
        $queue->setProps($props);
        if ($this->option('connection')) {
            $queue->setConnectionName($this->option('connection'));
        }
        $queue->dispatch();
    }

    private function getProps()
    {
        $props = [];
        if ($this->argument('props')) {
            $props = explode(',', $this->argument('props'));
        }

        $parsedProps = [];

        foreach ($props as $prop) {
            $prop = explode('=', $prop);

            if (count($prop) < 2) {
                throw new \OutOfBoundsException("{$prop[0]} is invalid");
            }

            $parsedProps[$prop[0]] = $prop[1];
        }

        return $parsedProps;
    }
}
