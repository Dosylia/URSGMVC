<?php

namespace tests\controllers;

use tests\BaseControllerTestCase;
use controllers\UserController;
use models\User;
use models\FriendRequest;
use models\ChatMessage;
use models\LeagueOfLegends;
use models\Valorant;
use models\UserLookingFor;
use models\MatchingScore;
use models\Items;
use models\GoogleUser;
use models\Report;
use models\RatingGames;

class UserControllerTest extends BaseControllerTestCase
{
    private function createController(array $mockOverrides = []): UserController
    {
        $defaults = [
            'user' => $this->createMock(User::class),
            'friendrequest' => $this->createMock(FriendRequest::class),
            'chatmessage' => $this->createMock(ChatMessage::class),
            'leagueoflegends' => $this->createMock(LeagueOfLegends::class),
            'valorant' => $this->createMock(Valorant::class),
            'userlookingfor' => $this->createMock(UserLookingFor::class),
            'matchingscore' => $this->createMock(MatchingScore::class),
            'items' => $this->createMock(Items::class),
            'googleUser' => $this->createMock(GoogleUser::class),
            'report' => $this->createMock(Report::class),
            'rating' => $this->createMock(RatingGames::class),
        ];
        $mocks = array_merge($defaults, $mockOverrides);

        return $this->createControllerWithMocks(UserController::class, $mocks);
    }

    // ─── getAllUsersPhone ────────────────────────────────────────

    public function testGetAllUsersPhoneNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'getAllUsersPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testGetAllUsersPhoneSuccess(): void
    {
        // This uses $validToken from keys.php — include it
        $this->setBearerToken('56874d4zezfze656e2f6e62f6e');

        $userMock = $this->createMock(User::class);
        $userMock->method('getAllUsers')->willReturn([
            $this->fakeUser(),
        ]);

        $controller = $this->createController(['user' => $userMock]);

        $_POST['allUsers'] = true;

        $result = $this->captureJsonOutput($controller, 'getAllUsersPhone');
        $this->assertNotNull($result);
        $this->assertEquals('Success', $result['message']);
        $this->assertArrayHasKey('allUsers', $result);
        $this->assertIsArray($result['allUsers']);
        $this->assertCount(1, $result['allUsers']);
        $this->assertEquals(1, $result['allUsers'][0]['user_id']);
        $this->assertEquals('TestUser', $result['allUsers'][0]['user_username']);
    }

    // ─── getLeaderboardUsers ────────────────────────────────────

    public function testGetLeaderboardUsersNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'getLeaderboardUsers');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── getUserData ────────────────────────────────────────────

    public function testGetUserDataSuccess(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser());

        $controller = $this->createController(['user' => $userMock]);

        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'getUserData');
        $this->assertNotNull($result);
        $this->assertEquals('Success', $result['message']);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals(1, $result['user']['user_id']);
        $this->assertEquals('TestUser', $result['user']['user_username']);
    }

    public function testGetUserDataNoPost(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'getUserData');
        $this->assertNotNull($result);
    }

    // ─── getUserMatching ────────────────────────────────────────

    public function testGetUserMatchingSuccess(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser(['lf_filteredServer' => null]));
        $userMock->method('getAllUsersExceptFriendsLimit')->willReturn([]);

        $frMock = $this->createMock(FriendRequest::class);
        $frMock->method('skipUserSwipping')->willReturn([]);

        $matchMock = $this->createMock(MatchingScore::class);
        $matchMock->method('getMatchingScore')->willReturn([]);

        $controller = $this->createController([
            'user' => $userMock,
            'friendrequest' => $frMock,
            'matchingscore' => $matchMock,
        ]);

        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'getUserMatching');
        $this->assertNotNull($result);
        $this->assertArrayHasKey('success', $result);
        // With empty user lists, controller returns no-match path
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('matching3', $result);
    }

    // ─── getCurrency ────────────────────────────────────────────

    public function testGetCurrencySuccess(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getCurrencyByUserId')->willReturn(['user_currency' => 500]);

        $controller = $this->createController(['user' => $userMock]);

        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'getCurrency');
        $this->assertNotNull($result);
        $this->assertEquals('Success', $result['message']);
        $this->assertArrayHasKey('currency', $result);
        $this->assertEquals(500, $result['currency']['user_currency']);
    }

    // ─── getCurrencyWebsite ─────────────────────────────────────

    public function testGetCurrencyWebsiteNoBearer(): void
    {
        $controller = $this->createController();
        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'getCurrencyWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testGetCurrencyWebsiteSuccess(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getCurrencyByUserId')->willReturn(['user_currency' => 500]);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
        ]);

        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'getCurrencyWebsite');
        $this->assertNotNull($result);
        $this->assertEquals('Success', $result['message']);
        $this->assertArrayHasKey('currency', $result);
        $this->assertEquals(500, $result['currency']['user_currency']);
    }

    // ─── registerToken ──────────────────────────────────────────

    public function testRegisterTokenNoBearer(): void
    {
        $controller = $this->createController();
        $_POST['userId'] = '1';
        $_POST['token'] = 'some_token';

        $result = $this->captureJsonOutput($controller, 'registerToken');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── updateSocialsWebsite ───────────────────────────────────

    public function testUpdateSocialsWebsiteNoBearer(): void
    {
        $controller = $this->createController();
        $_POST['param'] = json_encode([
            'userId' => 1,
            'discord' => 'test#1234',
            'twitter' => '@test',
            'instagram' => 'testgram',
            'twitch' => 'teststream',
            'bluesky' => 'test.bsky',
        ]);

        $result = $this->captureJsonOutput($controller, 'updateSocialsWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testUpdateSocialsWebsiteSuccess(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn(['user_username' => 'testuser']);
        $userMock->method('updateSocial2')->willReturn(true);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
        ]);

        $_POST['param'] = json_encode([
            'userId' => 1,
            'discord' => 'test#1234',
            'twitter' => '@test',
            'instagram' => 'testgram',
            'twitch' => 'teststream',
            'bluesky' => 'test.bsky',
        ]);

        $result = $this->captureJsonOutput($controller, 'updateSocialsWebsite');
        $this->assertNotNull($result);
        $this->assertEquals('Success', $result['message']);
    }

    // ─── updateSocialPhone ──────────────────────────────────────

    public function testUpdateSocialPhoneNoBearer(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getUserByUsername')->willReturn($this->fakeUser());

        $controller = $this->createController(['user' => $userMock]);
        $_POST['userData'] = json_encode([
            'username' => 'TestUser',
            'discord' => 'test#1234',
            'twitter' => '@test',
            'instagram' => 'testgram',
            'twitch' => 'teststream',
            'bluesky' => 'test.bsky',
        ]);

        $result = $this->captureJsonOutput($controller, 'updateSocialPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── updatePicturePhone ─────────────────────────────────────

    public function testUpdatePicturePhoneNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'updatePicturePhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── updateBonusPicturePhone ────────────────────────────────

    public function testUpdateBonusPicturePhoneNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'updateBonusPicturePhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── deleteBonusPicturePhone ────────────────────────────────

    public function testDeleteBonusPicturePhoneNoBearer(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser());

        $controller = $this->createController(['user' => $userMock]);
        $_POST['fileName'] = 'test.png';
        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'deleteBonusPicturePhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── deleteBonusPicture (website) ───────────────────────────

    public function testDeleteBonusPictureNoBearer(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser());

        $controller = $this->createController(['user' => $userMock]);
        $_POST['fileName'] = 'test.png';
        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'deleteBonusPicture');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── createUserPhone ────────────────────────────────────────

    public function testCreateUserPhoneNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'createUserPhone');
        $this->assertNotNull($result);
        $this->assertEquals('Error', $result['message'] ?? '');
    }

    // ─── createAccountSkipPreferences ───────────────────────────

    public function testCreateAccountSkipPreferencesNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'createAccountSkipPreferences');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── updateUserPhone ────────────────────────────────────────

    public function testUpdateUserPhoneNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'updateUserPhone');
        // Without POST['userData'], controller echoes {'message': 'Error'}
        $this->assertNotNull($result);
        $this->assertEquals('Error', $result['message'] ?? '');
    }

    // ─── chatFilterSwitch ───────────────────────────────────────

    public function testChatFilterSwitchNoBearer(): void
    {
        $controller = $this->createController();
        $_POST['param'] = json_encode(['userId' => 1, 'status' => 1]);

        $result = $this->captureJsonOutput($controller, 'chatFilterSwitch');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── chatFilterSwitchWebsite ────────────────────────────────

    public function testChatFilterSwitchWebsiteNoBearer(): void
    {
        $controller = $this->createController();
        $_POST['param'] = json_encode(['userId' => 1, 'status' => 1]);

        $result = $this->captureJsonOutput($controller, 'chatFilterSwitchWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testChatFilterSwitchWebsiteSuccess(): void
    {
        $this->setBearerToken('valid_token');
        $this->simulateLoggedInUser(1);

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser());
        $userMock->method('updateFilter')->willReturn(true);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
        ]);

        $_POST['param'] = json_encode([
            'userId' => 1,
            'status' => 1,
        ]);

        $result = $this->captureJsonOutput($controller, 'chatFilterSwitchWebsite');
        $this->assertNotNull($result);
        $this->assertEquals('Success', $result['message']);
    }

    // ─── arcaneSide ─────────────────────────────────────────────

    public function testArcaneSideSuccess(): void
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('updateSide')->willReturn(true);
        $userMock->method('getUserById')->willReturn($this->fakeUser());

        $controller = $this->createController(['user' => $userMock]);

        $_POST['pick'] = true;
        $_POST['side'] = 'Piltover';
        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'arcaneSide');
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('Piltover', $result['side']);
    }

    // ─── arcaneSideWebsite ──────────────────────────────────────

    public function testArcaneSideWebsiteSuccess(): void
    {
        $this->simulateLoggedInUser(1);

        $userMock = $this->createMock(User::class);
        $userMock->method('updateSide')->willReturn(true);
        $userMock->method('getUserById')->willReturn($this->fakeUser());

        $controller = $this->createController(['user' => $userMock]);

        $_POST['pick'] = true;
        $_POST['side'] = 'Zaun';
        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'arcaneSideWebsite');
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('Zaun', $result['side']);
    }

    // ─── reportUserWebsite ──────────────────────────────────────

    public function testReportUserWebsiteNoBearer(): void
    {
        $controller = $this->createController();
        $_POST['param'] = json_encode([
            'userId' => 1,
            'reportedId' => 2,
            'reason' => 'Spam',
            'status' => 'pending',
            'content' => 'Test content',
        ]);

        $result = $this->captureJsonOutput($controller, 'reportUserWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testReportUserWebsiteSuccess(): void
    {
        $this->setBearerToken('valid_token');
        $this->simulateLoggedInUser(1);

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser());

        $reportMock = $this->createMock(Report::class);
        $reportMock->method('reportUser')->willReturn(true);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
            'report' => $reportMock,
        ]);

        $_POST['param'] = json_encode([
            'userId' => 1,
            'reportedId' => 2,
            'reason' => 'Spam',
            'status' => 'pending',
            'content' => 'Offensive content',
        ]);

        $result = $this->captureJsonOutput($controller, 'reportUserWebsite');
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('Reported successfully', $result['message']);
    }

    // ─── reportUserPhone ────────────────────────────────────────

    public function testReportUserPhoneNoBearer(): void
    {
        $controller = $this->createController();
        $_POST['param'] = json_encode([
            'userId' => 1,
            'reportedId' => 2,
            'reason' => 'Spam',
            'status' => 'pending',
            'content' => 'Test content',
        ]);

        $result = $this->captureJsonOutput($controller, 'reportUserPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── userIsLookingForGameWebsite ────────────────────────────

    public function testUserIsLookingForGameWebsiteNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'userIsLookingForGameWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testUserIsLookingForGameWebsiteSuccess(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser());
        $userMock->method('userIsLookingForGame')->willReturn(true);
        $userMock->method('getLastRequestTime')->willReturn(time() - 200);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
        ]);

        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'userIsLookingForGameWebsite');
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('Status updated', $result['message']);
        $this->assertArrayHasKey('oldTime', $result);
    }

    // ─── userIsLookingForGamePhone ──────────────────────────────

    public function testUserIsLookingForGamePhoneNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'userIsLookingForGamePhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── updateNotificationPermission ───────────────────────────

    public function testUpdateNotificationPermissionNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'updateNotificationPermission');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── saveNotificationSubscription ───────────────────────────

    public function testSaveNotificationSubscriptionNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'saveNotificationSubscription');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── fetchNotificationEndpoint ──────────────────────────────

    public function testFetchNotificationEndpointNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'fetchNotificationEndpoint');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── markInactiveUsersOffline (cron) ────────────────────────

    public function testMarkInactiveUsersOfflineWrongToken(): void
    {
        $controller = $this->createController();
        $_GET['token'] = 'wrong';

        $output = $this->captureOutput($controller, 'markInactiveUsersOffline');
        $this->assertIsString($output);
    }

    // ─── getPersonalityTestResult ───────────────────────────────

    public function testGetPersonalityTestResultNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'getPersonalityTestResult');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testGetPersonalityTestResultSuccess(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getPersonalityTestResult')->willReturn(
            json_encode(['type' => 'INTJ'])
        );

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
        ]);

        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'getPersonalityTestResult');
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('result', $result);
        $this->assertEquals('INTJ', $result['result']['type']);
    }

    // ─── getMatchingPersonalityUser ─────────────────────────────

    public function testGetMatchingPersonalityUserNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'getMatchingPersonalityUser');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── rateFriendWebsite ──────────────────────────────────────

    public function testRateFriendWebsiteNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'rateFriendWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testRateFriendWebsiteSuccess(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $ratingMock = $this->createMock(RatingGames::class);
        $ratingMock->method('getRatingByUserAndFriend')->willReturn(null);
        $ratingMock->method('insertFirstRating')->willReturn(true);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser(['user_id' => 2]));

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'rating' => $ratingMock,
            'user' => $userMock,
        ]);

        $_POST['friendId'] = '2';
        $_POST['matchId'] = 'match_123';
        $_POST['rating'] = '5';
        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'rateFriendWebsite');
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
    }

    // ─── switchPersonalColorWebsite ─────────────────────────────

    public function testSwitchPersonalColorWebsiteNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'switchPersonalColorWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testSwitchPersonalColorWebsiteSuccess(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('updatePersonalColor')->willReturn(true);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
        ]);

        $_POST['color'] = '#ff5733';
        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'switchPersonalColorWebsite');
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('Color updated successfully', $result['message']);
    }

    // ─── Pure/utility functions ─────────────────────────────────

    public function testValidateInput(): void
    {
        $controller = $this->createController();
        $result = $controller->validateInput('<script>bad</script>');
        $this->assertStringNotContainsString('<script>', $result);
    }

    public function testInvalidUidTooLong(): void
    {
        $controller = $this->createController();
        $result = $controller->invalidUid('aaaaabbbbbcccccddddde'); // 21 chars
        $this->assertTrue($result);
    }

    public function testInvalidUidValid(): void
    {
        $controller = $this->createController();
        $result = $controller->invalidUid('ValidUser123');
        $this->assertFalse($result);
    }

    public function testInvalidUidSpecialChars(): void
    {
        $controller = $this->createController();
        $result = $controller->invalidUid('user@name!');
        $this->assertTrue($result);
    }

    public function testEmptyInputSignup(): void
    {
        $controller = $this->createController();
        $this->assertTrue($controller->emptyInputSignup('', 25, 'bio'));
        $this->assertTrue($controller->emptyInputSignup('user', '', 'bio'));
        $this->assertTrue($controller->emptyInputSignup('user', 25, ''));
        $this->assertFalse($controller->emptyInputSignup('user', 25, 'bio'));
    }

    public function testEmptyInputSignupUpdate(): void
    {
        $controller = $this->createController();
        $this->assertTrue($controller->emptyInputSignupUpdate('', 'bio'));
        $this->assertTrue($controller->emptyInputSignupUpdate(25, ''));
        $this->assertFalse($controller->emptyInputSignupUpdate(25, 'bio'));
    }

    public function testAdjustBrightness(): void
    {
        $controller = $this->createController();
        $result = $controller->adjustBrightness('#ffffff', -50);
        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^#[0-9a-fA-F]{6}$/', $result);
    }

    public function testIsUsernameForbidden(): void
    {
        $controller = $this->createController();
        // This checks against bannedUsernames.php list
        $result = $controller->isUsernameForbidden('NormalUser');
        // Should return false for a normal username
        $this->assertIsBool($result);
    }

    // ─── switchRandomChatPermission ─────────────────────────────

    public function testSwitchRandomChatPermissionNoParam(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'switchRandomChatPermission');
        $this->assertNull($result);
    }

    public function testSwitchRandomChatPermissionNoBearer(): void
    {
        $controller = $this->createController();
        $_POST['param'] = json_encode(['userId' => 1, 'status' => 1]);

        $result = $this->captureJsonOutput($controller, 'switchRandomChatPermission');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testSwitchRandomChatPermissionInvalidToken(): void
    {
        $this->setBearerToken('invalid_token');
        $this->simulateLoggedInUser(1);

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'different_token',
        ]);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
        ]);

        $_POST['param'] = json_encode(['userId' => 1, 'status' => 1]);

        $result = $this->captureJsonOutput($controller, 'switchRandomChatPermission');
        $this->assertNotNull($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid token', $result['message']);
    }

    public function testSwitchRandomChatPermissionUserMismatch(): void
    {
        $this->setBearerToken('valid_token');
        $this->simulateLoggedInUser(1);

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser(['user_id' => 999]));

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
        ]);

        $_POST['param'] = json_encode(['userId' => 1, 'status' => 1]);

        $result = $this->captureJsonOutput($controller, 'switchRandomChatPermission');
        $this->assertNotNull($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Request not allowed', $result['message']);
    }

    public function testSwitchRandomChatPermissionSuccess(): void
    {
        $this->setBearerToken('valid_token');
        $this->simulateLoggedInUser(1);

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser());
        $userMock->method('updateRandomChatPermission')->willReturn(true);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
        ]);

        $_POST['param'] = json_encode(['userId' => 1, 'status' => 1]);

        $result = $this->captureJsonOutput($controller, 'switchRandomChatPermission');
        $this->assertNotNull($result);
        $this->assertEquals('Success', $result['message']);
    }

    public function testSwitchRandomChatPermissionUpdateFails(): void
    {
        $this->setBearerToken('valid_token');
        $this->simulateLoggedInUser(1);

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser());
        $userMock->method('updateRandomChatPermission')->willReturn(false);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
        ]);

        $_POST['param'] = json_encode(['userId' => 1, 'status' => 0]);

        $result = $this->captureJsonOutput($controller, 'switchRandomChatPermission');
        $this->assertNotNull($result);
        $this->assertEquals('Couldnt update status', $result['message']);
    }

    // ─── getRandomChatPermission ────────────────────────────────

    public function testGetRandomChatPermissionNoParam(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'getRandomChatPermission');
        $this->assertNull($result);
    }

    public function testGetRandomChatPermissionNoBearer(): void
    {
        $controller = $this->createController();
        $_POST['param'] = json_encode(['userId' => 1]);

        $result = $this->captureJsonOutput($controller, 'getRandomChatPermission');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testGetRandomChatPermissionInvalidToken(): void
    {
        $this->setBearerToken('invalid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenByUserId')->willReturn([
            'google_masterToken' => 'different_token',
        ]);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
        ]);

        $_POST['param'] = json_encode(['userId' => 1]);

        $result = $this->captureJsonOutput($controller, 'getRandomChatPermission');
        $this->assertNotNull($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid token', $result['message']);
    }

    public function testGetRandomChatPermissionSuccess(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenByUserId')->willReturn([
            'google_masterToken' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getRandomChatPermission')->willReturn(1);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
        ]);

        $_POST['param'] = json_encode(['userId' => 1]);

        $result = $this->captureJsonOutput($controller, 'getRandomChatPermission');
        $this->assertNotNull($result);
        $this->assertEquals('Success', $result['message']);
        $this->assertEquals(1, $result['permission']);
    }

    public function testGetRandomChatPermissionReturnsZero(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenByUserId')->willReturn([
            'google_masterToken' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getRandomChatPermission')->willReturn(0);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
        ]);

        $_POST['param'] = json_encode(['userId' => 1]);

        $result = $this->captureJsonOutput($controller, 'getRandomChatPermission');
        $this->assertNotNull($result);
        $this->assertEquals('Success', $result['message']);
        $this->assertEquals(0, $result['permission']);
    }

    public function testGetRandomChatPermissionNull(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenByUserId')->willReturn([
            'google_masterToken' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getRandomChatPermission')->willReturn(null);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
        ]);

        $_POST['param'] = json_encode(['userId' => 1]);

        $result = $this->captureJsonOutput($controller, 'getRandomChatPermission');
        $this->assertNotNull($result);
        $this->assertEquals('Couldnt fetch status', $result['message']);
    }
}
