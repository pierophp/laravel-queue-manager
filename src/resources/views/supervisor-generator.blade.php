@foreach($configs as $config)
[program:laravel-worker-{{$config->name}}]
process_name=%(program_name)s_%(process_num)02d
command=php {{base_path('artisan')}} queue:work --queue={{$config->name}} --sleep={{ (int)$config->delay }} --tries={{ $config->max_attempts ? $config->max_attempts : 5 }} --timeout={{ $config->timeout ? $config->timeout : 0 }}
autostart=true
autorestart=true
user={{config('queue_manager.supervisor_user')}}
numprocs={{ $config->active ? ($config->max_instances ? $config->max_instances : 1) : 0 }}
redirect_stderr=true
stdout_logfile={{storage_path('logs/worker.log')}}
stopwaitsecs=600
stopsignal=INT

@endforeach
