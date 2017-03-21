<?php

return [
    'timeout' => 60 * 30, // 30 minutes
    'artisan_path' => base_path('artisan'),
    'log_path' => storage_path('logs/worker.log'),
    'execute_as_api' => false,
    'api_url' => 'http://127.0.0.1/queue/process',
    'supervisor_config_file' => '/etc/supervisor/conf.d/laravel-queue.conf',
    'supervisor_bin' => '/usr/bin/supervisorctl',
    'supervisor_user' => 'docker',
    'supervisor_update_timeout' => 600,
];
