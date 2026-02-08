<?php

namespace tests\controllers;

use tests\BaseControllerTestCase;
use controllers\MatchingScoreController;
use models\MatchingScore;

class MatchingScoreControllerTest extends BaseControllerTestCase
{
    private function createController(array $mockOverrides = []): MatchingScoreController
    {
        $defaults = [
            'matchingscore' => $this->createMock(MatchingScore::class),
        ];
        $mocks = array_merge($defaults, $mockOverrides);

        return $this->createControllerWithMocks(MatchingScoreController::class, $mocks);
    }

    // ─── getAlgoData ────────────────────────────────────────────

    public function testGetAlgoDataSuccess(): void
    {
        $matchingMock = $this->createMock(MatchingScore::class);
        $matchingMock->method('upsertMatching')->willReturn(true);

        $controller = $this->createController(['matchingscore' => $matchingMock]);

        $_POST['param'] = json_encode([
            (object)['user_id' => 1, 'user_matching' => 2, 'score' => 85],
            (object)['user_id' => 1, 'user_matching' => 3, 'score' => 70],
        ]);

        $result = $this->captureJsonOutput($controller, 'getAlgoData');
        $this->assertNotNull($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Success', $result[0]['message']);
        $this->assertEquals('Success', $result[1]['message']);
    }

    public function testGetAlgoDataPartialFailure(): void
    {
        $matchingMock = $this->createMock(MatchingScore::class);
        $matchingMock->method('upsertMatching')
            ->willReturnOnConsecutiveCalls(true, false);

        $controller = $this->createController(['matchingscore' => $matchingMock]);

        $_POST['param'] = json_encode([
            (object)['user_id' => 1, 'user_matching' => 2, 'score' => 85],
            (object)['user_id' => 1, 'user_matching' => 3, 'score' => 70],
        ]);

        $result = $this->captureJsonOutput($controller, 'getAlgoData');
        $this->assertNotNull($result);
        $this->assertEquals('Success', $result[0]['message']);
        $this->assertEquals('Error', $result[1]['message']);
    }

    public function testGetAlgoDataNoParam(): void
    {
        $controller = $this->createController();
        // No $_POST['param']

        $result = $this->captureJsonOutput($controller, 'getAlgoData');
        $this->assertNotNull($result);
        $this->assertEquals('Invalid request', $result['message']);
    }

    public function testGetAlgoDataEmptyArray(): void
    {
        $controller = $this->createController();

        $_POST['param'] = json_encode([]);

        $result = $this->captureJsonOutput($controller, 'getAlgoData');
        $this->assertNotNull($result);
        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testGetAlgoDataSingleEntry(): void
    {
        $matchingMock = $this->createMock(MatchingScore::class);
        $matchingMock->expects($this->once())
            ->method('upsertMatching')
            ->with(1, 2, 90)
            ->willReturn(true);

        $controller = $this->createController(['matchingscore' => $matchingMock]);

        $_POST['param'] = json_encode([
            (object)['user_id' => 1, 'user_matching' => 2, 'score' => 90],
        ]);

        $result = $this->captureJsonOutput($controller, 'getAlgoData');
        $this->assertNotNull($result);
        $this->assertCount(1, $result);
        $this->assertEquals(90, $result[0]['score']);
    }

    // ─── Getters/Setters ───────────────────────────────────────

    public function testGetSetUserMatching(): void
    {
        $controller = $this->createController();
        $this->assertNull($controller->getUserMatching());
        $controller->setUserMatching(42);
        $this->assertEquals(42, $controller->getUserMatching());
    }

    public function testGetSetUserMatched(): void
    {
        $controller = $this->createController();
        $this->assertNull($controller->getUserMatched());
        $controller->setUserMatched(99);
        $this->assertEquals(99, $controller->getUserMatched());
    }

    public function testGetSetScore(): void
    {
        $controller = $this->createController();
        $this->assertNull($controller->getScore());
        $controller->setScore(85);
        $this->assertEquals(85, $controller->getScore());
    }
}
