<?php
class Database {
    private static $pdo;
    public static function get() {
        if (!self::$pdo) {
            $c = require __DIR__ . '/../../config/config.php';
            $db = $c['db'];
            $dsn = "mysql:host={$db['host']};dbname={$db['dbname']};charset=utf8mb4";
            self::$pdo = new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        }
        return self::$pdo;
    }
}
