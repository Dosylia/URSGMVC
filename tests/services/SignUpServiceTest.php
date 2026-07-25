<?php

namespace tests\services;

use PHPUnit\Framework\TestCase;
use services\SignUpService;
use services\MasterTokenService;
use models\GoogleUser;

class SignUpServiceTest extends TestCase
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
        // session_destroy()/setcookie() need an open output buffer and
        // suppressed warnings under the CLI test runner, same as LogInServiceTest.
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

    private function makeService(?GoogleUser $googleUser = null): SignUpService
    {
        $googleUser = $googleUser ?? $this->createMock(GoogleUser::class);
        return new SignUpService($googleUser, new MasterTokenService($googleUser));
    }

    public function testCreateWebIdentityReturnsNullWhenInsertFails(): void
    {
        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('createGoogleUser')->willReturn(false);
        $googleUserMock->expects($this->never())->method('storeMasterTokenWebsite');

        $service = $this->makeService($googleUserMock);
        $outcome = $service->createWebIdentity('provider-id', 'Full Name', 'First', 'Last', 'new@test.com', 0);

        $this->assertNull($outcome);
    }

    public function testCreateWebIdentitySuccessPopulatesOutcomeAndSession(): void
    {
        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('createGoogleUser')->willReturn(55);
        $googleUserMock->method('storeMasterTokenWebsite')->willReturn(true);

        $service = $this->makeService($googleUserMock);
        $outcome = $service->createWebIdentity('provider-id', 'Full Name', 'First', 'Last', 'new@test.com', 0);

        $this->assertNotNull($outcome);
        $this->assertTrue($outcome->newUser);
        $this->assertFalse($outcome->userExists);
        $this->assertEquals(55, $outcome->identityRow['google_userId']);
        $this->assertEquals('provider-id', $outcome->identityRow['google_id']);
        $this->assertNotEmpty($outcome->masterToken);
        $this->assertEquals(55, $_SESSION['google_userId']);
        $this->assertEquals('provider-id', $_SESSION['google_id']);
        $this->assertEquals('new@test.com', $_SESSION['email']);
        $this->assertEquals($outcome->masterToken, $_SESSION['masterTokenWebsite']);
    }

    public function testCreateWebIdentityWithFailedTokenStorageReturnsEmptyToken(): void
    {
        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('createGoogleUser')->willReturn(55);
        $googleUserMock->method('storeMasterTokenWebsite')->willReturn(false);

        $service = $this->makeService($googleUserMock);
        $outcome = $service->createWebIdentity('provider-id', 'Full Name', 'First', 'Last', 'new@test.com', 0);

        $this->assertNotNull($outcome);
        $this->assertEquals('', $outcome->masterToken);
        $this->assertArrayNotHasKey('masterTokenWebsite', $_SESSION);
    }

    // ─── createMobileIdentity ───────────────────────────────────────

    public function testCreateMobileIdentityReturnsNullWhenInsertFails(): void
    {
        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('createGoogleUser')->willReturn(false);
        $googleUserMock->expects($this->never())->method('storeMasterToken');

        $service = $this->makeService($googleUserMock);
        $outcome = $service->createMobileIdentity('provider-id', 'Full Name', 'First', 'Last', 'new@test.com', 0);

        $this->assertNull($outcome);
    }

    public function testCreateMobileIdentitySuccessDoesNotTouchSession(): void
    {
        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('createGoogleUser')->willReturn(77);
        $googleUserMock->expects($this->once())->method('storeMasterToken');

        $service = $this->makeService($googleUserMock);
        $outcome = $service->createMobileIdentity('provider-id', 'Full Name', 'First', 'Last', 'new@test.com', 0);

        $this->assertNotNull($outcome);
        $this->assertTrue($outcome->newUser);
        $this->assertEquals(77, $outcome->identityRow['googleUserId']);
        $this->assertEquals('provider-id', $outcome->identityRow['googleId']);
        $this->assertNotEmpty($outcome->identityRow['token']);
        $this->assertNotEmpty($outcome->masterToken);
        $this->assertArrayNotHasKey('google_userId', $_SESSION);
    }
}
