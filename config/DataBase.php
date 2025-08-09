<?php

// Launch database
namespace config;

class DataBase
{
    private string $server;
    private string $db;
    private string $user;
    private string $password;
    private string $port;

    private \PDO $bdd;

    public function __construct()
    {
        $this->server   = $_ENV['DB_SERVER'];
        $this->db       = $_ENV['DB_NAME'];
        $this->user     = $_ENV['DB_USER'];
        $this->password = $_ENV['DB_PASSWORD'];
        $this->port     = $_ENV['DB_PORT'];
    }

    public function getBdd(): ?\PDO
    {
        try {
            $this->bdd = new \PDO(
                "mysql:host={$this->server};dbname={$this->db};port={$this->port};charset=utf8mb4",
                $this->user,
                $this->password,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
        } catch (\Exception $e) {
            die('Database connection error: ' . $e->getMessage());
        }

        return $this->bdd;
    }
}

// Test database
// $bdd = new DataBase();
// var_dump($bdd -> getBdd());







