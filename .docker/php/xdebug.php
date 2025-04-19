<?php

declare (strict_types = 1);

$configPath = '/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini';

$command = $argv[1];
if ($command === 'enable') {
    copy(__DIR__ . '/xdebug.ini', $configPath);
} elseif ($command === 'disable') {
    @unlink($configPath);
} elseif ($command === 'status') {
    $enabled          = in_array('xdebug', get_loaded_extensions(), true);
    [$status, $color] = $enabled ? ['enabled', "\e[32m"] : ['disabled', "\e[31m"];
    echo "{$color}Xdebug is {$status}\e[0m\n";
} else {
    echo "Unknown command\n";
    exit(1);
}
