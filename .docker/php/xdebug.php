<?php

declare(strict_types=1);

$targetConfigPath = '/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini';
$sourceConfigPath = __DIR__ . '/xdebug.ini';

$command = $argv[1];
if ($command === 'enable') {
    copy($sourceConfigPath, $targetConfigPath);
} elseif ($command === 'disable') {
    @unlink($targetConfigPath);
} elseif ($command === 'status') {
    $enabled          = file_exists($targetConfigPath);
    [$status, $color] = $enabled ? ['enabled', "\e[32m"] : ['disabled', "\e[31m"];
    echo "{$color}Xdebug is {$status}\e[0m\n";
} else {
    echo "Unknown command\n";
    exit(1);
}
