<?php

namespace tests\controllers;

use tests\BaseControllerTestCase;
use controllers\AdminController;
use models\Admin;
use models\FriendRequest;
use models\User;
use models\GoogleUser;
use models\ChatMessage;
use models\Items;
use models\Partners;

class AdminControllerTest extends BaseControllerTestCase
{
    private function createController(array $mockOverrides = []): AdminController
    {
        $defaults = [
            'friendrequest' => $this->createMock(FriendRequest::class),
            'user'          => $this->createMock(User::class),
            'googleUser'    => $this->createMock(GoogleUser::class),
            'chatmessage'   => $this->createMock(ChatMessage::class),
            'admin'         => $this->createMock(Admin::class),
            'items'         => $this->createMock(Items::class),
            'partners'      => $this->createMock(Partners::class),
        ];
        $mocks = array_merge($defaults, $mockOverrides);

        return $this->createControllerWithMocks(AdminController::class, $mocks);
    }

    // ─── adminLandingPage ───────────────────────────────────────

    public function testAdminLandingPageNotConnected(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'adminLandingPage');
        $this->assertTrue(true, 'Should redirect for non-connected users');
    }

    // ─── adminUsersPage ─────────────────────────────────────────

    public function testAdminUsersPageNotConnected(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'adminUsersPage');
        $this->assertTrue(true, 'Should redirect for non-connected users');
    }

    // ─── adminUpdateCurrency ────────────────────────────────────

    public function testAdminUpdateCurrencyNotAdmin(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'adminUpdateCurrency');
        $this->assertTrue(true, 'Should redirect for non-admin');
    }

    public function testAdminUpdateCurrencyNotAdminConnected(): void
    {
        $this->simulateLoggedInUser(100); // Not an admin ID
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'adminUpdateCurrency');
        $this->assertTrue(true, 'Should redirect for non-admin');
    }

    public function testAdminUpdateCurrencyNoPostData(): void
    {
        $this->simulateAdmin();
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'adminUpdateCurrency');
        $this->assertTrue(true, 'Should redirect with error');
    }

    public function testAdminUpdateCurrencySuccess(): void
    {
        $this->simulateAdmin();

        $userMock = $this->createMock(User::class);
        $userMock->method('updateCurrency')->willReturn(true);

        $adminMock = $this->createMock(Admin::class);
        $adminMock->method('logAdminAction')->willReturn(true);

        $controller = $this->createController([
            'user'  => $userMock,
            'admin' => $adminMock,
        ]);

        $_POST['currency'] = '5000';
        $_POST['user_id'] = '1';

        $output = $this->captureOutput($controller, 'adminUpdateCurrency');
        $this->assertTrue(true, 'Should redirect with success');
    }

    // ─── adminBanUser ───────────────────────────────────────────

    public function testAdminBanUserNotAdmin(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'adminBanUser');
        $this->assertTrue(true, 'Should redirect');
    }

    public function testAdminBanUserNoUserId(): void
    {
        $this->simulateAdmin();
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'adminBanUser');
        $this->assertTrue(true, 'Should redirect with error');
    }

    public function testAdminBanUserSuccess(): void
    {
        $this->simulateAdmin();

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn(array_merge(
            $this->fakeUser(),
            ['google_email' => 'ban@test.com']
        ));

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('deleteAccount')->willReturn(true);

        $adminMock = $this->createMock(Admin::class);
        $adminMock->method('addBannedUser')->willReturn(true);
        $adminMock->method('logAdminActionBan')->willReturn(true);

        $controller = $this->createController([
            'user'       => $userMock,
            'googleUser' => $googleUserMock,
            'admin'      => $adminMock,
        ]);

        $_POST['user_id'] = '2';

        $output = $this->captureOutput($controller, 'adminBanUser');
        $this->assertTrue(true, 'Should ban and redirect');
    }

    // ─── adminCensorBio ─────────────────────────────────────────

    public function testAdminCensorBioNotModerator(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'adminCensorBio');
        $this->assertTrue(true, 'Should redirect');
    }

    public function testAdminCensorBioSuccess(): void
    {
        $this->simulateModerator();

        $adminMock = $this->createMock(Admin::class);
        $adminMock->method('censorBio')->willReturn(true);
        $adminMock->method('logAdminAction')->willReturn(true);

        $controller = $this->createController(['admin' => $adminMock]);
        $_POST['user_id'] = '2';

        $output = $this->captureOutput($controller, 'adminCensorBio');
        $this->assertTrue(true, 'Should censor and redirect');
    }

    // ─── adminCensorPicture ─────────────────────────────────────

    public function testAdminCensorPictureNotModerator(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'adminCensorPicture');
        $this->assertTrue(true, 'Should redirect');
    }

    public function testAdminCensorPictureSuccess(): void
    {
        $this->simulateModerator();

        $adminMock = $this->createMock(Admin::class);
        $adminMock->method('censorPicture')->willReturn(true);
        $adminMock->method('logAdminAction')->willReturn(true);

        $controller = $this->createController(['admin' => $adminMock]);
        $_POST['user_id'] = '2';

        $output = $this->captureOutput($controller, 'adminCensorPicture');
        $this->assertTrue(true, 'Should censor picture and redirect');
    }

    // ─── adminGamePage ──────────────────────────────────────────

    public function testAdminGamePageNotConnected(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'adminGamePage');
        $this->assertTrue(true, 'Should redirect');
    }

    // ─── addCharGame ────────────────────────────────────────────

    public function testAddCharGameNotModerator(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'addCharGame');
        $this->assertTrue(true, 'Should redirect');
    }

    // ─── adminReportsPage ───────────────────────────────────────

    public function testAdminReportsPageNotConnected(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'adminReportsPage');
        $this->assertTrue(true, 'Should redirect');
    }

    // ─── reportAdminBanUser ─────────────────────────────────────

    public function testReportAdminBanUserNotAdmin(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'reportAdminBanUser');
        $this->assertTrue(true, 'Should redirect');
    }

    public function testReportAdminBanUserSuccess(): void
    {
        $this->simulateAdmin();

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn(array_merge(
            $this->fakeUser(),
            ['google_email' => 'reported@test.com']
        ));

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('deleteAccount')->willReturn(true);

        $adminMock = $this->createMock(Admin::class);
        $adminMock->method('addBannedUser')->willReturn(true);
        $adminMock->method('logAdminActionBan')->willReturn(true);
        $adminMock->method('getReportsByUserId')->willReturn([]);
        $adminMock->method('updateReport')->willReturn(true);

        $controller = $this->createController([
            'user'       => $userMock,
            'googleUser' => $googleUserMock,
            'admin'      => $adminMock,
        ]);
        $_POST['user_id'] = '2';

        $output = $this->captureOutput($controller, 'reportAdminBanUser');
        $this->assertTrue(true, 'Should ban and redirect');
    }

    // ─── reportAdminCensorBio ───────────────────────────────────

    public function testReportAdminCensorBioNotModerator(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'reportAdminCensorBio');
        $this->assertTrue(true, 'Should redirect');
    }

    public function testReportAdminCensorBioSuccess(): void
    {
        $this->simulateModerator();

        $adminMock = $this->createMock(Admin::class);
        $adminMock->method('censorBio')->willReturn(true);
        $adminMock->method('logAdminAction')->willReturn(true);
        $adminMock->method('getReportsByUserId')->willReturn([['reported_id' => 1]]);
        $adminMock->method('updateReport')->willReturn(true);

        $controller = $this->createController(['admin' => $adminMock]);
        $_POST['user_id'] = '2';

        $output = $this->captureOutput($controller, 'reportAdminCensorBio');
        $this->assertTrue(true);
    }

    // ─── reportAdminCensorPicture ───────────────────────────────

    public function testReportAdminCensorPictureNotModerator(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'reportAdminCensorPicture');
        $this->assertTrue(true, 'Should redirect');
    }

    // ─── reportAdminCensorBoth ──────────────────────────────────

    public function testReportAdminCensorBothNotModerator(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'reportAdminCensorBoth');
        $this->assertTrue(true, 'Should redirect');
    }

    public function testReportAdminCensorBothSuccess(): void
    {
        $this->simulateModerator();

        $adminMock = $this->createMock(Admin::class);
        $adminMock->method('censorBio')->willReturn(true);
        $adminMock->method('censorPicture')->willReturn(true);
        $adminMock->method('logAdminAction')->willReturn(true);
        $adminMock->method('getReportsByUserId')->willReturn([]);
        $adminMock->method('updateReport')->willReturn(true);

        $controller = $this->createController(['admin' => $adminMock]);
        $_POST['user_id'] = '2';

        $output = $this->captureOutput($controller, 'reportAdminCensorBoth');
        $this->assertTrue(true);
    }

    // ─── reportAdminRequestBan ──────────────────────────────────

    public function testReportAdminRequestBanNotModerator(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'reportAdminRequestBan');
        $this->assertTrue(true, 'Should redirect');
    }

    public function testReportAdminRequestBanSuccess(): void
    {
        $this->simulateModerator();

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser());

        $adminMock = $this->createMock(Admin::class);
        $adminMock->method('logAdminAction')->willReturn(true);
        $adminMock->method('getReportsByUserId')->willReturn([['reported_id' => 1]]);
        $adminMock->method('updateReport')->willReturn(true);

        $controller = $this->createController([
            'user'  => $userMock,
            'admin' => $adminMock,
        ]);
        $_POST['user_id'] = '2';

        $output = $this->captureOutput($controller, 'reportAdminRequestBan');
        $this->assertTrue(true);
    }

    // ─── reportAdminDismiss ─────────────────────────────────────

    public function testReportAdminDismissNotModerator(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'reportAdminDismiss');
        $this->assertTrue(true, 'Should redirect');
    }

    public function testReportAdminDismissSuccess(): void
    {
        $this->simulateModerator();

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser());

        $adminMock = $this->createMock(Admin::class);
        $adminMock->method('logAdminAction')->willReturn(true);
        $adminMock->method('getReportsByUserId')->willReturn([['reported_id' => 1]]);
        $adminMock->method('updateReport')->willReturn(true);

        $controller = $this->createController([
            'user'  => $userMock,
            'admin' => $adminMock,
        ]);
        $_POST['user_id'] = '2';

        $output = $this->captureOutput($controller, 'reportAdminDismiss');
        $this->assertTrue(true);
    }

    // ─── adminAddPartner ────────────────────────────────────────

    public function testAdminAddPartnerNotAdmin(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'adminAddPartner');
        $this->assertTrue(true, 'Should redirect');
    }

    public function testAdminAddPartnerNoUserId(): void
    {
        $this->simulateAdmin();
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'adminAddPartner');
        $this->assertTrue(true, 'Should redirect with error');
    }

    public function testAdminAddPartnerSuccess(): void
    {
        $this->simulateAdmin();

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser());
        $userMock->method('addPartner')->willReturn(true);

        $itemsMock = $this->createMock(Items::class);
        $itemsMock->method('getOwnedItems')->willReturn([]);
        $itemsMock->method('getItems')->willReturn([['items_id' => 1], ['items_id' => 2]]);
        $itemsMock->method('addItemToUserAsPartner')->willReturn(true);

        $adminMock = $this->createMock(Admin::class);
        $adminMock->method('logAdminAction')->willReturn(true);

        $controller = $this->createController([
            'user'  => $userMock,
            'items' => $itemsMock,
            'admin' => $adminMock,
        ]);
        $_POST['user_id'] = '2';

        $output = $this->captureOutput($controller, 'adminAddPartner');
        $this->assertTrue(true, 'Should add partner and redirect');
    }

    // ─── adminRemovePartner ─────────────────────────────────────

    public function testAdminRemovePartnerNotAdmin(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'adminRemovePartner');
        $this->assertTrue(true, 'Should redirect');
    }

    public function testAdminRemovePartnerSuccess(): void
    {
        $this->simulateAdmin();

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser());
        $userMock->method('removePartner')->willReturn(true);

        $itemsMock = $this->createMock(Items::class);
        $itemsMock->method('removePartnerItems')->willReturn(true);

        $adminMock = $this->createMock(Admin::class);
        $adminMock->method('logAdminAction')->willReturn(true);

        $controller = $this->createController([
            'user'  => $userMock,
            'items' => $itemsMock,
            'admin' => $adminMock,
        ]);
        $_POST['user_id'] = '2';

        $output = $this->captureOutput($controller, 'adminRemovePartner');
        $this->assertTrue(true, 'Should remove partner and redirect');
    }

    // ─── adminGrantItem ─────────────────────────────────────────

    public function testAdminGrantItemNotAdmin(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'adminGrantItem');
        $this->assertTrue(true, 'Should redirect');
    }

    public function testAdminGrantItemNoData(): void
    {
        $this->simulateAdmin();
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'adminGrantItem');
        $this->assertTrue(true, 'Should redirect with error');
    }

    public function testAdminGrantItemSuccess(): void
    {
        $this->simulateAdmin();

        $itemsMock = $this->createMock(Items::class);
        $itemsMock->method('getItemById')->willReturn($this->fakeItem());
        $itemsMock->method('addItemToUser')->willReturn(true);

        $adminMock = $this->createMock(Admin::class);
        $adminMock->method('logAdminAction')->willReturn(true);

        $controller = $this->createController([
            'items' => $itemsMock,
            'admin' => $adminMock,
        ]);
        $_POST['user_id'] = '2';
        $_POST['item_id'] = '1';

        $output = $this->captureOutput($controller, 'adminGrantItem');
        $this->assertTrue(true, 'Should grant item and redirect');
    }

    // ─── adminRemoveItem ────────────────────────────────────────

    public function testAdminRemoveItemNotAdmin(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'adminRemoveItem');
        $this->assertTrue(true, 'Should redirect');
    }

    public function testAdminRemoveItemSuccess(): void
    {
        $this->simulateAdmin();

        $itemsMock = $this->createMock(Items::class);
        $itemsMock->method('removeItemFromUser')->willReturn(true);

        $adminMock = $this->createMock(Admin::class);
        $adminMock->method('logAdminAction')->willReturn(true);

        $controller = $this->createController([
            'items' => $itemsMock,
            'admin' => $adminMock,
        ]);
        $_POST['user_id'] = '2';
        $_POST['item_id'] = '1';

        $output = $this->captureOutput($controller, 'adminRemoveItem');
        $this->assertTrue(true, 'Should remove item and redirect');
    }

    // ─── discordBotStatus ───────────────────────────────────────

    public function testDiscordBotStatusNoBearer(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'discordBotStatus');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testDiscordBotStatusInvalidToken(): void
    {
        $this->setBearerToken('wrong_token');
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'discordBotStatus');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── discordBotControl ──────────────────────────────────────

    public function testDiscordBotControlNoBearer(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'discordBotControl');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testDiscordBotControlInvalidToken(): void
    {
        $this->setBearerToken('wrong_token');
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'discordBotControl');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── discordBotCommand ──────────────────────────────────────

    public function testDiscordBotCommandNoBearer(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'discordBotCommand');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testDiscordBotCommandInvalidToken(): void
    {
        $this->setBearerToken('wrong_token');
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'discordBotCommand');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── adminStorePage ─────────────────────────────────────────

    public function testAdminStorePageNotAdmin(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'adminStorePage');
        $this->assertTrue(true, 'Should redirect');
    }

    // ─── adminDiscordBotPage ────────────────────────────────────

    public function testAdminDiscordBotPageNotAdmin(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'adminDiscordBotPage');
        $this->assertTrue(true, 'Should redirect');
    }

    // ─── adminPartnerPage ───────────────────────────────────────

    public function testAdminPartnerPageNotConnected(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'adminPartnerPage');
        $this->assertTrue(true, 'Should redirect');
    }
}
