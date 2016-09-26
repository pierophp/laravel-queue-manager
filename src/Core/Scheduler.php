<?php

namespace LaravelQueueManager\Core;

use Illuminate\Console\Scheduling\Schedule;
use LaravelQueueManager\Events\ScheduleError;
use LaravelQueueManager\Repository\QueueConfigRepository;

class Scheduler
{
    public static function schedule(Schedule $schedule)
    {
        $schedulableQueues = QueueConfigRepository::findSchedulables();

        foreach ($schedulableQueues as $schedulableQueue) {

            $scheduleConfig = json_decode($schedulableQueue->schedule_config);

            if (!isset($scheduleConfig->method)) {
                event(new ScheduleError('Method not configured for ' . $schedulableQueue->name, ['command' => 'reread']));
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