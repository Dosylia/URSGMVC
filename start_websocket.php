<?php

/**
 * WebSocket server bootstrap script.
 * Run from CLI: php start_websocket.php
 */

use Dotenv\Dotenv;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use controllers\WebSocketController;

require __DIR__ . '/vendor/autoload.php';

// Load environment
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

function loadClass($class)
{
    $classe = str_replace('\\', '/', $class);
    require_once $classe . '.php';
}
spl_autoload_register('loadClass');

$config = require __DIR__ . '/config/websocket.php';

$wsController = new WebSocketController();

$server = IoServer::factory(
    new HttpServer(
        new WsServer($wsController)
    ),
    $config['port']
);

echo "WebSocket server running on ws://{$config['host']}:{$config['port']}\n";
$server->run();
