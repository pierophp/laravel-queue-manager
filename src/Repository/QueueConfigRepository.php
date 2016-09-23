<?php

namespace LaravelQueueManager\Repository;

use LaravelQueueManager\Model\QueueConfig;

/**
 * Class QueueConfigRepository
 *
 * @package LaravelQueueManager\Repository
 */
final class QueueConfigRepository
{

    public static function findActives()
    {
        return QueueConfig::where(['active' => true])->get();
    }

    public static function findSchedulables()
    {
        return QueueConfig::where(['active' => true, 'schedulable' => true])->get();
    }
}