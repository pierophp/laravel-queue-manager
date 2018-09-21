# Laravel Queue Manager

The Laravel Queue Manager, manage the queue process worker.

It uses [Supervisor](http://supervisord.org/) as Process Control System.

It also has a scheduler system built-in.

## Installation

### Composer

```bash
$ composer require pierophp/laravel-queue-manager
```

### Publish

```bash
$ php artisan vendor:publish --provider="LaravelQueueManager\Providers\LaravelQueueManagerServiceProvider"
```

### Running the migration

If you have MySQL version before 5.7, change in the migration the field "schedule_config" from "json" to "text".

```bash
$ php artisan migrate
```

### Adding the provider

You need to add the provider at the file config/app.php

```
LaravelQueueManager\Providers\LaravelQueueManagerServiceProvider::class,
```

## Configuration

### Generating a job

You need generate a class that extends LaravelQueueManager\AbstractJob.

It's necessary to implement 2 methods:

| Method    | Description                  |
| --------- | ---------------------------- |
| getName() | The name of the job          |
| execute() | The code of the job yourself |

### Dispatching a new job

You need create a new instance of your job and call the dispatch() method.

Or use the CLI:

```bash
$ php artisan queue-manager:generate-queue queue_name
```

You can set optional params too:

```bash
$ php artisan queue-manager:generate-queue queue_name foo=test,bar=test
```

### Database

To the job work correctly, it is necessary generate a row in the queue_config table.

| Field           | Description                                                                                                                                                                                        |
| --------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| name            | It's the same of the return of the getName() method.                                                                                                                                               |
| class_name      | The full path with namespace of your job class (\App\Jobs\TestJob)                                                                                                                                 |
| active          | If the job is active or not                                                                                                                                                                        |
| schedulable     | If the job is schedulable or not                                                                                                                                                                   |
| schedule_config | A JSON config of the schedule. {"method" : "The schedule methods from laravel", "params": "The params to the schedule method (optional)", "props": [ { "my_job_prop": 1 }, { "my_job_prop": 2 } ]} |
| max_attemps     | The max attempts of the queue                                                                                                                                                                      |
| max_instances   | The max parallel instances of the queue                                                                                                                                                            |
| timeout         | The timeout of the queue                                                                                                                                                                           |
| delay           | The delay to the next execution (Not implemented yet)                                                                                                                                              |
| connection      | The connection name of the queue provider. (If null = default)                                                                                                                                     |
| aggregator      | The aggregator is used to group the queues in a report                                                                                                                                             |
| config          | The config is used to configure a dynamic functionality. Example json below                                                                                                                        |

##### Config field example

```
{
  "nextQueues":{
    "onError":[
      {
        "url":"url/test",
        "data":{
          "param":value
        },
        "name":"QUEUE_NAME",
        "method":"POST",
        "service":"SERVICE",
        "delay_seconds":1
      },
      {
        "url":"url/test",
        "data":{
          "param":value
        },
        "name":"QUEUE_NAME",
        "method":"GET",
        "service":"SERVICE",
        "delay_seconds":1
      }
    ],
    "onSuccess":[
      {
        "url":"url/test",
        "data":{
          "param":value
        },
        "name":"QUEUE_NAME",
        "method":"POST",
        "service":"SERVICE",
        "delay_seconds":1
      }
    ]
  }
}
```

### Config

At the queue_manager.php config file you can configure:

| Field                     | Description                                                                              | Default                                   |
| ------------------------- | ---------------------------------------------------------------------------------------- | ----------------------------------------- |
| artisan_path              | The artisan path                                                                         | base_path('artisan')                      |
| log_path                  | The log path                                                                             | storage_path('logs/worker.log')           |
| supervisor_config_file    | The supervisor config file                                                               | /etc/supervisor/conf.d/laravel-queue.conf |
| supervisor_bin            | The supervisor bin path                                                                  | /usr/bin/supervisorctl                    |
| supervisor_user           | The supervisor user                                                                      | docker                                    |
| supervisor_update_timeout | The supervisor update timeout to gracefully stop the process when a configuration change | 600                                       |
| execute_as_api            | Enable the queue as API mode                                                             | false                                     |
| api_url                   | URL to run the queue as API mode                                                         | http://127.0.0.1/queue/process            |
| fallback_connections      | Array of fallback connections when first provider fails to dispatch                      | []                                        |

### Showing all available jobs

```bash
$ php artisan queue-manager:show-jobs
```

### Getting error events

You need add to your AppServiceProvider and log as you like:

```php
$this->app['events']->listen(\LaravelQueueManager\Events\ScheduleError::class, function(\LaravelQueueManager\Events\ScheduleError $error){
    // my code
});

$this->app['events']->listen(\LaravelQueueManager\Events\DispatchQueueError::class, function(\LaravelQueueManager\Events\DispatchQueueError $error){
    // my code
});
```

## Deploying

### Supervisor config

You need configure a cron to run as root every minute to generate the supervisor config

```bash
$ php artisan queue-manager:generate-config
```

### Scheduler

You need configure a cron to run every minute to generate the scheduler

```bash
$ php artisan schedule:run
```

### Queue Restart

Every time you change the PHP code, it's necessary to restart the queues. Put this at your deploy script.

```bash
$ php artisan queue:restart
```

## API Mode

### Introduction

To easily scale your jobs machine, you can run the queues in API mode. An API is much more easy to apply auto-scale.

### Configuration

In your route configuration file add:

```php
$api->post('queue/process', 'LaravelQueueManager\Http\Controllers\QueueController@process');
```

Edit in your "queue_manager.php" config file the execute_as_api and api_url options.
