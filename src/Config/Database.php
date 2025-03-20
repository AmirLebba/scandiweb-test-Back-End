<?php

// src/Config/Database.php
namespace App\Config;

use PDO;
use PDOException;
use Dotenv\Dotenv;

class Database {
    private static $connection = null;

    public static function getConnection() {
        if (self::$connection === null) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();

            $host = $_ENV['DB_HOST'];
            $dbname = $_ENV['DB_NAME'];
            $user = $_ENV['DB_USER'];
            $pass = $_ENV['DB_PASS'];

            try {
                self::$connection = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
            } catch (PDOException $e) {
                error_log("Database connection error: " . $e->getMessage());
                throw new \RuntimeException("Database connection failed.");
            }
        }
        return self::$connection;
    }
}