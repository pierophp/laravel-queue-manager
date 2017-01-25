<?php

namespace LaravelQueueManager\Console\Commands;

use Illuminate\Queue\Console\WorkCommand as LaravelWorkCommand;


class WorkCommand extends LaravelWorkCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue-manager:work
                            {connection? : The name of connection}
                            {--queue= : The queue to listen on}
                            {--daemon : Run the worker in daemon mode (Deprecated)}
                            {--once : Only process the next job on the queue}
                            {--delay=0 : Amount of time to delay failed jobs}
                            {--force : Force the worker to run even in maintenance mode}
                            {--memory=128 : The memory limit in megabytes}
                            {--sleep=3 : Number of seconds to sleep when no job is available}
                            {--timeout=60 : The number of seconds a child process can run}
                            {--tries=0 : Number of times to attempt a job before logging it failed}';
}
