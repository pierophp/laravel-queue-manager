<?php

namespace LaravelQueueManager;

use Illuminate\Bus\Dispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Queue\SerializesModels;
use LaravelQueueManager\Repository\QueueConfigRepository;
use LaravelQueueManager\Events\DispatchQueueError;

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

    final public function dispatch($delaySeconds = 0)
    {
        $this->uid = uniqid();

        $connectionName = $this->getConectionName();

        $runningConnection = config('queue.default');
        if ($connectionName != 'default') {
            $runningConnection = $connectionName; 
            $this->onConnection($connectionName);
        }

        try {
            $dispatcher = app(Dispatcher::class);

            if ($delaySeconds) {
                $this->delay = now()->addSeconds($delaySeconds);
            }

            return $dispatcher->dispatch($this);
        } catch (\Throwable $e) {
            
            event(new DispatchQueueError($e->getMessage(), ['action' => 'queue_dispatch_error']));

            $fallbackConnections = config('queue_manager.fallback_connections');
            foreach ($fallbackConnections as $fallbackConnection) {
                if ($fallbackConnection === $runningConnection) {
                    continue;
                }

                try {
                    $this->onConnection($fallbackConnection);
                    $dispatcher = app(Dispatcher::class);
                    return $dispatcher->dispatch($this);
                } catch (\Throwable $e) {}
            }

            throw $e;
        }
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
