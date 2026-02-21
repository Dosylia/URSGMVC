<?php

namespace tests\controllers;

use tests\BaseControllerTestCase;
use controllers\ItemsController;
use models\Items;
use models\User;
use models\GoogleUser;

class ItemsControllerTest extends BaseControllerTestCase
{
    private function createController(array $mockOverrides = []): ItemsController
    {
        $defaults = [
            'items' => $this->createMock(Items::class),
            'user' => $this->createMock(User::class),
            'googleUser' => $this->createMock(GoogleUser::class),
        ];
        $mocks = array_merge($defaults, $mockOverrides);

        return $this->createControllerWithMocks(ItemsController::class, $mocks);
    }

    // ─── getItems ───────────────────────────────────────────────

    public function testGetItemsSuccess(): void
    {
        $itemsMock = $this->createMock(Items::class);
        $itemsMock->method('getItemsExceptBadges')->willReturn([
            $this->fakeItem(),
            $this->fakeItem(['items_id' => 2, 'items_name' => 'Double XP']),
        ]);

        $controller = $this->createController(['items' => $itemsMock]);
        $_POST['items'] = true;

        $result = $this->captureJsonOutput($controller, 'getItems');
        $this->assertNotNull($result);
        $this->assertEquals('Success', $result['message']);
        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']);
        $this->assertCount(2, $result['items']);

        // Verify first item has correct data
        $this->assertEquals(1, $result['items'][0]['items_id']);
        $this->assertEquals('VIP Badge', $result['items'][0]['items_name']);
        $this->assertEquals(100, $result['items'][0]['items_price']);
        $this->assertEquals('A shiny badge', $result['items'][0]['items_desc']);
        $this->assertEquals('badge.png', $result['items'][0]['items_picture']);
        $this->assertEquals('Cosmetic', $result['items'][0]['items_category']);

        // Verify second item has its overrides
        $this->assertEquals(2, $result['items'][1]['items_id']);
        $this->assertEquals('Double XP', $result['items'][1]['items_name']);
    }

