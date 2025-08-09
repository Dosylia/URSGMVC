<?php

// Launch database
namespace config;

class DataBase
{
    private static string $server = 'localhost:3306';
    private static string $db = 'default_db_name';
    private static string $user = 'root';
    private static string $password = '';

    private \PDO $bdd;

    public function getBdd(): ?\PDO
    {
        if (!empty($_ENV['db_server'])) {
            self::$server = $_ENV['db_server'];
        }
        if (!empty($_ENV['db_name'])) {
            self::$db = $_ENV['db_name'];
        }
        if (!empty($_ENV['db_user'])) {
            self::$user = $_ENV['db_user'];
        }
        if (!empty($_ENV['db_password'])) {
            self::$password = $_ENV['db_password'];
        }
        
        try {
            $dsn = 'mysql:host=' . self::$server . ';dbname=' . self::$db . ';charset=utf8mb4';

            $this->bdd = new \PDO(
                $dsn,
                self::$user,
                self::$password,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
        } catch (\Exception $e) {
            error_log('Database connection error: ' . $e->getMessage());
            die('Error message is : ' . $e->getMessage());
        }

        return $this->bdd;
    }
}

// Test database
// $bdd = new DataBase();
// var_dump($bdd -> getBdd());







