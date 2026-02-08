<?php

namespace tests\controllers;

use tests\BaseControllerTestCase;
use controllers\GameController;
use models\Game;
use models\User;
use models\GoogleUser;

class GameControllerTest extends BaseControllerTestCase
{
    private function createController(array $mockOverrides = []): GameController
    {
        $defaults = [
            'game' => $this->createMock(Game::class),
            'user' => $this->createMock(User::class),
            'googleUser' => $this->createMock(GoogleUser::class),
        ];
        $mocks = array_merge($defaults, $mockOverrides);

        return $this->createControllerWithMocks(GameController::class, $mocks);
    }

    // ─── getGameUser ────────────────────────────────────────────

    public function testGetGameUserNoPostData(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'getGameUser');
        $this->assertNotNull($result);
        $this->assertEquals('Cant access this', $result['message']);
    }

    public function testGetGameUserNoBearer(): void
    {
        $controller = $this->createController();
        $_POST['userId'] = '1';
        $_POST['game'] = 'League of Legends';

        $result = $this->captureJsonOutput($controller, 'getGameUser');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testGetGameUserInvalidToken(): void
    {
        $this->setBearerToken('bad_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'good_token',
        ]);

        $controller = $this->createController(['googleUser' => $googleUserMock]);
        $_POST['userId'] = '1';
        $_POST['game'] = 'League of Legends';

        $result = $this->captureJsonOutput($controller, 'getGameUser');
        $this->assertNotNull($result);
        $this->assertFalse($result['success']);
    }

    public function testGetGameUserWrongGame(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser([
            'user_game' => 'Valorant',
        ]));

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
        ]);

        $_POST['userId'] = '1';
        $_POST['game'] = 'League of Legends';

        $result = $this->captureJsonOutput($controller, 'getGameUser');
        $this->assertNotNull($result);
        $this->assertStringContainsString('Game not available', $result['message']);
    }

    public function testGetGameUserAlreadyPlayed(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser([
            'user_lastCompletedGame' => date('Y-m-d'),
        ]));

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
        ]);

        $_POST['userId'] = '1';
        $_POST['game'] = 'League of Legends';

        $result = $this->captureJsonOutput($controller, 'getGameUser');
        $this->assertNotNull($result);
        $this->assertEquals('Already played', $result['message']);
    }

    public function testGetGameUserSuccessWithHints(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser([
            'user_lastCompletedGame' => null,
        ]));

        $gameMock = $this->createMock(Game::class);
        $gameMock->method('getGameUser')->willReturn($this->fakeGameCharacter());

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
            'game' => $gameMock,
        ]);

        $_POST['userId'] = '1';
        $_POST['game'] = 'League of Legends';
        $_POST['tryCount'] = 1;

        $result = $this->captureJsonOutput($controller, 'getGameUser');
        $this->assertNotNull($result);
        $this->assertEquals('Success', $result['message']);
        $this->assertArrayHasKey('hints', $result);
        $this->assertArrayHasKey('game_main', $result['hints']);
    }

    public function testGetGameUserNoGameToday(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser());

        $gameMock = $this->createMock(Game::class);
        $gameMock->method('getGameUser')->willReturn(null);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
            'game' => $gameMock,
        ]);

        $_POST['userId'] = '1';
        $_POST['game'] = 'League of Legends';
        $_POST['tryCount'] = 1;

        $result = $this->captureJsonOutput($controller, 'getGameUser');
        $this->assertNotNull($result);
        $this->assertEquals('No game today', $result['message']);
    }

    // ─── submitGuess ────────────────────────────────────────────

    public function testSubmitGuessNoParam(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'submitGuess');
        $this->assertNotNull($result);
        $this->assertEquals('Invalid request', $result['message']);
    }

    public function testSubmitGuessNoBearer(): void
    {
        $controller = $this->createController();

        $_POST['param'] = json_encode([
            'game' => 'League of Legends',
            'guess' => 'Ahri',
            'tryCount' => 1,
            'userId' => 1,
        ]);

        $result = $this->captureJsonOutput($controller, 'submitGuess');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testSubmitGuessCorrectAnswer(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser([
            'user_lastCompletedGame' => null,
        ]));
        $userMock->method('addCurrency')->willReturn(true);
        $userMock->method('markGameAsPlayed')->willReturn(true);

        $gameMock = $this->createMock(Game::class);
        $gameMock->method('getGameUser')->willReturn($this->fakeGameCharacter([
            'game_username' => 'Ahri',
        ]));
        $gameMock->method('updateTotalCompletedGame')->willReturn(true);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
            'game' => $gameMock,
        ]);

        $_POST['param'] = json_encode((object)[
            'game' => 'League of Legends',
            'guess' => 'Ahri',
            'tryCount' => 1,
            'userId' => 1,
        ]);

        $result = $this->captureJsonOutput($controller, 'submitGuess');
        $this->assertNotNull($result);
        $this->assertEquals('Correct', $result['message']);
        $this->assertArrayHasKey('reward', $result);
    }

    public function testSubmitGuessIncorrectAnswer(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser([
            'user_lastCompletedGame' => null,
        ]));

        $gameMock = $this->createMock(Game::class);
        $gameMock->method('getGameUser')->willReturn($this->fakeGameCharacter([
            'game_username' => 'Ahri',
        ]));

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
            'game' => $gameMock,
        ]);

        $_POST['param'] = json_encode((object)[
            'game' => 'League of Legends',
            'guess' => 'Lux',
            'tryCount' => 1,
            'userId' => 1,
        ]);

        $result = $this->captureJsonOutput($controller, 'submitGuess');
        $this->assertNotNull($result);
        $this->assertContains($result['message'], ['Incorrect', 'Close']);
    }

    public function testSubmitGuessGameOver(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser([
            'user_lastCompletedGame' => null,
        ]));
        $userMock->method('markGameAsPlayed')->willReturn(true);

        $gameMock = $this->createMock(Game::class);
        $gameMock->method('getGameUser')->willReturn($this->fakeGameCharacter([
            'game_username' => 'Ahri',
        ]));
        $gameMock->method('updateTotalCompletedGame')->willReturn(true);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
            'game' => $gameMock,
        ]);

        $_POST['param'] = json_encode((object)[
            'game' => 'League of Legends',
            'guess' => 'Zed',
            'tryCount' => 4,
            'userId' => 1,
        ]);

        $result = $this->captureJsonOutput($controller, 'submitGuess');
        $this->assertNotNull($result);
        $this->assertEquals('Game Over', $result['message']);
    }

    public function testSubmitGuessAlreadyPlayed(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser([
            'user_lastCompletedGame' => date('Y-m-d'),
        ]));

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
        ]);

        $_POST['param'] = json_encode((object)[
            'game' => 'League of Legends',
            'guess' => 'Ahri',
            'tryCount' => 1,
            'userId' => 1,
        ]);

        $result = $this->captureJsonOutput($controller, 'submitGuess');
        $this->assertNotNull($result);
        $this->assertEquals('Already played', $result['message']);
    }

    // ─── Pure functions ─────────────────────────────────────────

    public function testNormalizeGuess(): void
    {
        $controller = $this->createController();

        $this->assertEquals('ahri', $controller->normalizeGuess('Ahri'));
        $this->assertEquals('ahri', $controller->normalizeGuess('  A.h.r.i  '));
        $this->assertEquals('missmage', $controller->normalizeGuess('Miss Mage'));
    }

    public function testSplitIntoWords(): void
    {
        $controller = $this->createController();

        $result = $controller->splitIntoWords('Miss Fortune');
        $this->assertContains('miss', $result);
        $this->assertContains('fortune', $result);
    }

    public function testSplitIntoWordsCamelCase(): void
    {
        $controller = $this->createController();

        $result = $controller->splitIntoWords('AurelionSol');
        $this->assertNotEmpty($result);
    }

    public function testGetHint(): void
    {
        $controller = $this->createController();
        $gameUser = $this->fakeGameCharacter();

        $hint1 = $controller->getHint($gameUser, 1);
        $this->assertArrayHasKey('affiliation', $hint1);

        $hint2 = $controller->getHint($gameUser, 2);
        $this->assertArrayHasKey('gender', $hint2);

        $hint3 = $controller->getHint($gameUser, 3);
        $this->assertArrayHasKey('guess', $hint3);

        $hint4 = $controller->getHint($gameUser, 4);
        $this->assertEmpty($hint4);
    }

    // ─── Getters/Setters ───────────────────────────────────────

    public function testGetSetUserId(): void
    {
        $controller = $this->createController();
        $controller->setUserId(42);
        $this->assertEquals(42, $controller->getUserId());
    }

    public function testGetSetNumberTry(): void
    {
        $controller = $this->createController();
        $controller->setNumberTry(3);
        $this->assertEquals(3, $controller->getNumberTry());
    }

    public function testGetSetGamePlayedByUser(): void
    {
        $controller = $this->createController();
        $controller->setGamePlayedByUser('League of Legends');
        $this->assertEquals('League of Legends', $controller->getGamePlayedByUser());
    }
}
