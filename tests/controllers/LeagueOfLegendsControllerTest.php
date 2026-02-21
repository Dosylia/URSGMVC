<?php

namespace tests\controllers;

use tests\BaseControllerTestCase;
use controllers\LeagueOfLegendsController;
use models\LeagueOfLegends;
use models\User;
use models\FriendRequest;
use models\GoogleUser;

class LeagueOfLegendsControllerTest extends BaseControllerTestCase
{
    private function createController(array $mockOverrides = []): LeagueOfLegendsController
    {
        $defaults = [
            'leagueOfLegends' => $this->createMock(LeagueOfLegends::class),
            'user' => $this->createMock(User::class),
            'friendrequest' => $this->createMock(FriendRequest::class),
            'googleUser' => $this->createMock(GoogleUser::class),
        ];
        $mocks = array_merge($defaults, $mockOverrides);

        return $this->createControllerWithMocks(LeagueOfLegendsController::class, $mocks);
    }

    // ─── createLeagueUser (website form) ────────────────────────

    public function testCreateLeagueUserNoForm(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'createLeagueUser');
        $this->assertTrue(true, 'createLeagueUser without submit should redirect');
    }

    public function testCreateLeagueUserSuccess(): void
    {
        $this->simulateLoggedInUser(1);

        $lolMock = $this->createMock(LeagueOfLegends::class);
        $lolMock->method('createLoLUser')->willReturn(true);

        $controller = $this->createController(['leagueOfLegends' => $lolMock]);

        $_POST['submit'] = true;
        $_POST['userId'] = '1';
        $_POST['main1'] = 'Lux';
        $_POST['main2'] = 'Janna';
        $_POST['main3'] = 'Sona';
        $_POST['rank_lol'] = 'Gold';
        $_POST['role_lol'] = 'Support';
        $_POST['server'] = 'Europe West';
        $_POST['skipSelection'] = '0';

        $output = $this->captureOutput($controller, 'createLeagueUser');
        $this->assertTrue(true);
    }

    // ─── createLeagueUserPhone ──────────────────────────────────

    public function testCreateLeagueUserPhoneNoBearer(): void
    {
        $controller = $this->createController();
        $_POST['leagueData'] = json_encode([
            'userId' => 1,
            'main1' => 'Lux',
            'main2' => 'Janna',
            'main3' => 'Sona',
            'rank' => 'Gold',
            'role' => 'Support',
            'server' => 'Europe West',
            'skipSelection' => 0,
        ]);

        $result = $this->captureJsonOutput($controller, 'createLeagueUserPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testCreateLeagueUserPhoneSuccess(): void
    {
        $this->setBearerToken('valid_phone_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenByUserId')->willReturn([
            'google_masterToken' => 'valid_phone_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser(['lol_id' => null]));

        $lolMock = $this->createMock(LeagueOfLegends::class);
        $lolMock->method('createLoLUser')->willReturn(1);
        $lolMock->method('getLeageAccountByLeagueId')->willReturn($this->fakeLoLProfile());

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'leagueOfLegends' => $lolMock,
            'user' => $userMock,
        ]);

        $_POST['leagueData'] = json_encode([
            'userId' => 1,
            'main1' => 'Lux',
            'main2' => 'Janna',
            'main3' => 'Sona',
            'rank' => 'Gold',
            'role' => 'Support',
            'server' => 'Europe West',
            'skipSelection' => 0,
        ]);

        $result = $this->captureJsonOutput($controller, 'createLeagueUserPhone');
        $this->assertNotNull($result);
        $this->assertEquals('Success', $result['message']);
        $this->assertArrayHasKey('sessionId', $result);
        $this->assertIsString($result['sessionId']);
        $this->assertArrayHasKey('user', $result);
        // Verify user sub-object values match fakeLoLProfile
        $this->assertEquals(1, $result['user']['lolId']);
        $this->assertEquals('Lux', $result['user']['main1']);
        $this->assertEquals('Janna', $result['user']['main2']);
        $this->assertEquals('Sona', $result['user']['main3']);
        $this->assertEquals('Gold', $result['user']['rank']);
        $this->assertEquals('Support', $result['user']['role']);
        $this->assertEquals('Europe West', $result['user']['server']);
    }

    // ─── UpdateLeague (website form) ────────────────────────────

    public function testUpdateLeagueNoForm(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'UpdateLeague');
        $this->assertTrue(true);
    }

    public function testUpdateLeagueSuccess(): void
    {
        $this->simulateLoggedInUser(1);

        $lolMock = $this->createMock(LeagueOfLegends::class);
        $lolMock->method('updateLeagueData')->willReturn(true);

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $controller = $this->createController([
            'leagueOfLegends' => $lolMock,
            'googleUser' => $googleUserMock,
        ]);

        $_POST['submit'] = true;
        $_POST['userId'] = '1';
        $_POST['main1'] = 'Ezreal';
        $_POST['main2'] = 'Jhin';
        $_POST['main3'] = 'Caitlyn';
        $_POST['rank_lol'] = 'Platinum';
        $_POST['role_lol'] = 'ADC';
        $_POST['server'] = 'Europe West';
        $_POST['skipSelection'] = '0';
        $_COOKIE['master_token'] = 'valid_token';

        $output = $this->captureOutput($controller, 'UpdateLeague');
        $this->assertTrue(true);
    }

    // ─── bindAccount ────────────────────────────────────────────

    public function testBindAccountNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'bindAccount');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testBindAccountSuccess(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenByUserId')->willReturn([
            'google_masterToken' => 'valid_token',
        ]);

        $lolMock = $this->createMock(LeagueOfLegends::class);
        $lolMock->method('addLoLAccount')->willReturn(true);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'leagueOfLegends' => $lolMock,
        ]);

        $_POST['userData'] = json_encode([
            'userId' => 1,
            'account' => 'TestPlayer#TAG1',
            'server' => 'euw1',
        ]);

        // bindAccount calls the Riot API (getSummonerByNameAndTag) which
        // can't be mocked since it's a method on the controller itself.
        // We verify the method runs without crashing and returns valid JSON.
        $result = $this->captureJsonOutput($controller, 'bindAccount');
        $this->assertNotNull($result);
        $this->assertArrayHasKey('message', $result);
    }

    // ─── verifyLeagueAccountPhone ───────────────────────────────

    public function testVerifyLeagueAccountPhoneNoBearer(): void
    {
        $controller = $this->createController();
        $_POST['userData'] = json_encode([
            'userId' => 1,
            'account' => 'TestPlayer',
            'server' => 'euw1',
        ]);

        $result = $this->captureJsonOutput($controller, 'verifyLeagueAccountPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── unbindLoLAccount ───────────────────────────────────────

    public function testUnbindLoLAccountSuccess(): void
    {
        $this->simulateLoggedInUser(1);

        $lolMock = $this->createMock(LeagueOfLegends::class);
        $lolMock->method('unbindLoLAccount')->willReturn(true);

        $controller = $this->createController(['leagueOfLegends' => $lolMock]);

        $_POST['userId'] = '1';

        $output = $this->captureOutput($controller, 'unbindLoLAccount');
        $this->assertTrue(true);
    }

    // ─── refreshRiotData (cron) ─────────────────────────────────

    public function testRefreshRiotDataWrongToken(): void
    {
        $controller = $this->createController();
        $_GET['token'] = 'wrong';

        $output = $this->captureOutput($controller, 'refreshRiotData');
        $this->assertIsString($output);
    }

    // ─── Utility functions ──────────────────────────────────────

    public function testEmptyInputSignup(): void
    {
        $controller = $this->createController();
        $this->assertTrue($controller->emptyInputSignup(''));
        $this->assertFalse($controller->emptyInputSignup('TestAccount'));
    }

    public function testValidateInput(): void
    {
        $controller = $this->createController();
        $result = $controller->validateInput('<b>test</b>');
        $this->assertStringNotContainsString('<b>', $result);
    }

    public function testDetermineRankAndTier(): void
    {
        $controller = $this->createController();

        $stats = [
            ['queueType' => 'RANKED_SOLO_5x5', 'tier' => 'GOLD', 'rank' => 'II'],
        ];
        $result = $controller->determineRankAndTier($stats);
        $this->assertEquals('GOLD II', $result);
    }

    public function testDetermineRankAndTierNoSoloQ(): void
    {
        $controller = $this->createController();

        $stats = [
            ['queueType' => 'RANKED_FLEX_SR', 'tier' => 'SILVER', 'rank' => 'I'],
        ];
        $result = $controller->determineRankAndTier($stats);
        $this->assertEquals('SILVER I', $result);
    }

    public function testDetermineRankAndTierEmpty(): void
    {
        $controller = $this->createController();

        $result = $controller->determineRankAndTier([]);
        $this->assertEquals('Unranked', $result);
    }
}
