<?php

namespace LaravelQueueManager\Events;

class AfterQueueError
{
    public $message;

    public $context;

    public function __construct(string $message, array $context = [])
    {
        $this->message = $message;

        $this->context = $context;
    }
}