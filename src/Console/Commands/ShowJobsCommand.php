<?php

namespace LaravelQueueManager\Console\Commands;

use Illuminate\Console\Command;
use LaravelQueueManager\Repository\QueueConfigRepository;

class ShowJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue-manager:show-jobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show all available jobs';

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
        $queueConfigs = QueueConfigRepository::findAll();
        $table = [];
        foreach ($queueConfigs as $queueConfig) {

            $reflectionJob = new \ReflectionClass($queueConfig->class_name);

            $props = $this->getProps($reflectionJob);

            $configuration = [
                'Active: ' . ($queueConfig->active ?? 0),
                'Schedulable: ' . ($queueConfig->schedulable ?? 0),
                'Max Instances: ' . ($queueConfig->max_instances ?? 1),
                'Connection: ' . ($queueConfig->connection ?? 'default'),
            ];

            $table [] = [
                'Name' => $queueConfig->name,
                'Description' => $this->getDesc($reflectionJob),
                'Params' => implode(', ', $props),
                'Configuration' => implode(', ', $configuration)
            ];
        }

        $this->table(array_keys($table[0]), $table);
        $this->info("\nUsage:");
        $this->comment("php artisan queue-manager:generate-queue job_name param1=1,param2=0\n");

    }

    protected function getProps(\ReflectionClass $reflectionJob)
    {
        $props = $reflectionJob->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);
        $reservedProps = $this->getReservedProps();
        $returnProps = [];
        foreach ($props as $prop) {

            if (in_array($prop->name, $reservedProps)) {
                continue;
            }

            $returnProps [] = $prop->name;
        }

        return $returnProps;
    }

    protected function getReservedProps()
    {
        return [
            'connection',
            'delay',
            'job',
            'queue',
            'uid',
        ];
    }

    protected function getDesc(\ReflectionClass $reflectionJob)
    {
        $comment = $reflectionJob->getDocComment();

        $atPosition = strpos($comment, '@');
        if ($atPosition === false) {
            $atPosition = strlen($comment);
        }

        $comment = trim(str_replace(array('/', '*'), '', substr($comment, 0, $atPosition)));

        return $comment;
    }
}
