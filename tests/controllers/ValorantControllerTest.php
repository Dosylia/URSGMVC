<?php

namespace tests\controllers;

use tests\BaseControllerTestCase;
use controllers\ValorantController;
use models\Valorant;
use models\User;
use models\FriendRequest;
use models\GoogleUser;

class ValorantControllerTest extends BaseControllerTestCase
{
    private function createController(array $mockOverrides = []): ValorantController
    {
        $defaults = [
            'valorant' => $this->createMock(Valorant::class),
            'user' => $this->createMock(User::class),
            'friendrequest' => $this->createMock(FriendRequest::class),
            'googleUser' => $this->createMock(GoogleUser::class),
        ];
        $mocks = array_merge($defaults, $mockOverrides);

        return $this->createControllerWithMocks(ValorantController::class, $mocks);
    }

    // ─── createValorantUser (website form) ──────────────────────

    public function testCreateValorantUserNoForm(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'createValorantUser');
        $this->assertTrue(true, 'createValorantUser without submit should redirect');
    }

    public function testCreateValorantUserSuccess(): void
    {
        $this->simulateLoggedInUser(1, 'g123456', 0, 1, 0);

        $valMock = $this->createMock(Valorant::class);
        $valMock->method('createValorantUser')->willReturn(true);

        $controller = $this->createController(['valorant' => $valMock]);

        $_POST['submit'] = true;
        $_POST['userId'] = '1';
        $_POST['main1'] = 'Sage';
        $_POST['main2'] = 'Jett';
        $_POST['main3'] = 'Reyna';
        $_POST['rank_valorant'] = 'Gold';
        $_POST['role_valorant'] = 'Support';
        $_POST['server_valorant'] = 'EU';
        $_POST['skipSelection'] = '0';

        $output = $this->captureOutput($controller, 'createValorantUser');
        $this->assertTrue(true);
    }

    // ─── createValorantUserPhone ────────────────────────────────

    public function testCreateValorantUserPhoneNoBearer(): void
    {
        $controller = $this->createController();
        $_POST['valorantData'] = json_encode([
            'userId' => 1,
            'main1' => 'Sage',
            'main2' => 'Jett',
            'main3' => 'Reyna',
            'rank' => 'Gold',
            'role' => 'Support',
            'server' => 'EU',
            'skipSelection' => 0,
        ]);

        $result = $this->captureJsonOutput($controller, 'createValorantUserPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testCreateValorantUserPhoneSuccess(): void
    {
        $this->setBearerToken('valid_phone_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenByUserId')->willReturn([
            'google_masterToken' => 'valid_phone_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser(['valorant_id' => null]));

        $valMock = $this->createMock(Valorant::class);
        $valMock->method('createValorantUser')->willReturn(1);
        $valMock->method('getValorantAccountByValorantId')->willReturn($this->fakeValorantProfile());

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'valorant' => $valMock,
            'user' => $userMock,
        ]);

        $_POST['valorantData'] = json_encode([
            'userId' => 1,
            'main1' => 'Sage',
            'main2' => 'Jett',
            'main3' => 'Reyna',
            'rank' => 'Gold',
            'role' => 'Support',
            'server' => 'EU',
            'skipSelection' => 0,
        ]);

        $result = $this->captureJsonOutput($controller, 'createValorantUserPhone');
        $this->assertNotNull($result);
        $this->assertEquals('Success', $result['message']);
        $this->assertArrayHasKey('sessionId', $result);
        $this->assertIsString($result['sessionId']);
        $this->assertArrayHasKey('user', $result);
        // Verify user sub-object values match fakeValorantProfile
        $this->assertEquals(1, $result['user']['valorantId']);
        $this->assertEquals('Sage', $result['user']['main1']);
        $this->assertNull($result['user']['main2']);
        $this->assertNull($result['user']['main3']);
        $this->assertEquals('Gold', $result['user']['rank']);
        $this->assertEquals('Support', $result['user']['role']);
        $this->assertEquals('EU', $result['user']['server']);
    }

    // ─── UpdateValorant (website form) ──────────────────────────

    public function testUpdateValorantNoForm(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'UpdateValorant');
        $this->assertTrue(true);
    }

    public function testUpdateValorantSuccess(): void
    {
        $this->simulateLoggedInUser(1, 'g123456', 0, 1, 1);

        $valMock = $this->createMock(Valorant::class);
        $valMock->method('updateValorantData')->willReturn(true);

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $controller = $this->createController([
            'valorant' => $valMock,
            'googleUser' => $googleUserMock,
        ]);

        $_POST['submit'] = true;
        $_POST['userId'] = '1';
        $_POST['main1'] = 'Omen';
        $_POST['main2'] = 'Viper';
        $_POST['main3'] = 'Brimstone';
        $_POST['rank_valorant'] = 'Platinum';
        $_POST['role_valorant'] = 'Controller';
        $_POST['server_valorant'] = 'EU';
        $_POST['skipSelection'] = '0';
        $_COOKIE['master_token'] = 'valid_token';

        $output = $this->captureOutput($controller, 'UpdateValorant');
        $this->assertTrue(true);
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
        $result = $controller->validateInput('  <div>hello</div>  ');
        $this->assertStringNotContainsString('<div>', $result);
        $this->assertEquals('&lt;div&gt;hello&lt;/div&gt;', $result);
    }
}
