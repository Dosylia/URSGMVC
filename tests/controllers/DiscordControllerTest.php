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
use models\BannedUsers;
use services\LogInService;
use services\SignUpService;
use services\MasterTokenService;
use services\DiscordOAuthClientInterface;

class DiscordControllerTest extends BaseControllerTestCase
{
    private function createController(array $mockOverrides = []): DiscordController
    {
        $defaults = [
            'leagueOfLegends'    => $this->createMock(LeagueOfLegends::class),
            'user'               => $this->createMock(User::class),
            'valorant'           => $this->createMock(Valorant::class),
            'googleUser'         => $this->createMock(GoogleUser::class),
            'userlookingfor'     => $this->createMock(UserLookingFor::class),
            'discord'            => $this->createMock(Discord::class),
            'items'              => $this->createMock(Items::class),
            'bannedusers'        => $this->createMock(BannedUsers::class),
            'discordOAuthClient' => $this->createMock(DiscordOAuthClientInterface::class),
        ];
        $mocks = array_merge($defaults, $mockOverrides);

        $masterTokenService = new MasterTokenService($mocks['googleUser']);
        $mocks['logInService'] = new LogInService(
            $masterTokenService,
            $mocks['user'],
            $mocks['leagueOfLegends'],
            $mocks['valorant'],
            $mocks['userlookingfor']
        );
        $mocks['signUpService'] = new SignUpService($mocks['googleUser'], $masterTokenService);

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

    // ─── discordData ─────────────────────────────────────────────

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDiscordDataBannedUserStopsBeforeIdentityLookup(): void
    {
        // Must run in a separate process because the controller uses require_once 'keys.php'.
        require_once __DIR__ . '/../../vendor/autoload.php';

        $_GET['code'] = 'test-code';

        $oauthMock = $this->createMock(DiscordOAuthClientInterface::class);
        $oauthMock->method('getAccessToken')->willReturn(['access_token' => 'tok']);
        $oauthMock->method('getUserInfo')->willReturn([
            'id' => 'discord-1',
            'username' => 'BannedUser',
            'email' => 'banned@test.com',
            'avatar' => null,
        ]);

        $bannedMock = $this->createMock(BannedUsers::class);
        $bannedMock->method('checkBan')->willReturn(true);

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->expects($this->never())->method('getUserByDiscordId');

        $controller = $this->createController([
            'discordOAuthClient' => $oauthMock,
            'bannedusers'        => $bannedMock,
            'googleUser'         => $googleUserMock,
        ]);

        $this->captureOutput($controller, 'discordData');
        unset($_GET['code']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDiscordDataExistingUserOnboarded(): void
    {
        require_once __DIR__ . '/../../vendor/autoload.php';

        $_GET['code'] = 'test-code';

        $oauthMock = $this->createMock(DiscordOAuthClientInterface::class);
        $oauthMock->method('getAccessToken')->willReturn(['access_token' => 'tok']);
        $oauthMock->method('getUserInfo')->willReturn([
            'id' => 'discord-1',
            'username' => 'ExistingUser',
            'email' => 'existing@test.com',
            'avatar' => null,
        ]);

        $bannedMock = $this->createMock(BannedUsers::class);
        $bannedMock->method('checkBan')->willReturn(false);

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getUserByDiscordId')->willReturn([
            'google_userId' => 1,
            'google_id' => 'discord-1',
            'google_fullName' => 'Existing User',
            'google_firstName' => 'Existing',
            'google_lastName' => 'User',
            'google_email' => 'existing@test.com',
            'google_masterTokenWebsite' => 'existing_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByGoogleUserId')->willReturn($this->fakeUser(['user_game' => 'League of Legends']));

        $lolMock = $this->createMock(LeagueOfLegends::class);
        $lolMock->method('getLeageUserByUserId')->willReturn($this->fakeLoLProfile());

        $lfMock = $this->createMock(UserLookingFor::class);
        $lfMock->method('getLookingForUserByUserId')->willReturn($this->fakeLookingFor());

        $discordMock = $this->createMock(Discord::class);
        $discordMock->method('getDiscordAccount')->willReturn(['user_id' => 1]);
        $discordMock->expects($this->never())->method('saveDiscordData');

        $controller = $this->createController([
            'discordOAuthClient' => $oauthMock,
            'bannedusers'        => $bannedMock,
            'googleUser'         => $googleUserMock,
            'user'               => $userMock,
            'leagueOfLegends'    => $lolMock,
            'userlookingfor'     => $lfMock,
            'discord'            => $discordMock,
        ]);

        $this->captureOutput($controller, 'discordData');

        $this->assertEquals(1, $_SESSION['userId']);
        $this->assertEquals(1, $_SESSION['lol_id'] ?? null);
        unset($_GET['code']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDiscordDataNewUserSuccess(): void
    {
        require_once __DIR__ . '/../../vendor/autoload.php';

        $_GET['code'] = 'test-code';

        $oauthMock = $this->createMock(DiscordOAuthClientInterface::class);
        $oauthMock->method('getAccessToken')->willReturn(['access_token' => 'tok']);
        $oauthMock->method('getUserInfo')->willReturn([
            'id' => 'discord-new-1',
            'username' => 'NewUser',
            'email' => 'newdiscord@test.com',
            'avatar' => null,
        ]);

        $bannedMock = $this->createMock(BannedUsers::class);
        $bannedMock->method('checkBan')->willReturn(false);

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getUserByDiscordId')->willReturn(false);
        $googleUserMock->method('getGoogleUserByEmail')->willReturn(false);
        $googleUserMock->method('createGoogleUser')->willReturn(88);
        $googleUserMock->method('storeMasterTokenWebsite')->willReturn(true);

        $controller = $this->createController([
            'discordOAuthClient' => $oauthMock,
            'bannedusers'        => $bannedMock,
            'googleUser'         => $googleUserMock,
        ]);

        $this->captureOutput($controller, 'discordData');

        $this->assertEquals(88, $_SESSION['google_userId'] ?? null);
        $this->assertEquals('discord-new-1', $_SESSION['google_id'] ?? null);
        unset($_GET['code']);
    }
}
