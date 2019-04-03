@foreach($configs as $config)
[program:laravel-worker-{{$config->name}}]
process_name=%(program_name)s_%(process_num)02d
command=php {{config('queue_manager.artisan_path')}} queue-manager:work --queue={{$config->name}} --sleep={{ (int)$config->delay ? $config->delay : 1 }} --tries={{ $config->max_attempts ? $config->max_attempts : 5 }} --timeout={{ $config->timeout ? $config->timeout : 0 }} {{ $config->connection == 'default' ? '' : $config->connection }}
autostart=true
autorestart=true
user={{config('queue_manager.supervisor_user')}}
numprocs={{ $config->active ? ($config->max_instances ? $config->max_instances : 1) : 0 }}
redirect_stderr=true
stdout_logfile={{config('queue_manager.log_path')}}
stopwaitsecs={{ $config->timeout ? $config->timeout : 600 }}
stopsignal=TERM

@endforeach

@foreach($fallbackConnections as $fallbackConnection)
[program:laravel-worker-fallback-{{$fallbackConnection}}]
process_name=%(program_name)s_%(process_num)02d
command=php {{config('queue_manager.artisan_path')}} queue-manager:work --sleep=60 --tries=5 --timeout=600 {{ $fallbackConnection }}
autostart=true
autorestart=true
user={{config('queue_manager.supervisor_user')}}
numprocs=1
redirect_stderr=true
stdout_logfile={{config('queue_manager.log_path')}}
stopwaitsecs=600
stopsignal=TERM

@endforeach
