<?php

namespace controllers;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use traits\SecurityController;

require 'vendor/autoload.php';

class WebSocketController implements MessageComponentInterface {
    use SecurityController;

    protected $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
    }

    // Handles a new WebSocket connection
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    // Handles incoming messages
    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1;
        echo sprintf(
            'Connection %d sending message "%s" to %d other connection%s' . "\n",
            $from->resourceId,
            $msg,
            $numRecv,
            $numRecv == 1 ? '' : 's'
        );

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($msg);
            }
        }
    }

    // Handles connection close
    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    // Handles errors
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    // Starts the WebSocket server
    public function startServer() {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) &&
            $this->isConnectLf() &&
            $this->isAdmin()
        ) {
            $config = require __DIR__ . '/../config/websocket.php';

            $server = IoServer::factory(
                new HttpServer(
                    new WsServer(
                        $this // Use the current class as the WebSocket server
                    )
                ),
                $config['port']
            );

            echo "WebSocket server running on ws://{$config['host']}:{$config['port']}\n";
            $server->run();
        }
    }
}
