<?php

namespace tests\services;

use PHPUnit\Framework\TestCase;
use services\LogInService;
use services\MasterTokenService;
use services\LoginDestination;
use models\GoogleUser;
use models\User;
use models\LeagueOfLegends;
use models\Valorant;
use models\UserLookingFor;

class LogInServiceTest extends TestCase
{
    private array $backupSession;
    private array $backupCookie;
    private int $previousErrorLevel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupSession = $_SESSION ?? [];
        $this->backupCookie = $_COOKIE ?? [];
        $_SESSION = [];
        // setcookie() emits a "headers already sent" warning under the CLI test
        // runner once any prior output has been flushed; suppress it like
        // BaseControllerTestCase::captureOutput does for controller tests.
        $this->previousErrorLevel = error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
        ob_start();
    }

    protected function tearDown(): void
    {
        ob_end_clean();
        error_reporting($this->previousErrorLevel);
        $_SESSION = $this->backupSession;
        $_COOKIE = $this->backupCookie;
        parent::tearDown();
    }

    private function makeService(
        ?GoogleUser $googleUser = null,
        ?User $user = null,
        ?LeagueOfLegends $leagueOfLegends = null,
        ?Valorant $valorant = null,
        ?UserLookingFor $userLookingFor = null
    ): LogInService {
        $masterTokenService = new MasterTokenService($googleUser ?? $this->createMock(GoogleUser::class));

        return new LogInService(
            $masterTokenService,
            $user ?? $this->createMock(User::class),
            $leagueOfLegends ?? $this->createMock(LeagueOfLegends::class),
            $valorant ?? $this->createMock(Valorant::class),
            $userLookingFor ?? $this->createMock(UserLookingFor::class)
        );
    }

    private function identityRow(array $overrides = []): array
    {
        return array_merge([
            'google_userId' => 1,
            'google_id' => 'g123',
            'google_fullName' => 'Test User',
            'google_firstName' => 'Test',
            'google_lastName' => 'User',
            'google_email' => 'test@test.com',
            'google_masterTokenWebsite' => 'existing_token',
        ], $overrides);
    }

    private function userRow(array $overrides = []): array
    {
        return array_merge([
            'user_id' => 1,
            'user_username' => 'testuser',
            'user_gender' => 'Male',
            'user_age' => 25,
            'user_kindOfGamer' => 'Competitive',
            'user_game' => 'League of Legends',
        ], $overrides);
    }

    // ─── resumeWebSession: token handling ────────────────────────

    public function testResumeWebSessionReusesExistingToken(): void
    {
        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->expects($this->never())->method('storeMasterTokenWebsite');

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByGoogleUserId')->willReturn(false);

        $service = $this->makeService($googleUserMock, $userMock);
        $outcome = $service->resumeWebSession($this->identityRow(['google_masterTokenWebsite' => 'existing_token']));

        $this->assertEquals('existing_token', $outcome->masterToken);
    }

    public function testResumeWebSessionMintsTokenWhenMissing(): void
    {
        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->expects($this->once())->method('storeMasterTokenWebsite');

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByGoogleUserId')->willReturn(false);

        $service = $this->makeService($googleUserMock, $userMock);
        $outcome = $service->resumeWebSession($this->identityRow(['google_masterTokenWebsite' => null]));

        $this->assertNotEmpty($outcome->masterToken);
        $this->assertNotEquals('existing_token', $outcome->masterToken);
    }

    public function testResumeWebSessionPopulatesIdentitySession(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByGoogleUserId')->willReturn(false);

        $service = $this->makeService(null, $userMock);
        $service->resumeWebSession($this->identityRow());

        $this->assertEquals(1, $_SESSION['google_userId']);
        $this->assertEquals('g123', $_SESSION['google_id']);
        $this->assertEquals('test@test.com', $_SESSION['email']);
        $this->assertEquals('Test User', $_SESSION['full_name']);
        $this->assertEquals('Test', $_SESSION['google_firstName']);
        $this->assertEquals('existing_token', $_SESSION['masterTokenWebsite']);
    }

    // ─── resumeWebSession: no linked user row ────────────────────

    public function testResumeWebSessionNoUserRow(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByGoogleUserId')->willReturn(false);

        $service = $this->makeService(null, $userMock);
        $outcome = $service->resumeWebSession($this->identityRow());

        $this->assertFalse($outcome->userExists);
        $this->assertFalse($outcome->newUser);
        $this->assertEquals(LoginDestination::NO_USER_ROW, $outcome->destination);
        $this->assertArrayNotHasKey('userId', $_SESSION);
    }

    // ─── resumeWebSession: League of Legends branch ──────────────

    public function testResumeWebSessionLeagueOnboarded(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByGoogleUserId')->willReturn($this->userRow(['user_game' => 'League of Legends']));

        $lolMock = $this->createMock(LeagueOfLegends::class);
        $lolMock->method('getLeageUserByUserId')->willReturn(['lol_id' => 42]);

        $lfMock = $this->createMock(UserLookingFor::class);
        $lfMock->method('getLookingForUserByUserId')->willReturn(['lf_id' => 99]);

        $service = $this->makeService(null, $userMock, $lolMock, null, $lfMock);
        $outcome = $service->resumeWebSession($this->identityRow());

        $this->assertTrue($outcome->userExists);
        $this->assertEquals(LoginDestination::ONBOARDED, $outcome->destination);
        $this->assertEquals('League of Legends', $outcome->game);
        $this->assertEquals(['lol_id' => 42], $outcome->gameProfile);
        $this->assertEquals(['lf_id' => 99], $outcome->lookingForRow);
        $this->assertEquals(42, $_SESSION['lol_id']);
        $this->assertEquals(99, $_SESSION['lf_id']);
        $this->assertEquals(1, $_SESSION['userId']);
    }

    public function testResumeWebSessionLeagueNeedsLookingFor(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByGoogleUserId')->willReturn($this->userRow(['user_game' => 'League of Legends']));

        $lolMock = $this->createMock(LeagueOfLegends::class);
        $lolMock->method('getLeageUserByUserId')->willReturn(['lol_id' => 42]);

        $lfMock = $this->createMock(UserLookingFor::class);
        $lfMock->method('getLookingForUserByUserId')->willReturn(false);

        $service = $this->makeService(null, $userMock, $lolMock, null, $lfMock);
        $outcome = $service->resumeWebSession($this->identityRow());

        $this->assertEquals(LoginDestination::NEEDS_LOOKING_FOR, $outcome->destination);
        $this->assertEquals(42, $_SESSION['lol_id']);
        $this->assertArrayNotHasKey('lf_id', $_SESSION);
    }

    public function testResumeWebSessionLeagueNeedsGameAccount(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByGoogleUserId')->willReturn($this->userRow(['user_game' => 'League of Legends']));

        $lolMock = $this->createMock(LeagueOfLegends::class);
        $lolMock->method('getLeageUserByUserId')->willReturn(false);

        $service = $this->makeService(null, $userMock, $lolMock);
        $outcome = $service->resumeWebSession($this->identityRow());

        $this->assertEquals(LoginDestination::NEEDS_GAME_ACCOUNT, $outcome->destination);
        $this->assertEquals('League of Legends', $outcome->game);
        $this->assertNull($outcome->gameProfile);
        $this->assertArrayNotHasKey('lol_id', $_SESSION);
    }

    // ─── resumeWebSession: Valorant branch ────────────────────────

    public function testResumeWebSessionValorantOnboarded(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByGoogleUserId')->willReturn($this->userRow(['user_game' => 'Valorant']));

        $valorantMock = $this->createMock(Valorant::class);
        $valorantMock->method('getValorantUserByUserId')->willReturn(['valorant_id' => 7]);

        $lfMock = $this->createMock(UserLookingFor::class);
        $lfMock->method('getLookingForUserByUserId')->willReturn(['lf_id' => 8]);

        $service = $this->makeService(null, $userMock, null, $valorantMock, $lfMock);
        $outcome = $service->resumeWebSession($this->identityRow());

        $this->assertEquals(LoginDestination::ONBOARDED, $outcome->destination);
        $this->assertEquals('Valorant', $outcome->game);
        $this->assertEquals(7, $_SESSION['valorant_id']);
        $this->assertEquals(8, $_SESSION['lf_id']);
    }

    public function testResumeWebSessionValorantNeedsLookingFor(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByGoogleUserId')->willReturn($this->userRow(['user_game' => 'Valorant']));

        $valorantMock = $this->createMock(Valorant::class);
        $valorantMock->method('getValorantUserByUserId')->willReturn(['valorant_id' => 7]);

        $lfMock = $this->createMock(UserLookingFor::class);
        $lfMock->method('getLookingForUserByUserId')->willReturn(false);

        $service = $this->makeService(null, $userMock, null, $valorantMock, $lfMock);
        $outcome = $service->resumeWebSession($this->identityRow());

        $this->assertEquals(LoginDestination::NEEDS_LOOKING_FOR, $outcome->destination);
        $this->assertEquals(7, $_SESSION['valorant_id']);
    }

    public function testResumeWebSessionValorantNeedsGameAccount(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByGoogleUserId')->willReturn($this->userRow(['user_game' => 'Valorant']));

        $valorantMock = $this->createMock(Valorant::class);
        $valorantMock->method('getValorantUserByUserId')->willReturn(false);

        $service = $this->makeService(null, $userMock, null, $valorantMock);
        $outcome = $service->resumeWebSession($this->identityRow());

        $this->assertEquals(LoginDestination::NEEDS_GAME_ACCOUNT, $outcome->destination);
        $this->assertEquals('Valorant', $outcome->game);
        $this->assertArrayNotHasKey('valorant_id', $_SESSION);
    }

    // ─── resumeMobileProfile ───────────────────────────────────────

    public function testResumeMobileProfileUsesMobileTokenColumnAndSkipsSession(): void
    {
        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->expects($this->never())->method('storeMasterTokenWebsite');
        $googleUserMock->expects($this->never())->method('storeMasterToken');

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByGoogleUserId')->willReturn(false);

        $service = $this->makeService($googleUserMock, $userMock);
        $outcome = $service->resumeMobileProfile($this->identityRow(['google_masterToken' => 'existing_mobile_token']));

        $this->assertEquals('existing_mobile_token', $outcome->masterToken);
        $this->assertEquals('existing_mobile_token', $outcome->identityRow['token']);
        $this->assertArrayNotHasKey('google_userId', $_SESSION);
    }

    public function testResumeMobileProfileNoUserRowReturnsCuratedIdentityOnly(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByGoogleUserId')->willReturn(false);

        $service = $this->makeService(null, $userMock);
        $outcome = $service->resumeMobileProfile($this->identityRow());

        $this->assertFalse($outcome->userExists);
        $this->assertEquals(LoginDestination::NO_USER_ROW, $outcome->destination);
        $this->assertEquals('g123', $outcome->identityRow['googleId']);
    }

    public function testResumeMobileProfileLeagueOnboardedBuildsCuratedArrays(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByGoogleUserId')->willReturn($this->userRow(['user_game' => 'League of Legends']));

        $lolMock = $this->createMock(LeagueOfLegends::class);
        $lolMock->method('getLeageUserByUserId')->willReturn(['lol_id' => 42, 'lol_main1' => 'Lux', 'lol_main2' => null, 'lol_main3' => null, 'lol_rank' => 'Gold', 'lol_role' => 'Support', 'lol_server' => 'EUW', 'lol_account' => null, 'lol_sUsername' => null, 'lol_sLevel' => null, 'lol_sRank' => null, 'lol_sProfileIcon' => null, 'lol_noChamp' => 0]);

        $lfMock = $this->createMock(UserLookingFor::class);
        $lfMock->method('getLookingForUserByUserId')->willReturn(['lf_id' => 99, 'lf_gender' => 'Any', 'lf_kindofgamer' => 'Chill', 'lf_game' => 'League of Legends', 'lf_lolmain1' => null, 'lf_lolmain2' => null, 'lf_lolmain3' => null, 'lf_lolrank' => 'Any', 'lf_lolrole' => 'Any', 'lf_lolNoChamp' => 0, 'lf_filteredServer' => null]);

        $service = $this->makeService(null, $userMock, $lolMock, null, $lfMock);
        $outcome = $service->resumeMobileProfile($this->identityRow());

        $this->assertEquals(LoginDestination::ONBOARDED, $outcome->destination);
        $this->assertEquals(42, $outcome->gameProfile['lolId']);
        $this->assertEquals(99, $outcome->lookingForRow['lfId']);
        $this->assertArrayNotHasKey('lol_id', $_SESSION);
        $this->assertArrayNotHasKey('userId', $_SESSION);
    }
}
