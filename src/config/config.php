<?php

return [
    'artisan_path' => base_path('artisan'),
    'log_path' => storage_path('logs/worker.log'), 
    'supervisor_config_file' => '/etc/supervisor/conf.d/laravel-queue.conf',
    'supervisor_bin' => '/usr/bin/supervisorctl',
    'supervisor_user' => 'docker',
    'supervisor_update_timeout' => 600,
];