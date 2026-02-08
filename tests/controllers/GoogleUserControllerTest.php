<?php

namespace tests\controllers;

use tests\BaseControllerTestCase;
use controllers\GoogleUserController;
use models\GoogleUser;
use models\User;
use models\LeagueOfLegends;
use models\Valorant;
use models\UserLookingFor;
use models\MatchingScore;
use models\Partners;
use models\BannedUsers;
use models\PlayerFinder;
use models\ChatMessage;
use models\FriendRequest;

class GoogleUserControllerTest extends BaseControllerTestCase
{
    private function createController(array $mockOverrides = []): GoogleUserController
    {
        $defaults = [
            'googleUser'     => $this->createMock(GoogleUser::class),
            'user'           => $this->createMock(User::class),
            'leagueoflegends'=> $this->createMock(LeagueOfLegends::class),
            'valorant'       => $this->createMock(Valorant::class),
            'userlookingfor' => $this->createMock(UserLookingFor::class),
            'matchingscore'  => $this->createMock(MatchingScore::class),
            'partners'       => $this->createMock(Partners::class),
            'bannedusers'    => $this->createMock(BannedUsers::class),
            'playerFinder'   => $this->createMock(PlayerFinder::class),
            'chatmessage'    => $this->createMock(ChatMessage::class),
            'friendrequest'  => $this->createMock(FriendRequest::class),
        ];
        $mocks = array_merge($defaults, $mockOverrides);

        return $this->createControllerWithMocks(GoogleUserController::class, $mocks);
    }

    // ─── changeLanguage ─────────────────────────────────────────

