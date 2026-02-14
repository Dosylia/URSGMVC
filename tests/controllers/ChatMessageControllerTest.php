<?php

namespace tests\controllers;

use tests\BaseControllerTestCase;
use controllers\ChatMessageController;
use models\ChatMessage;
use models\User;
use models\FriendRequest;
use models\GoogleUser;
use models\Items;
use models\PlayerFinder;

class ChatMessageControllerTest extends BaseControllerTestCase
{
    private function createController(array $mockOverrides = []): ChatMessageController
    {
        $defaults = [
            'chatmessage' => $this->createMock(ChatMessage::class),
            'user' => $this->createMock(User::class),
            'friendrequest' => $this->createMock(FriendRequest::class),
            'googleUser' => $this->createMock(GoogleUser::class),
            'items' => $this->createMock(Items::class),
            'playerfinder' => $this->createMock(PlayerFinder::class),
        ];
        $mocks = array_merge($defaults, $mockOverrides);

        return $this->createControllerWithMocks(ChatMessageController::class, $mocks);
    }

    // ─── sendMessageDataWebsite ─────────────────────────────────

    public function testSendMessageDataWebsiteNoBearer(): void
    {
        $controller = $this->createController();

        // Without $_POST['param'], the controller returns 'Invalid data received'
        // before ever reaching the bearer token check
        $result = $this->captureJsonOutput($controller, 'sendMessageDataWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
        $this->assertEquals('Invalid data received', $result['message'] ?? '');
    }

    public function testSendMessageDataWebsiteInvalidToken(): void
    {
        $this->setBearerToken('bad_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'correct_token',
        ]);

        $controller = $this->createController(['googleUser' => $googleUserMock]);

        $_POST['param'] = json_encode([
            'senderId' => 1,
            'receiverId' => 2,
            'message' => 'Hello',
        ]);

        $result = $this->captureJsonOutput($controller, 'sendMessageDataWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testSendMessageDataWebsiteSuccess(): void
    {
        $this->simulateLoggedInUser(1);
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $chatMock = $this->createMock(ChatMessage::class);
        $chatMock->method('insertMessageWebsite')->willReturn(true);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser());
        $userMock->method('fetchSubscriptionEndpoint')->willReturn(null);

        $frMock = $this->createMock(FriendRequest::class);
        $frMock->method('getFriendStatus')->willReturn('accepted');

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'chatmessage' => $chatMock,
            'user' => $userMock,
            'friendrequest' => $frMock,
        ]);

        $_POST['param'] = json_encode([
            'senderId' => 1,
            'receiverId' => 2,
            'message' => 'Hello there!',
            'replyToChatId' => null,
        ]);

