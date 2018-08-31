<?php

namespace LaravelQueueManager\Core;

use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use LaravelQueueManager\Events\ScheduleError;
use LaravelQueueManager\Repository\QueueConfigRepository;

class Scheduler
{
    public static function schedule(Schedule $schedule)
    {
        /** @var Carbon $dateNow */
        $dateNow = now();
        $schedulableQueues = QueueConfigRepository::findSchedulables();

        foreach ($schedulableQueues as $schedulableQueue) {

            $scheduleConfig = json_decode($schedulableQueue->schedule_config);

            try {

                if (!isset($scheduleConfig->method)) {
                    event(new ScheduleError('ScheduleError - Method not configured for ' . $schedulableQueue->name, ['queue' => $schedulableQueue->name]));
                    continue;
                }

                $method = $scheduleConfig->method;

                $params = '';
                if (isset($scheduleConfig->params)) {
                    $params = $scheduleConfig->params;
                }

                $scheduleCall = $schedule->call(function () use ($schedulableQueue, $scheduleConfig, $dateNow) {
                    $className = $schedulableQueue->class_name;

                    $lockKey = 'QUEUE_LOCK_' . $schedulableQueue->name . '_' . $dateNow->format('Y-m-d-H-i');

                    $expiresAt = now()->addMinutes(10);

                    if (!\Cache::add($lockKey, '1', $expiresAt)) {
                        return;
                    }

                    if (isset($scheduleConfig->server) && $scheduleConfig->server !== gethostname()) {
                        return;
                    }

                    if ($scheduleConfig->props && is_array($scheduleConfig->props)) {
                        foreach ($scheduleConfig->props as $prop) {
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
                });

                if (isset($scheduleConfig->avoid_overlapping) && (bool)$scheduleConfig->avoid_overlapping === true) {
                    $scheduleCall->withoutOverlapping();
                }

                $scheduleCall->$method($params);

            } catch (\Throwable $e) {
                event(new ScheduleError('Schedule Error - ' . $schedulableQueue->name . ' ' . $e->getMessage(), ['queue' => $schedulableQueue->name]));
            }
        }
    }

}
