<?php

/**
 * Database Configuration
 * 
 * Update with your local XAMPP or cPanel/InfinityFree MySQL credentials.
 * For InfinityFree:
 * - host: e.g. sqlXXX.infinityfree.com (found in InfinityFree control panel)
 * - database: e.g. if0_XXXXX_mydb
 * - username: e.g. if0_XXXXX
 * - password: your vPanel password
 */

return [
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'port'      => 3306,
    'database'  => 'my_api_db',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options'   => [
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
