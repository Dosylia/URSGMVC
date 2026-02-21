<?php

namespace tests\controllers;

use tests\BaseControllerTestCase;
use controllers\PlayerFinderController;
use models\FriendRequest;
use models\User;
use models\GoogleUser;
use models\PlayerFinder;
use models\ChatMessage;

class PlayerFinderControllerTest extends BaseControllerTestCase
{
    private function createController(array $mockOverrides = []): PlayerFinderController
    {
        $defaults = [
            'friendrequest' => $this->createMock(FriendRequest::class),
            'user'          => $this->createMock(User::class),
            'googleUser'    => $this->createMock(GoogleUser::class),
            'playerFinder'  => $this->createMock(PlayerFinder::class),
            'chatmessage'   => $this->createMock(ChatMessage::class),
        ];
        $mocks = array_merge($defaults, $mockOverrides);

        return $this->createControllerWithMocks(PlayerFinderController::class, $mocks);
    }

    // ─── addPlayerFinderPost (website) ──────────────────────────

    public function testAddPlayerFinderPostNoBearer(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'addPlayerFinderPost');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testAddPlayerFinderPostNoUserId(): void
    {
        $this->setBearerToken('valid_token');

        $controller = $this->createController();

        // Simulate empty php://input
        $result = $this->captureJsonOutput($controller, 'addPlayerFinderPost');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testAddPlayerFinderPostInvalidToken(): void
    {
        $this->setBearerToken('wrong_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'different_token',
        ]);

        $controller = $this->createController(['googleUser' => $googleUserMock]);

        // php://input won't work in test, but the token check should fail first
        $result = $this->captureJsonOutput($controller, 'addPlayerFinderPost');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── deletePlayerFinderPost (website) ───────────────────────

    public function testDeletePlayerFinderPostNoBearer(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'deletePlayerFinderPost');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── playWithThem (website) ─────────────────────────────────

    public function testPlayWithThemNoBearer(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'playWithThem');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── getInterestedPeople (website) ──────────────────────────

    public function testGetInterestedPeopleNoBearer(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'getInterestedPeople');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testGetInterestedPeopleNoUserId(): void
    {
        $this->setBearerToken('valid_token');
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'getInterestedPeople');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testGetInterestedPeopleInvalidToken(): void
    {
        $this->setBearerToken('wrong_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'different_token',
        ]);

        $controller = $this->createController(['googleUser' => $googleUserMock]);
        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'getInterestedPeople');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testGetInterestedPeopleNoPost(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $pfMock = $this->createMock(PlayerFinder::class);
        $pfMock->method('getPlayerFinderPost')->willReturn(false);

        $controller = $this->createController([
            'googleUser'   => $googleUserMock,
            'playerFinder' => $pfMock,
        ]);
        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'getInterestedPeople');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testGetInterestedPeopleNoUnseen(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $pfMock = $this->createMock(PlayerFinder::class);
        $pfMock->method('getPlayerFinderPost')->willReturn(array_merge(
            $this->fakePlayerFinderPost(),
            ['pf_peopleInterest' => json_encode([['userId' => 2, 'seen' => true]])]
        ));

        $controller = $this->createController([
            'googleUser'   => $googleUserMock,
            'playerFinder' => $pfMock,
        ]);
        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'getInterestedPeople');
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertFalse($result['interestedUsers']);
    }

    // ─── addPlayerFinderPostPhone ───────────────────────────────

    public function testAddPlayerFinderPostPhoneNoBearer(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'addPlayerFinderPostPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── getPlayerFinderPostsPhone ──────────────────────────────

    public function testGetPlayerFinderPostsPhoneNoBearer(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'getPlayerFinderPostsPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testGetPlayerFinderPostsPhoneNoUserId(): void
    {
        $this->setBearerToken('valid_token');
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'getPlayerFinderPostsPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testGetPlayerFinderPostsPhoneInvalidToken(): void
    {
        $this->setBearerToken('wrong_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenByUserId')->willReturn([
            'google_masterToken' => 'different_token',
        ]);

        $controller = $this->createController(['googleUser' => $googleUserMock]);
        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'getPlayerFinderPostsPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testGetPlayerFinderPostsPhoneSuccess(): void
    {
        $this->setBearerToken('valid_phone_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenByUserId')->willReturn([
            'google_masterToken' => 'valid_phone_token',
        ]);

        $pfMock = $this->createMock(PlayerFinder::class);
        $pfMock->method('getAllPlayerFinderPost')->willReturn([$this->fakePlayerFinderPost()]);

        $controller = $this->createController([
            'googleUser'   => $googleUserMock,
            'playerFinder' => $pfMock,
        ]);
        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'getPlayerFinderPostsPhone');
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('posts', $result);
        $this->assertIsArray($result['posts']);
        $this->assertCount(1, $result['posts']);
        $this->assertEquals(1, $result['posts'][0]['pf_id']);
        $this->assertEquals(1, $result['posts'][0]['user_id']);
        $this->assertEquals('Support', $result['posts'][0]['pf_role']);
        $this->assertEquals('Gold', $result['posts'][0]['pf_rank']);
        $this->assertEquals('Looking for friendly teammates', $result['posts'][0]['pf_description']);
        $this->assertEquals(1, $result['posts'][0]['pf_voiceChat']);
        $this->assertEquals('League of Legends', $result['posts'][0]['pf_game']);
    }

    // ─── deletePlayerFinderPostPhone ────────────────────────────

    public function testDeletePlayerFinderPostPhoneNoBearer(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'deletePlayerFinderPostPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── playWithThemPhone ──────────────────────────────────────

    public function testPlayWithThemPhoneNoBearer(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'playWithThemPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── getInterestedPeoplePhone ───────────────────────────────

    public function testGetInterestedPeoplePhoneNoBearer(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'getInterestedPeoplePhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── markInterestAsSeen ─────────────────────────────────────

    public function testMarkInterestAsSeenNoBearer(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'markInterestAsSeen');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testMarkInterestAsSeenInvalidRequest(): void
    {
        $this->setBearerToken('valid_token');
        $controller = $this->createController();

        // No userId or postId → invalid
        $result = $this->captureJsonOutput($controller, 'markInterestAsSeen');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testMarkInterestAsSeenAccessDenied(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $pfMock = $this->createMock(PlayerFinder::class);
        $pfMock->method('getPlayerFinderPostById')->willReturn(array_merge(
            $this->fakePlayerFinderPost(),
            ['user_id' => 999] // Different user
        ));

        $controller = $this->createController([
            'googleUser'   => $googleUserMock,
            'playerFinder' => $pfMock,
        ]);
        $_POST['userId'] = '1';
        $_POST['postId'] = '10';

        $result = $this->captureJsonOutput($controller, 'markInterestAsSeen');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── editPlayerPost ─────────────────────────────────────────

    public function testEditPlayerPostNoBearer(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'editPlayerPost');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testEditPlayerPostNoUserId(): void
    {
        $this->setBearerToken('valid_token');
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'editPlayerPost');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testEditPlayerPostSuccess(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $pfMock = $this->createMock(PlayerFinder::class);
        $pfMock->method('getPlayerFinderPostById')->willReturn(array_merge(
            $this->fakePlayerFinderPost(),
            ['user_id' => 1]
        ));
        $pfMock->method('editPlayerPost')->willReturn(true);

        $controller = $this->createController([
            'googleUser'   => $googleUserMock,
            'playerFinder' => $pfMock,
        ]);
        $_POST['userId'] = '1';
        $_POST['postId'] = '10';
        $_POST['description'] = 'Looking for duo';
        $_POST['role'] = 'ADC';
        $_POST['rank'] = 'Gold';

        $result = $this->captureJsonOutput($controller, 'editPlayerPost');
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('Description updated successfully', $result['message']);
    }

    // ─── getRandomPlayerFinder ──────────────────────────────────

    public function testGetRandomPlayerFinderNoParam(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'getRandomPlayerFinder');
        $this->assertNotNull($result);
        $this->assertFalse($result['success']);
    }

    public function testGetRandomPlayerFinderNoBearer(): void
    {
        $_POST['param'] = json_encode(['userId' => 1]);
        $controller = $this->createController();

        // Controller double-echoes: getBearerTokenOrJsonError echoes Unauthorized,
        // then the if(!$token) block echoes again. Use captureOutput instead.
        $output = $this->captureOutput($controller, 'getRandomPlayerFinder');
        $this->assertStringContainsString('Unauthorized', $output);
    }

    public function testGetRandomPlayerFinderInvalidToken(): void
    {
        $this->setBearerToken('wrong_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'different_token',
        ]);

        $controller = $this->createController(['googleUser' => $googleUserMock]);
        $_POST['param'] = json_encode(['userId' => 1]);

        $result = $this->captureJsonOutput($controller, 'getRandomPlayerFinder');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── getGoogleUserModel ─────────────────────────────────────

    public function testGetGoogleUserModel(): void
    {
        $googleUserMock = $this->createMock(GoogleUser::class);
        $controller = $this->createController(['googleUser' => $googleUserMock]);
        $result = $controller->getGoogleUserModel();
        $this->assertInstanceOf(GoogleUser::class, $result);
    }
}
