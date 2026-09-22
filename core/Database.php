<?php
/**
 * MSEC SMART STOCK - Database Connection & Abstraction Layer
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/database/DatabaseSeeder.php';

class Database {
    private static ?PDO $instance = null;
    private static string $driver = 'sqlite';

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $config = require dirname(__DIR__) . '/config/database.php';
            self::$driver = $config['default'] ?? 'sqlite';
            $conn = $config['connections'][self::$driver];

            try {
                if (self::$driver === 'sqlite') {
                    $dbFile = $conn['database'];
                    $isNew = !file_exists($dbFile) || filesize($dbFile) === 0;
                    self::$instance = new PDO("sqlite:" . $dbFile, null, null, $conn['options']);
                    if ($isNew) {
                        DatabaseSeeder::run(self::$instance, 'sqlite');
                    }
                } else {
                    $dsn = "mysql:host={$conn['host']};port={$conn['port']};dbname={$conn['database']};charset={$conn['charset']}";
                    self::$instance = new PDO($dsn, $conn['username'], $conn['password'], $conn['options']);
                }
            } catch (PDOException $e) {
                // If MySQL fails, fallback to SQLite seamlessly
                if (self::$driver === 'mysql') {
                    $sqliteConn = $config['connections']['sqlite'];
                    $dbFile = $sqliteConn['database'];
                    $isNew = !file_exists($dbFile) || filesize($dbFile) === 0;
                    self::$instance = new PDO("sqlite:" . $dbFile, null, null, $sqliteConn['options']);
                    self::$driver = 'sqlite';
                    if ($isNew) {
                        DatabaseSeeder::run(self::$instance, 'sqlite');
                    }
                } else {
                    die("Database Connection Error: " . htmlspecialchars($e->getMessage()));
                }
            }
        }
        return self::$instance;
    }

    public static function getDriver(): string {
        return self::$driver;
    }
}
