<?php

namespace LaravelQueueManager\Events;

class DispatchQueueError
{
    public $message;

    public $context;

    public function __construct($message, $context)
    {
        $this->message = $message;

        $this->context = $context;
    }

}