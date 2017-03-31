<?php

namespace LaravelQueueManager;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Queue\SerializesModels;
use LaravelQueueManager\Repository\QueueConfigRepository;

abstract class AbstractJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $uid;

    private $connectionName;

    abstract function execute();

    abstract function getName();

    private function preventKillProcess()
    {
        if (php_sapi_name() !== 'cli') {
            return;
        }

        pcntl_signal(SIGINT, function () {
        });
    }

    private function reconnectDb()
    {
        if ($this->job instanceof SyncJob) {
            return;
        }

        $connections = \DB::getConnections();
        foreach ($connections as $connectionName => $connection) {
            \DB::reconnect($connectionName);
        }
    }

    final public function handle()
    {
        $this->preventKillProcess();
        $this->reconnectDb();
        $this->execute();
    }

    final public function dispatch()
    {
        $this->uid = uniqid();

        $connectionName = $this->getConectionName();

        if ($connectionName != 'default') {
            $this->onConnection($connectionName);
        }

        dispatch($this->onQueue($this->getName()));
    }

    final public function setProps($props)
    {
        foreach ($props as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * @return mixed
     */
    public function getUid()
    {
        return $this->uid;
    }

    public function setConectionName($connectionName)
    {   
        $this->connectionName = $connectionName;
    }

    public function getConectionName()
    {   
        if ($this->connectionName) {
            return $this->connectionName;
        }

        $queueConfig = QueueConfigRepository::findOneByName($this->getName());
        if (!$queueConfig) {
            return 'default';
        }

        if ($queueConfig->connection) {
            return $queueConfig->connection;
        }

        return 'default';
    }

}