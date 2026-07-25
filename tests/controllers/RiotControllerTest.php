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
use services\LogInService;
use services\SignUpService;
use services\MasterTokenService;
use services\RiotOAuthClientInterface;

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
            'riotOAuthClient' => $this->createMock(RiotOAuthClientInterface::class),
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

    // ─── riotAccount (RSO login path) ────────────────────────────

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRiotAccountLoginExistingRSOUserOnboarded(): void
    {
        // Must run in a separate process because the controller uses require_once 'keys.php'.
        require_once __DIR__ . '/../../vendor/autoload.php';

        $_GET['code'] = 'test-code';

        $riotOAuthMock = $this->createMock(RiotOAuthClientInterface::class);
        $riotOAuthMock->method('getAccessToken')->willReturn('mock-token');
        $riotOAuthMock->method('getUserData')->willReturn([
            'puuid' => 'riot-puuid-1',
            'gameName' => 'ExistingSummoner',
            'tagLine' => 'NA1',
        ]);

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getUserByPuuid')->willReturn([
            'google_userId' => 1,
            'google_id' => 'riot-puuid-1',
            'google_fullName' => 'Existing Summoner',
            'google_firstName' => 'Existing',
            'google_lastName' => 'Summoner',
            'google_email' => 'riot_riot-puuid-1@fake.riot',
            'google_masterTokenWebsite' => 'existing_token',
            'google_createdWithRSO' => 1,
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByGoogleUserId')->willReturn($this->fakeUser(['user_game' => 'League of Legends']));

        $lolMock = $this->createMock(LeagueOfLegends::class);
        $lolMock->method('getLeageUserByUserId')->willReturn($this->fakeLoLProfile());

        $lfMock = $this->createMock(UserLookingFor::class);
        $lfMock->method('getLookingForUserByUserId')->willReturn($this->fakeLookingFor());

        $controller = $this->createController([
            'riotOAuthClient' => $riotOAuthMock,
            'googleUser'      => $googleUserMock,
            'user'            => $userMock,
            'leagueOfLegends' => $lolMock,
            'userlookingfor'  => $lfMock,
        ]);

        $this->captureOutput($controller, 'riotAccount');

        $this->assertEquals(1, $_SESSION['userId']);
        $this->assertEquals('ExistingSummoner', $_SESSION['full_name'] ?? null);
        $this->assertEquals('NA1', $_SESSION['tagLine'] ?? null);
        unset($_GET['code']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRiotAccountLoginNewUserSuccess(): void
    {
        require_once __DIR__ . '/../../vendor/autoload.php';

        $_GET['code'] = 'test-code';

        $riotOAuthMock = $this->createMock(RiotOAuthClientInterface::class);
        $riotOAuthMock->method('getAccessToken')->willReturn('mock-token');
        $riotOAuthMock->method('getUserData')->willReturn([
            'puuid' => 'riot-puuid-new',
            'gameName' => 'NewSummoner',
            'tagLine' => 'EUW',
        ]);

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getUserByPuuid')->willReturn(false);
        $googleUserMock->method('createGoogleUser')->willReturn(99);
        $googleUserMock->method('storeMasterTokenWebsite')->willReturn(true);

        $controller = $this->createController([
            'riotOAuthClient' => $riotOAuthMock,
            'googleUser'      => $googleUserMock,
        ]);

        $this->captureOutput($controller, 'riotAccount');

        $this->assertEquals(99, $_SESSION['google_userId'] ?? null);
        $this->assertEquals('riot-puuid-new', $_SESSION['google_id'] ?? null);
        $this->assertEquals('EUW', $_SESSION['tagLine'] ?? null);
        unset($_GET['code']);
    }
}
