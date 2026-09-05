<?php

use config\DataBase;
use database\Migrator;
use Dotenv\Dotenv;

require 'vendor/autoload.php';

// Same env-file detection as index.php, kept in sync on purpose so migrations
// always run against the same database the app itself would connect to.
$env = getenv('APP_ENV') ?: 'local';
switch ($env) {
    case 'production':
        $envFile = '.env.prod';
        break;
    case 'development':
        $envFile = '.env.dev';
        break;
    default:
        $envFile = '.env.local';
        break;
}

$dotenv = Dotenv::createImmutable(__DIR__, $envFile);
$dotenv->load();

$bdd = (new DataBase())->getBdd();
$migrator = new Migrator($bdd, __DIR__ . '/database/migrations');

$command = $argv[1] ?? null;

switch ($command) {
    case 'up':
        $migrator->up();
        break;
    case 'down':
        $steps = isset($argv[2]) ? (int) $argv[2] : 1;
        $migrator->down($steps);
        break;
    case 'status':
        $migrator->status();
        break;
    default:
        echo "Usage: php migrate.php [up|down [steps]|status]\n";
        exit(1);
}