    public function testGetItemsNoPost(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'getItems');
        // Should return error or empty
        $this->assertNotNull($result);
    }

    // ─── getItemsWebsite - mapped via getItems ──────────────────

    public function testGetItemsWebsiteSuccess(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $itemsMock = $this->createMock(Items::class);
        $itemsMock->method('getItemsExceptBadges')->willReturn([
            $this->fakeItem(),
        ]);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'items' => $itemsMock,
        ]);

        $_POST['items'] = '1';

        $result = $this->captureJsonOutput($controller, 'getItems');
        $this->assertNotNull($result);
        $this->assertEquals('Success', $result['message']);
        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']);
        $this->assertCount(1, $result['items']);
        $this->assertEquals(1, $result['items'][0]['items_id']);
        $this->assertEquals('VIP Badge', $result['items'][0]['items_name']);
        $this->assertEquals(100, $result['items'][0]['items_price']);
    }

    // ─── getOwnedItems ─────────────────────────────────────────

    public function testGetOwnedItemsSuccess(): void
    {
        $itemsMock = $this->createMock(Items::class);
        $itemsMock->method('getOwnedItems')->willReturn([
            $this->fakeItem(),
        ]);

        $controller = $this->createController(['items' => $itemsMock]);
        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'getOwnedItems');
        $this->assertNotNull($result);
        $this->assertEquals('Success', $result['message']);
        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']);
        $this->assertCount(1, $result['items']);
        $this->assertEquals(1, $result['items'][0]['items_id']);
        $this->assertEquals('VIP Badge', $result['items'][0]['items_name']);
    }

    // ─── getOwnedItemsPhone ────────────────────────────────────

    public function testGetOwnedItemsPhoneNoBearer(): void
    {
        $controller = $this->createController();
        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'getOwnedItemsPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testGetOwnedItemsPhoneSuccess(): void
    {
        $this->setBearerToken('valid_phone_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenByUserId')->willReturn([
            'google_masterToken' => 'valid_phone_token',
        ]);

        $itemsMock = $this->createMock(Items::class);
        $itemsMock->method('getOwnedItems')->willReturn([
            $this->fakeItem(),
        ]);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'items' => $itemsMock,
        ]);

        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'getOwnedItemsPhone');
        $this->assertNotNull($result);
        $this->assertEquals('Success', $result['message']);
        $this->assertArrayHasKey('items', $result);
        $this->assertIsArray($result['items']);
        $this->assertCount(1, $result['items']);
        $this->assertEquals(1, $result['items'][0]['items_id']);
        $this->assertEquals('VIP Badge', $result['items'][0]['items_name']);
    }

    // ─── ownGoldEmotesPhone ────────────────────────────────────

    public function testOwnGoldEmotesPhoneNoBearer(): void
    {
        $controller = $this->createController();
        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'ownGoldEmotesPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testOwnGoldEmotesPhoneSuccess(): void
    {
        $this->setBearerToken('valid_phone_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenByUserId')->willReturn([
            'google_masterToken' => 'valid_phone_token',
        ]);

        $itemsMock = $this->createMock(Items::class);
        $itemsMock->method('ownGoldEmotes')->willReturn(true);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'items' => $itemsMock,
        ]);

        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'ownGoldEmotesPhone');
        $this->assertNotNull($result);
        $this->assertEquals('Success', $result['message']);
        $this->assertTrue($result['ownGoldEmotes']);
    }

    // ─── buyItemWebsite ─────────────────────────────────────────

    public function testBuyItemWebsiteNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'buyItemWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testBuyItemWebsiteSuccess(): void
    {
        $this->setBearerToken('valid_token');
        $this->simulateLoggedInUser(1);

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser(['user_currency' => 1000]));
        $userMock->method('UpdateCurrency')->willReturn(true);

        $itemsMock = $this->createMock(Items::class);
        $itemsMock->method('getItemById')->willReturn($this->fakeItem(['items_price' => 100]));
        $itemsMock->method('userOwnsItem')->willReturn(false);
        $itemsMock->method('buyItem')->willReturn(true);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
            'items' => $itemsMock,
        ]);

        $_POST['param'] = json_encode([
            'itemId' => 1,
            'userId' => 1,
        ]);

        $result = $this->captureJsonOutput($controller, 'buyItemWebsite');
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('Item bought successfully', $result['message']);
    }

    public function testBuyItemWebsiteInsufficientFunds(): void
    {
        $this->setBearerToken('valid_token');
        $this->simulateLoggedInUser(1);

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser(['user_currency' => 10]));

        $itemsMock = $this->createMock(Items::class);
        $itemsMock->method('getItemById')->willReturn($this->fakeItem(['items_price' => 100]));
        $itemsMock->method('userOwnsItem')->willReturn(false);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'user' => $userMock,
            'items' => $itemsMock,
        ]);

        $_POST['param'] = json_encode([
            'itemId' => 1,
            'userId' => 1,
        ]);

        $result = $this->captureJsonOutput($controller, 'buyItemWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success']);
        $this->assertEquals('Not enough currency', $result['message']);
    }

    // ─── buyItemPhone ───────────────────────────────────────────

    public function testBuyItemPhoneNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'buyItemPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── buyRoleWebsite ─────────────────────────────────────────

    public function testBuyRoleWebsiteNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'buyRoleWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── buyRolePhone ───────────────────────────────────────────

    public function testBuyRolePhoneNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'buyRolePhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── usePictureFrame ────────────────────────────────────────

    public function testUsePictureFrameNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'usePictureFrame');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── usePictureFrameWebsite ─────────────────────────────────

    public function testUsePictureFrameWebsiteNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'usePictureFrameWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── removePictureFrame ─────────────────────────────────────

    public function testRemovePictureFrameNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'removePictureFrame');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── removePictureFrameWebsite ──────────────────────────────

    public function testRemovePictureFrameWebsiteNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'removePictureFrameWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── useBadgeWebsite ────────────────────────────────────────

    public function testUseBadgeWebsiteNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'useBadgeWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── removeBadgeWebsite ─────────────────────────────────────

    public function testRemoveBadgeWebsiteNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'removeBadgeWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }
}
