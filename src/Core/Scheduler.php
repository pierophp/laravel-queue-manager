<?php

namespace LaravelQueueManager\Core;

use Illuminate\Console\Scheduling\Schedule;

class Scheduler
{
    public function schedule(Schedule $schedule)
    {
        $schedulableQueues = QueueConfigRepository::findSchedulables();

        foreach ($schedulableQueues as $schedulableQueue) {

            $scheduleConfig = json_decode($schedulableQueue->schedule_config);

            if (!isset($scheduleConfig->method)) {
                // @todo Implement event
                \BusinessLogger::error('queue_schedule_error', 'Method not configured for ' . $schedulableQueue->name);
                continue;
            }

            $method = $scheduleConfig->method;

            $params = '';
            if (isset($scheduleConfig->params)) {
                $params = $scheduleConfig->params;
            }

            $schedule->call(function () use ($schedulableQueue) {

                $className = $schedulableQueue->class_name;

                $job = (new $className());
                $job->dispatch();

            })->$method($params);
        }
    }

}