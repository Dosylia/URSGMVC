<?php

use config\DataBase;
use database\Migrator;
use Dotenv\Dotenv;

require 'vendor/autoload.php';


$env = getenv('APP_ENV');
if ($env !== false) {
    $envFile = match ($env) {
        'production' => '.env.prod',
        'development' => '.env.dev',
        default => '.env.local',
    };
} else {
    $envFile = null;
    foreach (['.env.prod', '.env.dev', '.env.local'] as $candidate) {
        if (file_exists(__DIR__ . '/' . $candidate)) {
            $envFile = $candidate;
            break;
        }
    }

    if ($envFile === null) {
        fwrite(STDERR, "No APP_ENV set and no .env.prod/.env.dev/.env.local file found in " . __DIR__ . ". Set APP_ENV or add one of those files.\n");
        exit(1);
    }
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
