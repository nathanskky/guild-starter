<?php declare(strict_types=1);

/**
 * Configuration for Illuminate Database/Eloquent ORM.
 */

return [
    'driver' => 'mysql',
    'host' => $_ENV['MYSQL_HOST'],
    'port' => $_ENV['MYSQL_PORT'],
    'database' => $_ENV['MYSQL_DATABASE'],
    'username' => $_ENV['MYSQL_USERNAME'],
    'password' => $_ENV['MYSQL_PASSWORD'],
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
];
