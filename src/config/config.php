<?php

return [
    'supervisor_config_file' => '/etc/supervisor/conf.d/laravel-queue.conf',
    'supervisor_bin' => '/usr/bin/supervisorctl',
    'supervisor_user' => 'docker',
    'supervisor_update_timeout' => 600,
];