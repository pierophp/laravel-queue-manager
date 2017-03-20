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
    public static function findAll()
    {
        return QueueConfig::where([])->get()->orderBy('name');
    }

    public static function findActives()
    {
        return QueueConfig::where(['active' => true])->get()->orderBy('name');
    }

    public static function findSchedulables()
    {
        return QueueConfig::where(['active' => true, 'schedulable' => true])->get()->orderBy('name');
    }

    public static function findOneByName($name)
    {
        return QueueConfig::where(['name' => $name])->get()->first();
    }
}
