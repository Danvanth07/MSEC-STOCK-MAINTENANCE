<?php
/**
 * MSEC SMART STOCK - Database Configuration
 * Supports SQLite (Default, Zero-config portable) and MariaDB/MySQL
 */

return [
    'default' => 'sqlite', // 'sqlite' or 'mysql'

    'connections' => [
        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => dirname(__DIR__) . '/database/msec_stock.sqlite',
            'options'  => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => false
            ]
        ],

        'mysql' => [
            'driver'    => 'mysql',
            'host'      => '127.0.0.1',
            'port'      => 3306,
            'database'  => 'msec_stock',
            'username'  => 'root',
            'password'  => '',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options'   => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        ]
    ]
];
