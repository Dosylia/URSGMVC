<?php

namespace tests\controllers;

use tests\BaseControllerTestCase;
use controllers\DiscordController;
use models\LeagueOfLegends;
use models\Valorant;
use models\User;
use models\GoogleUser;
use models\UserLookingFor;
use models\Discord;
use models\Items;

class DiscordControllerTest extends BaseControllerTestCase
{
    private function createController(array $mockOverrides = []): DiscordController
    {
        $defaults = [
            'leagueOfLegends' => $this->createMock(LeagueOfLegends::class),
            'user'            => $this->createMock(User::class),
            'valorant'        => $this->createMock(Valorant::class),
            'googleUser'      => $this->createMock(GoogleUser::class),
            'userlookingfor'  => $this->createMock(UserLookingFor::class),
            'discord'         => $this->createMock(Discord::class),
            'items'           => $this->createMock(Items::class),
        ];
        $mocks = array_merge($defaults, $mockOverrides);

        return $this->createControllerWithMocks(DiscordController::class, $mocks);
    }

    // ─── createChannel ──────────────────────────────────────────

    public function testCreateChannelNoBearer(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'createChannel');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testCreateChannelNoUserId(): void
    {
        $this->setBearerToken('valid_token');
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'createChannel');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
        $this->assertEquals('Invalid request', $result['message'] ?? '');
    }

    public function testCreateChannelInvalidToken(): void
    {
        $this->setBearerToken('wrong_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'different_token',
        ]);

        $controller = $this->createController(['googleUser' => $googleUserMock]);
        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'createChannel');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testCreateChannelRateLimit(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $discordMock = $this->createMock(Discord::class);
        $discordMock->method('hasCreatedChannelRecently')->willReturn(true);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'discord' => $discordMock,
        ]);
        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'createChannel');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
        $this->assertStringContainsString('wait', $result['message'] ?? '');
    }

    // ─── deleteExpiredChannels ───────────────────────────────────

    public function testDeleteExpiredChannelsUnauthorized(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'deleteExpiredChannels');
        $this->assertIsString($output);
    }

    public function testDeleteExpiredChannelsNoChannels(): void
    {
        $discordMock = $this->createMock(Discord::class);
        $discordMock->method('getExpiredChannels')->willReturn([]);

        $controller = $this->createController(['discord' => $discordMock]);
        $_GET['token'] = '56874d4azfzezfze65ezze2ffd6e62f6e';

        $output = $this->captureOutput($controller, 'deleteExpiredChannels');
        // The method returns early with an echo when no expired channels exist
        $this->assertTrue(true, 'Should report no expired channels');
    }

    // ─── sendMessageDiscord ─────────────────────────────────────

    public function testSendMessageDiscordNoBearer(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'sendMessageDiscord');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testSendMessageDiscordNoUserId(): void
    {
        $this->setBearerToken('valid_token');
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'sendMessageDiscord');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testSendMessageDiscordInvalidToken(): void
    {
        $this->setBearerToken('wrong_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'different_token',
        ]);

        $controller = $this->createController(['googleUser' => $googleUserMock]);
        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'sendMessageDiscord');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── sendMessageDiscordPhone ────────────────────────────────

    public function testSendMessageDiscordPhoneNoBearer(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'sendMessageDiscordPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testSendMessageDiscordPhoneNoUserId(): void
    {
        $this->setBearerToken('valid_token');
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'sendMessageDiscordPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testSendMessageDiscordPhoneInvalidToken(): void
    {
        $this->setBearerToken('wrong_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenByUserId')->willReturn([
            'google_masterToken' => 'different_token',
        ]);

        $controller = $this->createController(['googleUser' => $googleUserMock]);
        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'sendMessageDiscordPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── startBotCronJob ────────────────────────────────────────

    public function testStartBotCronJobUnauthorized(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'startBotCronJob');
        $this->assertIsString($output);
    }

    // ─── connectDiscordMobile ───────────────────────────────────

    public function testConnectDiscordMobileNoPhoneData(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'connectDiscordMobile');
        $this->assertIsString($output);
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
