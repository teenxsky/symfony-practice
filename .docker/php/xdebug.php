<?php

declare (strict_types = 1);

const SOURCE_PATH = __DIR__ . '/xdebug.ini';
const TARGET_PATH = '/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini';

$command = $argv[1];
if ($command === 'enable') {
    copy(SOURCE_PATH, TARGET_PATH);
} elseif ($command === 'disable') {
    @unlink(TARGET_PATH);
} elseif ($command === 'status') {
    $enabled          = file_exists(TARGET_PATH);
    [$status, $color] = $enabled ? ['enabled', "\e[32m"] : ['disabled', "\e[31m"];
    echo "{$color}Xdebug is {$status}\e[0m\n";
} else {
    echo "Unknown command\n";
    exit(1);
}
