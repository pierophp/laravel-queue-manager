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

    protected $name;

    abstract function execute();

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
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

        return dispatch($this->onQueue($this->getName()));
    }

    final public function setProps($props)
    {
        foreach ($props as $key => $value) {
            if ($value instanceof \stdClass) {
                $value = json_encode($value);
            }
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

    public function setConnectionName($connectionName)
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
