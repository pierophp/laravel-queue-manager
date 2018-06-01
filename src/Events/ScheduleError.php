<?php

namespace LaravelQueueManager\Events;

class ScheduleError
{
    public $message;

    public $context;

    public function __construct($message, $context = [])
    {
        $this->message = $message;

        $this->context = $context;
    }

}