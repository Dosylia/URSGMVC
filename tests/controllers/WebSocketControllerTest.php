<?php

namespace tests\controllers;

use tests\BaseControllerTestCase;
use controllers\WebSocketController;
use Ratchet\ConnectionInterface;

class WebSocketControllerTest extends BaseControllerTestCase
{
    private function createController(): WebSocketController
    {
        $ref = new \ReflectionClass(WebSocketController::class);
        $controller = $ref->newInstanceWithoutConstructor();

        // Initialize the SplObjectStorage that would be set in constructor
        $clientsProp = $ref->getProperty('clients');
        $clientsProp->setAccessible(true);
        $clientsProp->setValue($controller, new \SplObjectStorage());

        return $controller;
    }

    private function createMockConnection(int $resourceId = 1): ConnectionInterface
    {
        $conn = $this->createMock(ConnectionInterface::class);

        // resourceId is a public property in Ratchet
        $conn->resourceId = $resourceId;

        return $conn;
    }

    // ─── onOpen ─────────────────────────────────────────────────

    public function testOnOpenAddsConnection(): void
    {
        $controller = $this->createController();
        $conn = $this->createMockConnection(1);

        ob_start();
        $controller->onOpen($conn);
        $output = ob_get_clean();

        $this->assertStringContainsString('New connection', $output);
    }

    public function testOnOpenMultipleConnections(): void
    {
        $controller = $this->createController();
        $conn1 = $this->createMockConnection(1);
        $conn2 = $this->createMockConnection(2);

        ob_start();
        $controller->onOpen($conn1);
        $controller->onOpen($conn2);
        ob_get_clean();

        // Read clients property to verify count
        $ref = new \ReflectionClass($controller);
        $clientsProp = $ref->getProperty('clients');
        $clientsProp->setAccessible(true);
        $clients = $clientsProp->getValue($controller);

        $this->assertCount(2, $clients);
    }

    // ─── onMessage ──────────────────────────────────────────────

    public function testOnMessageBroadcastsToOthers(): void
    {
        $controller = $this->createController();

        $conn1 = $this->createMockConnection(1);
        $conn2 = $this->createMockConnection(2);
        $conn3 = $this->createMockConnection(3);

        // conn2 and conn3 should receive the message; conn1 should not
        $conn2->expects($this->once())->method('send')->with('Hello');
        $conn3->expects($this->once())->method('send')->with('Hello');

        ob_start();
        $controller->onOpen($conn1);
        $controller->onOpen($conn2);
        $controller->onOpen($conn3);
        $controller->onMessage($conn1, 'Hello');
        $output = ob_get_clean();

        $this->assertStringContainsString('sending message', $output);
    }

    public function testOnMessageNoOtherClients(): void
    {
        $controller = $this->createController();
        $conn = $this->createMockConnection(1);

        ob_start();
        $controller->onOpen($conn);
        $controller->onMessage($conn, 'Hello alone');
        $output = ob_get_clean();

        $this->assertStringContainsString('0 other connection', $output);
    }

    // ─── onClose ────────────────────────────────────────────────

    public function testOnCloseRemovesConnection(): void
    {
        $controller = $this->createController();
        $conn = $this->createMockConnection(1);

        ob_start();
        $controller->onOpen($conn);
        $controller->onClose($conn);
        $output = ob_get_clean();

        $this->assertStringContainsString('disconnected', $output);

        $ref = new \ReflectionClass($controller);
        $clientsProp = $ref->getProperty('clients');
        $clientsProp->setAccessible(true);
        $clients = $clientsProp->getValue($controller);

        $this->assertCount(0, $clients);
    }

    // ─── onError ────────────────────────────────────────────────

    public function testOnErrorClosesConnection(): void
    {
        $controller = $this->createController();
        $conn = $this->createMockConnection(1);

        $conn->expects($this->once())->method('close');

        ob_start();
        $controller->onOpen($conn);
        $controller->onError($conn, new \Exception('Test error'));
        $output = ob_get_clean();

        $this->assertStringContainsString('error has occurred', $output);
        $this->assertStringContainsString('Test error', $output);
    }
}