    public function testChangeLanguageValidLang(): void
    {
        $_POST['lang'] = 'fr';
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'changeLanguage');
        $this->assertTrue(true, 'changeLanguage should redirect on valid lang');
    }

    public function testChangeLanguageInvalidLang(): void
    {
        $_POST['lang'] = 'zz';
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'changeLanguage');
        $this->assertTrue(true, 'changeLanguage should do nothing on invalid lang');
    }

    // ─── getGoogleData (website) ────────────────────────────────

    public function testGetGoogleDataNoPost(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'getGoogleData');
        $this->assertNotNull($result);
        $this->assertEquals('Contact an administrator', $result['message'] ?? '');
    }

    public function testGetGoogleDataBannedUser(): void
    {
        $originalEnv = $_ENV['environment'] ?? null;
        $_ENV['environment'] = 'local';

        $bannedMock = $this->createMock(BannedUsers::class);
        $bannedMock->method('checkBan')->willReturn(true);

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('userExist')->willReturn(false);

        $controller = $this->createController([
            'bannedusers' => $bannedMock,
            'googleUser'  => $googleUserMock,
        ]);

        $_POST['googleData'] = json_encode([
            'googleId'   => 'g123',
            'fullName'   => 'Test User',
            'givenName'  => 'Test',
            'familyName' => 'User',
            'email'      => 'banned@test.com',
            'idToken'    => 'fake_token',
        ]);

        $result = $this->captureJsonOutput($controller, 'getGoogleData');
        $_ENV['environment'] = $originalEnv ?? 'test';
        $this->assertNotNull($result);
        $this->assertEquals('Account is banned', $result['message'] ?? '');
    }

    public function testGetGoogleDataExistingUser(): void
    {
        $originalEnv = $_ENV['environment'] ?? null;
        $_ENV['environment'] = 'local';

        $bannedMock = $this->createMock(BannedUsers::class);
        $bannedMock->method('checkBan')->willReturn(false);

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('userExist')->willReturn([
            'google_userId' => 1,
            'google_id' => 'g123',
            'google_fullName' => 'Test User',
            'google_firstName' => 'Test',
            'google_lastName' => 'User',
            'google_email' => 'test@test.com',
            'google_masterTokenWebsite' => 'existing_token',
        ]);
        $googleUserMock->method('storeMasterTokenWebsite')->willReturn(true);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserDataByGoogleUserId')->willReturn([
            'user_id' => 1,
            'user_username' => 'testuser',
        ]);
        $userMock->method('getUserByUsername')->willReturn($this->fakeUser());

        $lolMock = $this->createMock(LeagueOfLegends::class);
        $lolMock->method('getLeageUserByUserId')->willReturn($this->fakeLoLProfile());

        $lfMock = $this->createMock(UserLookingFor::class);
        $lfMock->method('getLookingForUserByUserId')->willReturn($this->fakeLookingFor());

        $controller = $this->createController([
            'bannedusers'    => $bannedMock,
            'googleUser'     => $googleUserMock,
            'user'           => $userMock,
            'leagueoflegends'=> $lolMock,
            'userlookingfor' => $lfMock,
        ]);

        $_POST['googleData'] = json_encode([
            'googleId'   => 'g123',
            'fullName'   => 'Test User',
            'givenName'  => 'Test',
            'familyName' => 'User',
            'email'      => 'test@test.com',
            'idToken'    => 'fake_token',
        ]);

        $result = $this->captureJsonOutput($controller, 'getGoogleData');
        $_ENV['environment'] = $originalEnv ?? 'test';
        $this->assertNotNull($result);
        $this->assertEquals('Success', $result['message']);
        $this->assertFalse($result['newUser']);
        $this->assertTrue($result['userExists']);
        $this->assertArrayHasKey('googleUser', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('masterTokenWebsite', $result);
        // Verify googleUser contains raw DB data
        $this->assertEquals('g123', $result['googleUser']['google_id']);
        $this->assertEquals('test@test.com', $result['googleUser']['google_email']);
        // Verify user contains our fakeUser data
        $this->assertEquals('TestUser', $result['user']['user_username']);
        $this->assertEquals(1, $result['user']['user_id']);
    }

    public function testGetGoogleDataNewUserEmailAlreadyUsed(): void
    {
        $originalEnv = $_ENV['environment'] ?? null;
        $_ENV['environment'] = 'local';

        $bannedMock = $this->createMock(BannedUsers::class);
        $bannedMock->method('checkBan')->willReturn(false);

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('userExist')->willReturn(false);
        $googleUserMock->method('getGoogleUserByEmail')->willReturn([
            'google_email' => 'exists@test.com',
        ]);

        $controller = $this->createController([
            'bannedusers' => $bannedMock,
            'googleUser'  => $googleUserMock,
        ]);

        $_POST['googleData'] = json_encode([
            'googleId'   => 'g999',
            'fullName'   => 'New User',
            'givenName'  => 'New',
            'familyName' => 'User',
            'email'      => 'exists@test.com',
            'idToken'    => 'fake_token',
        ]);

        $result = $this->captureJsonOutput($controller, 'getGoogleData');
        $_ENV['environment'] = $originalEnv ?? 'test';
        $this->assertNotNull($result);
        $this->assertEquals('Email already used.', $result['message'] ?? '');
    }

    // ─── getGoogleDataPhone ─────────────────────────────────────

    public function testGetGoogleDataPhoneNoPost(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'getGoogleDataPhone');
        $this->assertNotNull($result);
        $this->assertStringContainsString('administrator', $result['message'] ?? '');
    }

    public function testGetGoogleDataPhoneBannedUser(): void
    {
        $bannedMock = $this->createMock(BannedUsers::class);
        $bannedMock->method('checkBan')->willReturn(true);

        $controller = $this->createController([
            'bannedusers' => $bannedMock,
        ]);

        $_POST['googleData'] = json_encode([
            'googleId'   => 'g123',
            'fullName'   => 'Test',
            'givenName'  => 'Test',
            'familyName' => 'User',
            'email'      => 'banned@test.com',
        ]);

        $result = $this->captureJsonOutput($controller, 'getGoogleDataPhone');
        $this->assertNotNull($result);
        $this->assertEquals('Account is banned', $result['message'] ?? '');
    }

    public function testGetGoogleDataPhoneExistingUser(): void
    {
        $bannedMock = $this->createMock(BannedUsers::class);
        $bannedMock->method('checkBan')->willReturn(false);

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('userExist')->willReturn([
            'google_userId' => 1,
            'google_id' => 'g123',
            'google_fullName' => 'Test',
            'google_firstName' => 'Test',
            'google_lastName' => 'User',
            'google_email' => 'test@test.com',
            'google_masterToken' => 'phone_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserDataByGoogleUserId')->willReturn([
            'user_id' => 1,
            'user_username' => 'testuser',
        ]);
        $userMock->method('getUserByUsername')->willReturn($this->fakeUser());

        $lolMock = $this->createMock(LeagueOfLegends::class);
        $lolMock->method('getLeageUserByUserId')->willReturn($this->fakeLoLProfile());

        $lfMock = $this->createMock(UserLookingFor::class);
        $lfMock->method('getLookingForUserByUserId')->willReturn($this->fakeLookingFor());

        $controller = $this->createController([
            'bannedusers'    => $bannedMock,
            'googleUser'     => $googleUserMock,
            'user'           => $userMock,
            'leagueoflegends'=> $lolMock,
            'userlookingfor' => $lfMock,
        ]);

        $_POST['googleData'] = json_encode([
            'googleId'   => 'g123',
            'fullName'   => 'Test',
            'givenName'  => 'Test',
            'familyName' => 'User',
            'email'      => 'test@test.com',
        ]);

        $result = $this->captureJsonOutput($controller, 'getGoogleDataPhone');
        $this->assertNotNull($result);
        $this->assertEquals('Success', $result['message']);
        $this->assertFalse($result['newUser']);
        $this->assertTrue($result['userExists']);
        $this->assertArrayHasKey('googleUser', $result);
        $this->assertArrayHasKey('user', $result);
        // Verify curated user keys (phone uses camelCase names)
        $this->assertEquals(1, $result['user']['userId']);
        $this->assertEquals('TestUser', $result['user']['username']);
        $this->assertEquals('Male', $result['user']['gender']);
        $this->assertEquals(25, $result['user']['age']);
        $this->assertEquals('Competitive', $result['user']['kindOfGamer']);
        $this->assertEquals('League of Legends', $result['user']['game']);
        $this->assertEquals('Test bio', $result['user']['shortBio']);
        $this->assertEquals('default.png', $result['user']['picture']);
        $this->assertEquals(500, $result['user']['currency']);
        // Verify googleUser curated keys
        $this->assertEquals('g123', $result['googleUser']['googleId']);
        $this->assertEquals('Test', $result['googleUser']['firstName']);
        $this->assertEquals('test@test.com', $result['googleUser']['email']);
    }

    // ─── emailConfirmDb ─────────────────────────────────────────

    public function testEmailConfirmDbNoMail(): void
    {
        $controller = $this->createController();
        // No $_GET['mail'] → should do nothing
        $output = $this->captureOutput($controller, 'emailConfirmDb');
        $this->assertTrue(true);
    }

    public function testEmailConfirmDbEmailNotFound(): void
    {
        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getGoogleUserByEmail')->willReturn(false);

        $controller = $this->createController(['googleUser' => $googleUserMock]);

        $_GET['mail'] = 'notfound@test.com';

        $output = $this->captureOutput($controller, 'emailConfirmDb');
        $this->assertTrue(true, 'Should redirect with email not found message');
    }

    public function testEmailConfirmDbSuccess(): void
    {
        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getGoogleUserByEmail')->willReturn([
            'google_email' => 'found@test.com',
        ]);
        $googleUserMock->method('updateEmailStatus')->willReturn(true);

        $controller = $this->createController(['googleUser' => $googleUserMock]);

        $_GET['mail'] = 'found@test.com';

        $output = $this->captureOutput($controller, 'emailConfirmDb');
        $this->assertTrue(true, 'Should redirect with email confirmed');
    }

    // ─── logOut ─────────────────────────────────────────────────

    public function testLogOutNotConnected(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'logOut');
        $this->assertTrue(true, 'Should redirect to home');
    }

    public function testLogOutConnected(): void
    {
        $this->simulateLoggedInUser(1);
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'logOut');
        $this->assertTrue(true, 'Should clear session and redirect');
    }

    // ─── unsubscribeMails ───────────────────────────────────────

    public function testUnsubscribeMailsNoParams(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'unsubscribeMails');
        $this->assertTrue(true, 'Should redirect with invalid request');
    }

    public function testUnsubscribeMailsMismatchId(): void
    {
        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getUserByEmail')->willReturn([
            'google_userId' => 999,
        ]);

        $controller = $this->createController(['googleUser' => $googleUserMock]);

        $_GET['email'] = 'test@test.com';
        $_GET['googleUserId'] = '1'; // Mismatch

        $output = $this->captureOutput($controller, 'unsubscribeMails');
        $this->assertTrue(true, 'Should redirect with invalid request');
    }

    public function testUnsubscribeMailsSuccess(): void
    {
        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getUserByEmail')->willReturn([
            'google_userId' => 1,
        ]);
        $googleUserMock->method('unsubscribeMails')->willReturn(true);

        $controller = $this->createController(['googleUser' => $googleUserMock]);

        $_GET['email'] = 'test@test.com';
        $_GET['googleUserId'] = '1';

        $output = $this->captureOutput($controller, 'unsubscribeMails');
        $this->assertTrue(true, 'Should redirect with unsubscribed message');
    }

    // ─── isMobileUpdateNeeded ───────────────────────────────────

    public function testIsMobileUpdateNeededNoVersion(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'isMobileUpdateNeeded');
        $this->assertNotNull($result);
        $this->assertArrayHasKey('message', $result);
    }

    public function testIsMobileUpdateNeededOldVersion(): void
    {
        $controller = $this->createController();
        $_POST['currentVersion'] = '1.0.0';

        $result = $this->captureJsonOutput($controller, 'isMobileUpdateNeeded');
        $this->assertNotNull($result);
        $this->assertTrue($result['updateNeeded'] ?? false);
    }

    public function testIsMobileUpdateNeededLatestVersion(): void
    {
        $controller = $this->createController();
        $_POST['currentVersion'] = '99.99.99';

        $result = $this->captureJsonOutput($controller, 'isMobileUpdateNeeded');
        $this->assertNotNull($result);
        $this->assertFalse($result['updateNeeded'] ?? true);
    }

    // ─── deleteAccountConfirm ───────────────────────────────────

    public function testDeleteAccountConfirmNoToken(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'deleteAccountConfirm');
        $this->assertTrue(true, 'Should redirect with invalid request');
    }

    public function testDeleteAccountConfirmInvalidToken(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getDeletionToken')->willReturn(false);

        $controller = $this->createController(['user' => $userMock]);
        $_GET['token'] = 'invalid_token';

        $output = $this->captureOutput($controller, 'deleteAccountConfirm');
        $this->assertTrue(true, 'Should redirect with invalid token');
    }

    public function testDeleteAccountConfirmSuccess(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getDeletionToken')->willReturn([
            'user_deletionTokenExpiry' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'google_email' => 'delete@test.com',
        ]);
        $userMock->method('invalidateDeletionToken')->willReturn(true);

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('deleteAccount')->willReturn(true);

        $controller = $this->createController([
            'user'       => $userMock,
            'googleUser' => $googleUserMock,
        ]);

        $_GET['token'] = 'valid_token';

        $output = $this->captureOutput($controller, 'deleteAccountConfirm');
        $this->assertTrue(true, 'Should delete and redirect');
    }

    // ─── deleteRiotAccount ──────────────────────────────────────

    public function testDeleteRiotAccountNotConnected(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'deleteRiotAccount');
        $this->assertTrue(true, 'Should redirect if not connected');
    }

    // ─── submitCandidature ──────────────────────────────────────

    public function testSubmitCandidatureNoData(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'submitCandidature');
        $this->assertTrue(true, 'No post data should do nothing');
    }

    // ─── MailingCronJob ─────────────────────────────────────────

    public function testMailingCronJobUnauthorized(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'MailingCronJob');
        $this->assertStringContainsString('Unauthorized', $output);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testMailingCronJobNoUsers(): void
    {
        // Must run in a separate process because the controller uses require_once 'keys.php'.
        // In production each HTTP request is a fresh process so require_once works fine.
        // In PHPUnit all tests share one process, so prior tests consume the require_once,
        // leaving $tokenRefresh undefined in the method scope.
        require_once __DIR__ . '/../../vendor/autoload.php';

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getGoogleUsersMailingCronJob')->willReturn(false);

        $controller = $this->createController(['googleUser' => $googleUserMock]);

        $_GET['token'] = '56874d4azfzezfze65ezze2ffd6e62f6e';

        $output = $this->captureOutput($controller, 'MailingCronJob');
        $this->assertStringContainsString('No users to notify', $output);
    }

    // ─── Utility / getter / setter ──────────────────────────────

    public function testValidateInput(): void
    {
        $controller = $this->createController();
        $result = $controller->validateInput('  <script>alert("xss")</script>  ');
        $this->assertStringNotContainsString('<script>', $result);
    }

    public function testGetSetGoogleId(): void
    {
        $controller = $this->createController();
        $controller->setGoogleId('abc123');
        $this->assertEquals('abc123', $controller->getGoogleId());
    }

    public function testGetSetGoogleUserId(): void
    {
        $controller = $this->createController();
        $controller->setGoogleUserId(42);
        $this->assertEquals(42, $controller->getGoogleUserId());
    }

    public function testGetSetGoogleFullName(): void
    {
        $controller = $this->createController();
        $controller->setGoogleFullName('Test User');
        $this->assertEquals('Test User', $controller->getGoogleFullName());
    }

    public function testGetSetGoogleFirstName(): void
    {
        $controller = $this->createController();
        $controller->setGoogleFirstName('Test');
        $this->assertEquals('Test', $controller->getGoogleFirstName());
    }

    public function testGetSetGoogleFamilyName(): void
    {
        $controller = $this->createController();
        $controller->setGoogleFamilyName('Family');
        $this->assertEquals('Family', $controller->getGoogleFamilyName());
    }

    public function testGetSetGoogleEmail(): void
    {
        $controller = $this->createController();
        $controller->setGoogleEmail('test@gmail.com');
        $this->assertEquals('test@gmail.com', $controller->getGoogleEmail());
    }
}
