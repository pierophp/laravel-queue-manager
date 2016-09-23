<?php

namespace LaravelQueueManager;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class AbstractJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $uid;

    abstract function execute();

    abstract function getName();

    private function preventKillProcess()
    {
        pcntl_signal(SIGINT, function () {
        });
    }

    final public function handle()
    {
        $this->preventKillProcess();
        $this->execute();
    }

    final public function dispatch()
    {
        $this->uid = uniqid();

        dispatch($this->onQueue($this->getName()));
    }

    /**
     * @return mixed
     */
    public function getUid()
    {
        return $this->uid;
    }


}