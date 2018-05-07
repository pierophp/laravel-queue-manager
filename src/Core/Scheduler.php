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

            $schedule->call(function () use ($schedulableQueue, $scheduleConfig) {
                $className = $schedulableQueue->class_name;

                $lockKey = 'QUEUE_LOCK_' . $schedulableQueue->class_name . '_' . date('Y-m-d-H-i');

                if (\Cache::has($lockKey)) {
                    return;
                }
                
                $expiresAt = now()->addMinutes(10);

                \Cache::put($lockKey, '1', $expiresAt);
                
                if ($scheduleConfig->props && is_array($scheduleConfig->props)) {
                    foreach($scheduleConfig->props as $prop) {
                        $job = (new $className());    
                        $job->setName($schedulableQueue->name);
                        $job->setProps($prop);
                        $job->dispatch();
                    }
                } else {
                    $job = (new $className());    
                    $job->setName($schedulableQueue->name);
                    $job->dispatch();
                }
            })->$method($params);
        }
    }

}