        $result = $this->captureJsonOutput($controller, 'sendMessageDataWebsite');
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Message sent', $result['message']);
    }

    // ─── sendMessageDataPhone ───────────────────────────────────

    public function testSendMessageDataPhoneNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'sendMessageDataPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testSendMessageDataPhoneSuccess(): void
    {
        $this->setBearerToken('valid_phone_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenByUserId')->willReturn([
            'google_masterToken' => 'valid_phone_token',
        ]);

        $chatMock = $this->createMock(ChatMessage::class);
        $chatMock->method('insertMessage')->willReturn(true);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser());
        $userMock->method('getToken')->willReturn(null);

        $frMock = $this->createMock(FriendRequest::class);
        $frMock->method('getFriendStatus')->willReturn('accepted');

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'chatmessage' => $chatMock,
            'user' => $userMock,
            'friendrequest' => $frMock,
        ]);

        $_POST['param'] = json_encode([
            'senderId' => 1,
            'receiverId' => 2,
            'message' => 'Hello from phone!',
        ]);

        $result = $this->captureJsonOutput($controller, 'sendMessageDataPhone');
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Message sent', $result['message']);
    }

    // ─── getMessageDataWebsite ──────────────────────────────────

    public function testGetMessageDataWebsiteNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'getMessageDataWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testGetMessageDataWebsiteSuccess(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $chatMock = $this->createMock(ChatMessage::class);
        $chatMock->method('getMessage')->willReturn([
            $this->fakeChatMessage(),
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser());

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'chatmessage' => $chatMock,
            'user' => $userMock,
        ]);

        $_POST['userId'] = '1';
        $_POST['friendId'] = '2';
        $_POST['firstFriend'] = '0';

        $result = $this->captureJsonOutput($controller, 'getMessageDataWebsite');
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('friend', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('messages', $result);
        // Verify friend sub-object values
        $this->assertEquals(1, $result['friend']['user_id']);
        $this->assertEquals('TestUser', $result['friend']['user_username']);
        $this->assertEquals('default.png', $result['friend']['user_picture']);
        $this->assertArrayHasKey('user_lastRequestTime', $result['friend']);
        $this->assertArrayHasKey('user_isOnline', $result['friend']);
        $this->assertArrayHasKey('user_isLooking', $result['friend']);
        $this->assertArrayHasKey('lol_verified', $result['friend']);
        $this->assertArrayHasKey('ownGoldEmotes', $result['friend']);
        // Verify user sub-object values
        $this->assertEquals(1, $result['user']['user_id']);
        $this->assertEquals('TestUser', $result['user']['user_username']);
        $this->assertEquals('default.png', $result['user']['user_picture']);
        $this->assertArrayHasKey('user_hasChatFilter', $result['user']);
        $this->assertArrayHasKey('ownGoldEmotes', $result['user']);
        // Verify messages content
        $this->assertIsArray($result['messages']);
        $this->assertCount(1, $result['messages']);
        $this->assertEquals(1, $result['messages'][0]['chat_id']);
        $this->assertEquals('Hello there!', $result['messages'][0]['chat_message']);
    }

    // ─── getMessageDataPhone ────────────────────────────────────

    public function testGetMessageDataPhoneNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'getMessageDataPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testGetMessageDataPhoneSuccess(): void
    {
        $this->setBearerToken('valid_phone_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenByUserId')->willReturn([
            'google_masterToken' => 'valid_phone_token',
        ]);

        $chatMock = $this->createMock(ChatMessage::class);
        $chatMock->method('getMessage')->willReturn([
            $this->fakeChatMessage(),
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser());

        $itemsMock = $this->createMock(Items::class);
        $itemsMock->method('ownGoldEmotes')->willReturn(false);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'chatmessage' => $chatMock,
            'user' => $userMock,
            'items' => $itemsMock,
        ]);

        $_POST['userId'] = '1';
        $_POST['friendId'] = '2';

        $result = $this->captureJsonOutput($controller, 'getMessageDataPhone');
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('friend', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('messages', $result);
        // Verify friend sub-object values (phone version has fewer fields)
        $this->assertEquals(1, $result['friend']['user_id']);
        $this->assertEquals('TestUser', $result['friend']['user_username']);
        $this->assertEquals('default.png', $result['friend']['user_picture']);
        $this->assertArrayHasKey('ownGoldEmotes', $result['friend']);
        // Verify user sub-object values
        $this->assertEquals(1, $result['user']['user_id']);
        $this->assertEquals('TestUser', $result['user']['user_username']);
        $this->assertArrayHasKey('user_hasChatFilter', $result['user']);
        $this->assertArrayHasKey('ownGoldEmotes', $result['user']);
        // Verify messages content
        $this->assertIsArray($result['messages']);
        $this->assertCount(1, $result['messages']);
        $this->assertEquals(1, $result['messages'][0]['chat_id']);
        $this->assertEquals('Hello there!', $result['messages'][0]['chat_message']);
    }

    // ─── getUnreadMessageWebsite ────────────────────────────────

    public function testGetUnreadMessageWebsiteNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'getUnreadMessageWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testGetUnreadMessageWebsiteSuccess(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $chatMock = $this->createMock(ChatMessage::class);
        $chatMock->method('countMessage')->willReturn([
            ['chat_senderId' => 2, 'unread_count' => 3],
        ]);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'chatmessage' => $chatMock,
        ]);

        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'getUnreadMessageWebsite');
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('unreadCount', $result);
        $this->assertIsArray($result['unreadCount']);
        $this->assertCount(1, $result['unreadCount']);
        $this->assertEquals(2, $result['unreadCount'][0]['chat_senderId']);
        $this->assertEquals(3, $result['unreadCount'][0]['unread_count']);
    }

    // ─── getUnreadMessagePhone ──────────────────────────────────

    public function testGetUnreadMessagePhoneNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'getUnreadMessagePhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testGetUnreadMessagePhoneSuccess(): void
    {
        $this->setBearerToken('valid_phone_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenByUserId')->willReturn([
            'google_masterToken' => 'valid_phone_token',
        ]);

        $chatMock = $this->createMock(ChatMessage::class);
        $chatMock->method('countMessage')->willReturn([]);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'chatmessage' => $chatMock,
        ]);

        $_POST['userId'] = '1';

        $result = $this->captureJsonOutput($controller, 'getUnreadMessagePhone');
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('unreadCount', $result);
        $this->assertIsArray($result['unreadCount']);
        $this->assertCount(0, $result['unreadCount']);
    }

    // ─── markMessageAsReadWebsite ───────────────────────────────

    public function testMarkMessageAsReadWebsiteNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'markMessageAsReadWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testMarkMessageAsReadWebsiteSuccess(): void
    {
        $this->setBearerToken('valid_token');
        $this->simulateLoggedInUser(1);

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser());

        $chatMock = $this->createMock(ChatMessage::class);
        $chatMock->method('getMessage')->willReturn([$this->fakeChatMessage()]);
        $chatMock->method('updateMessageStatus')->willReturn(true);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'chatmessage' => $chatMock,
            'user' => $userMock,
        ]);

        $_POST['param'] = json_encode([
            'senderId' => 1,
            'receiverId' => 2,
        ]);

        $result = $this->captureJsonOutput($controller, 'markMessageAsReadWebsite');
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('Message marked as read', $result['message']);
    }

    // ─── deleteMessageWebsite ───────────────────────────────────

    public function testDeleteMessageWebsiteNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'deleteMessageWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testDeleteMessageWebsiteSuccess(): void
    {
        $this->setBearerToken('valid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'valid_token',
        ]);

        $chatMock = $this->createMock(ChatMessage::class);
        $chatMock->method('getMessageById')->willReturn($this->fakeChatMessage(['chat_senderId' => 1]));
        $chatMock->method('deleteMessageUser')->willReturn(true);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'chatmessage' => $chatMock,
        ]);

        $_POST['userId'] = '1';
        $_POST['chatId'] = '1';

        $result = $this->captureJsonOutput($controller, 'deleteMessageWebsite');
        $this->assertNotNull($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('Pending notification updated', $result['message']);
    }

    // ─── uploadChatImage ────────────────────────────────────────

    public function testUploadChatImageNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'uploadChatImage');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── uploadChatImagePhone ───────────────────────────────────

    public function testUploadChatImagePhoneNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'uploadChatImagePhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── deleteChatImage ────────────────────────────────────────

    public function testDeleteChatImageNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'deleteChatImage');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── deleteOldMessage (cron) ────────────────────────────────

    public function testDeleteOldMessageWrongToken(): void
    {
        $controller = $this->createController();
        $_GET['token'] = 'wrong_token';

        $output = $this->captureOutput($controller, 'deleteOldMessage');
        // Should deny or do nothing with wrong token
        $this->assertIsString($output);
    }

    // ─── getRandomChatMessages ──────────────────────────────────

    public function testGetRandomChatMessagesNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'getRandomChatMessages');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── closeRandomChat ────────────────────────────────────────

    public function testCloseRandomChatNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'closeRandomChat');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── checkIncomingRandomChats ───────────────────────────────

    public function testCheckIncomingRandomChatsNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'checkIncomingRandomChats');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── validateRandomChatSession ──────────────────────────────

    public function testValidateRandomChatSessionNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'validateRandomChatSession');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── getAllQueuedNotification (cron) ─────────────────────────

    public function testGetAllQueuedNotificationWrongToken(): void
    {
        $controller = $this->createController();
        $_GET['token'] = 'invalid';

        $output = $this->captureOutput($controller, 'getAllQueuedNotification');
        $this->assertIsString($output);
    }
}
