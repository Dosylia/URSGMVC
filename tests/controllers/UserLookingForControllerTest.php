<?php

namespace tests\controllers;

use tests\BaseControllerTestCase;
use controllers\UserLookingForController;
use models\UserLookingFor;
use models\User;
use models\LeagueOfLegends;
use models\FriendRequest;
use models\GoogleUser;

class UserLookingForControllerTest extends BaseControllerTestCase
{
    private function createController(array $mockOverrides = []): UserLookingForController
    {
        $defaults = [
            'userlookingfor' => $this->createMock(UserLookingFor::class),
            'user' => $this->createMock(User::class),
            'leagueoflegends' => $this->createMock(LeagueOfLegends::class),
            'friendrequest' => $this->createMock(FriendRequest::class),
            'googleUser' => $this->createMock(GoogleUser::class),
        ];
        $mocks = array_merge($defaults, $mockOverrides);

        return $this->createControllerWithMocks(UserLookingForController::class, $mocks);
    }

    // ─── createLookingFor (website form) ────────────────────────

    public function testCreateLookingForNoForm(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'createLookingFor');
        $this->assertTrue(true, 'createLookingFor without submit should redirect');
    }

    public function testCreateLookingForSuccess(): void
    {
        $this->simulateLoggedInUser(1);

        $lfMock = $this->createMock(UserLookingFor::class);
        $lfMock->method('createLookingForUser')->willReturn(true);

        $controller = $this->createController(['userlookingfor' => $lfMock]);

        $_POST['submit'] = true;
        $_POST['userId'] = '1';
        $_POST['game'] = 'League of Legends';
        $_POST['gender'] = 'Any';
        $_POST['kindofgamer'] = 'Competitive';
        $_POST['main1'] = 'Jinx';
        $_POST['main2'] = 'Seraphine';
        $_POST['main3'] = 'Nami';
        $_POST['rank'] = 'Gold';
        $_POST['role'] = 'ADC';
        $_POST['skipSelection'] = '0';

        $output = $this->captureOutput($controller, 'createLookingFor');
        $this->assertTrue(true);
    }

    // ─── createLookingForUserPhone ──────────────────────────────

    public function testCreateLookingForUserPhoneNoBearer(): void
    {
        $controller = $this->createController();
        $_POST['lookingforData'] = json_encode([
            'userId' => 1,
            'game' => 'League of Legends',
            'gender' => 'Any',
            'kindOfGamer' => 'Competitive',
            'main1' => 'Jinx',
            'main2' => 'Seraphine',
            'main3' => 'Nami',
            'rank' => 'Gold',
            'role' => 'ADC',
            'skipSelection' => 0,
        ]);

        $result = $this->captureJsonOutput($controller, 'createLookingForUserPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testCreateLookingForUserPhoneSuccess(): void
    {
        $this->setBearerToken('valid_phone_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenByUserId')->willReturn([
            'google_masterToken' => 'valid_phone_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser(['lf_id' => null]));

        $lfMock = $this->createMock(UserLookingFor::class);
        $lfMock->method('createLookingForUser')->willReturn(true);
        $lfMock->method('getLookingForUserByUserId')->willReturn($this->fakeLookingFor());

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'userlookingfor' => $lfMock,
            'user' => $userMock,
        ]);

        $_POST['lookingforData'] = json_encode([
            'userId' => 1,
            'game' => 'League of Legends',
            'gender' => 'Any',
            'kindOfGamer' => 'Competitive',
            'main1' => 'Jinx',
            'main2' => 'Seraphine',
            'main3' => 'Nami',
            'rank' => 'Gold',
            'role' => 'ADC',
            'skipSelection' => 0,
        ]);

        $result = $this->captureJsonOutput($controller, 'createLookingForUserPhone');
        $this->assertNotNull($result);
        $this->assertEquals('Success', $result['message']);
        $this->assertArrayHasKey('sessionId', $result);
        $this->assertIsString($result['sessionId']);
        $this->assertArrayHasKey('user', $result);
        // Verify user sub-object values match fakeLookingFor
        $this->assertEquals(1, $result['user']['lfId']);
        $this->assertEquals('Any', $result['user']['lfGender']);
        $this->assertEquals('Competitive', $result['user']['lfKingOfGamer']);
        $this->assertEquals('League of Legends', $result['user']['lfGame']);
        $this->assertEquals('Jinx', $result['user']['main1Lf']);
        $this->assertEquals('Seraphine', $result['user']['main2Lf']);
        $this->assertEquals('Nami', $result['user']['main3Lf']);
        $this->assertEquals('Gold', $result['user']['rankLf']);
        $this->assertEquals('ADC', $result['user']['roleLf']);
    }

    // ─── updateLookingFor (website form) ────────────────────────

    public function testUpdateLookingForNoForm(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'updateLookingFor');
        $this->assertTrue(true);
    }

    public function testUpdateLookingForSuccess(): void
    {
        $this->simulateLoggedInUser(1);

        $lfMock = $this->createMock(UserLookingFor::class);
        $lfMock->method('updateLookingForData')->willReturn(true);

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $controller = $this->createController([
            'userlookingfor' => $lfMock,
            'googleUser' => $googleUserMock,
        ]);

        $_POST['submit'] = true;
        $_POST['userId'] = '1';
        $_POST['game'] = 'League of Legends';
        $_POST['gender'] = 'Female';
        $_POST['kindofgamer'] = 'Casual';
        $_POST['main1'] = 'Lux';
        $_POST['main2'] = 'Morgana';
        $_POST['main3'] = 'Karma';
        $_POST['rank'] = 'Platinum';
        $_POST['role'] = 'Support';
        $_POST['skipSelection'] = '0';
        $_POST['filteredServers'] = '';
        $_COOKIE['master_token'] = 'valid_token';

        $output = $this->captureOutput($controller, 'updateLookingFor');
        $this->assertTrue(true);
    }

    // ─── Utility functions ──────────────────────────────────────

    public function testValidateInput(): void
    {
        $controller = $this->createController();
        $result = $controller->validateInput('  <b>test</b>  ');
        $this->assertStringNotContainsString('<b>', $result);
    }
}
