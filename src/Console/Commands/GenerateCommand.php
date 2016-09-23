<?php

namespace LaravelQueueManager\Console\Commands;

use LaravelQueueManager\Core\SupervisorGenerator;
use Illuminate\Console\Command;

class GenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue_manager:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the queue config on supervisor';

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
    public function handle(SupervisorGenerator $generator)
    {
        $generator->generate();
    }
}
