<?php

namespace tests\controllers;

use tests\BaseControllerTestCase;
use controllers\RiotController;
use models\LeagueOfLegends;
use models\Valorant;
use models\User;
use models\GoogleUser;
use models\UserLookingFor;
use models\Items;
use models\RatingGames;

class RiotControllerTest extends BaseControllerTestCase
{
    private function createController(array $mockOverrides = []): RiotController
    {
        $defaults = [
            'leagueOfLegends' => $this->createMock(LeagueOfLegends::class),
            'user'            => $this->createMock(User::class),
            'valorant'        => $this->createMock(Valorant::class),
            'googleUser'      => $this->createMock(GoogleUser::class),
            'userlookingfor'  => $this->createMock(UserLookingFor::class),
            'items'           => $this->createMock(Items::class),
            'rating'          => $this->createMock(RatingGames::class),
        ];
        $mocks = array_merge($defaults, $mockOverrides);

        return $this->createControllerWithMocks(RiotController::class, $mocks);
    }

    // ─── riotAccountPhone ───────────────────────────────────────

    public function testRiotAccountPhoneNoCode(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'riotAccountPhone');
        $this->assertNotNull($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('Error', $result['message']);
    }

    public function testRiotAccountPhoneWithCode(): void
    {
        $_GET['code'] = 'test_auth_code';
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'riotAccountPhone');
        $this->assertNotNull($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('code', $result);
        $this->assertEquals('Success', $result['message']);
        $this->assertEquals('test_auth_code', $result['code']);
    }

    // ─── RiotCodePhone ──────────────────────────────────────────

    public function testRiotCodePhoneNoData(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'RiotCodePhone');
        $this->assertNotNull($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('Error', $result['message']);
    }

    // ─── getGameStatusLoL ───────────────────────────────────────

    public function testGetGameStatusLoLNoFriendId(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'getGameStatusLoL');
        $this->assertNotNull($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('Wrong request', $result['message']);
    }

    public function testGetGameStatusLoLNotVerified(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn(array_merge($this->fakeUser(), ['lol_verified' => 0]));

        $controller = $this->createController(['user' => $userMock]);
        $_POST['friendId'] = '2';

        $result = $this->captureJsonOutput($controller, 'getGameStatusLoL');
        // When lol_verified is 0, controller produces no output (genuine code gap)
        $this->assertNull($result);
    }

    // ─── checkIfUsersPlayedTogether ─────────────────────────────

    public function testCheckIfUsersPlayedTogetherNoParams(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'checkIfUsersPlayedTogether');
        $this->assertNotNull($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('Invalid request', $result['message']);
    }

    public function testCheckIfUsersPlayedTogetherNoAuth(): void
    {
        $controller = $this->createController();
        $_POST['friendId'] = '2';
        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'checkIfUsersPlayedTogether');
        $this->assertNotNull($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('Unauthorized', $result['message']);
    }

    public function testCheckIfUsersPlayedTogetherInvalidToken(): void
    {
        $this->setBearerToken('wrong_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'different_token',
        ]);

        $controller = $this->createController(['googleUser' => $googleUserMock]);
        $_POST['friendId'] = '2';
        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'checkIfUsersPlayedTogether');
        $this->assertNotNull($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('Invalid token', $result['message']);
    }

    // ─── connectRiotMobile ──────────────────────────────────────

    public function testConnectRiotMobileNoPhoneData(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'connectRiotMobile');
        $this->assertNotNull($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('Missing phone data', $result['message']);
    }

    // ─── getChampionNameById ────────────────────────────────────

    public function testGetChampionNameByIdFound(): void
    {
        $controller = $this->createController();
        $championData = [
            'Ahri' => ['key' => '103', 'name' => 'Ahri'],
            'Jinx' => ['key' => '222', 'name' => 'Jinx'],
        ];
        $result = $controller->getChampionNameById(222, $championData);
        $this->assertEquals('Jinx', $result);
    }

    public function testGetChampionNameByIdNotFound(): void
    {
        $controller = $this->createController();
        $championData = [
            'Ahri' => ['key' => '103', 'name' => 'Ahri'],
        ];
        $result = $controller->getChampionNameById(999, $championData);
        $this->assertNull($result);
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
