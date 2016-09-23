# Laravel Queue Manager
The Laravel Queue Manager manage the queue process worker. 

It uses [Supervisor](http://supervisord.org/) as Process Control System.

It also has as scheduler system built in. 

## Installation

### Composer
```bash
$ composer require pierophp/laravel-queue-manager:dev-master
```

### Publish
```bash
$ php artisan vendor:publish --provider="LaravelQueueManager\Providers\LaravelQueueManagerServiceProvider"
```

### Run the migration

If you have MySQL version before 5.7, change in migration the field "schedule_config" from "json" to "text". 

```bash
$ php artisan migrate
```

### Add the provider
Add the provider at the file config/app.php
```
LaravelQueueManager\Providers\LaravelQueueManagerServiceProvider::class,
```
## Configuration

### Generating a job

Generate a class that extends LaravelQueueManager\AbstractJob.

It's necessary implement 2 methods:

| Method | Description |
| --- | --- |
| getName() | The name of the job |
| execute() | The code of the job yourself |

### Dispatching a new job
Create a new instance of your job and call the dispatch() method.

### Database

To the job works, is necessary generate a row in the queue_config table.

| Field | Description |
| --- | --- |
| name | It's the same of the return of getName() method. |
| class_name | The full path with namespace of your job class (\App\jobs\TestJob) |
| active | If the job is active or not |
| schedulable | If the job is schedulable or not |
| schedule_config | A JSON config of the schedule. {"method" : "The schedule methods from laravel", "params": "The params to the schedule method (optional)"}|
| max_attemps | The max attempts of the queue |
| max_instances | The max parallel instances of the queue |
| timeout | The timeout of the queue |
| delay | The delay to the next execution (Not implemented yet) |

### Config

At the queue_manager.php config file you can configure:

| Field | Description |
| --- | --- |
| supervisor_config_file | The supervisor config file |
| supervisor_bin | The supervisor bin path |
| supervisor_user | The supervisor user |
| supervisor_update_timeout | The supervisor update timeout to gracefully stop the process when a configuration change |

### Getting error events
Add to your AppServiceProvider e log as you like
```php
$this->app['events']->listen(\LaravelQueueManager\Events\ScheduleError::class, function(\LaravelQueueManager\Events\ScheduleError $scheduleError){
            
});
```

## Deploy

### Supervisor config
Configure a cron to run as root every minute to generate the supervisor config

```bash
$ php artisan queue_manager:generate
```

### Scheduler
Configure a cron to run every minute to generate the scheduler
```bash
$ php artisan schedule:run
```

### Queue Restart
Every time you change the PHP code, it's necessary to restart the queues. Put this at your deploy script.
```bash
$ php artisan queue:restart
```
