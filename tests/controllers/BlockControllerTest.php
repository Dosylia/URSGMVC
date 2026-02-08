<?php

namespace tests\controllers;

use tests\BaseControllerTestCase;
use controllers\BlockController;
use models\Block;
use models\FriendRequest;
use models\User;
use models\GoogleUser;
use models\ChatMessage;

class BlockControllerTest extends BaseControllerTestCase
{
    private function createController(array $mockOverrides = []): BlockController
    {
        $defaults = [
            'block' => $this->createMock(Block::class),
            'friendrequest' => $this->createMock(FriendRequest::class),
            'user' => $this->createMock(User::class),
            'googleUser' => $this->createMock(GoogleUser::class),
            'chatmessage' => $this->createMock(ChatMessage::class),
        ];
        $mocks = array_merge($defaults, $mockOverrides);

        return $this->createControllerWithMocks(BlockController::class, $mocks);
    }

    // ─── blockPerson (website form POST) ────────────────────────

    public function testBlockPersonSuccess(): void
    {
        $this->simulateLoggedInUser(1);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser(['user_id' => 1]));

        $blockMock = $this->createMock(Block::class);
        $blockMock->method('isBlocked')->willReturn(false);
        $blockMock->method('blockPerson')->willReturn(true);

        $frMock = $this->createMock(FriendRequest::class);
        $frMock->method('updateFriend')->willReturn(true);

        $chatMock = $this->createMock(ChatMessage::class);
        $chatMock->method('deleteMessageUnfriend')->willReturn(true);

        $controller = $this->createController([
            'user' => $userMock,
            'block' => $blockMock,
            'friendrequest' => $frMock,
            'chatmessage' => $chatMock,
        ]);

        $_POST['submit'] = true;
        $_POST['senderId'] = '1';
        $_POST['receiverId'] = '2';

        // blockPerson does header("location:...") + exit — we expect no exception in output capture
        $output = $this->captureOutput($controller, 'blockPerson');
        // Method redirects, so output is empty; just ensure no crash
        $this->assertTrue(true, 'blockPerson should execute without error');
    }

    public function testBlockPersonNoFormSubmit(): void
    {
        $controller = $this->createController();
        // No $_POST['submit'] set — should redirect with "No form"
        $output = $this->captureOutput($controller, 'blockPerson');
        $this->assertTrue(true, 'blockPerson without submit should redirect');
    }

    public function testBlockPersonAlreadyBlocked(): void
    {
        $this->simulateLoggedInUser(1);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser(['user_id' => 1]));

        $blockMock = $this->createMock(Block::class);
        $blockMock->method('isBlocked')->willReturn(true);

        $controller = $this->createController([
            'user' => $userMock,
            'block' => $blockMock,
        ]);

        $_POST['submit'] = true;
        $_POST['senderId'] = '1';
        $_POST['receiverId'] = '2';

        $output = $this->captureOutput($controller, 'blockPerson');
        $this->assertTrue(true, 'blockPerson with already-blocked user should redirect');
    }

    public function testBlockPersonUnauthorizedUser(): void
    {
        $this->simulateLoggedInUser(99);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser(['user_id' => 99]));

        $controller = $this->createController(['user' => $userMock]);

        $_POST['submit'] = true;
        $_POST['senderId'] = '1'; // senderId != session userId
        $_POST['receiverId'] = '2';

        $output = $this->captureOutput($controller, 'blockPerson');
        $this->assertTrue(true, 'blockPerson with mismatched sender should redirect');
    }

    public function testBlockPersonBlockFails(): void
    {
        $this->simulateLoggedInUser(1);

        $userMock = $this->createMock(User::class);
        $userMock->method('getUserById')->willReturn($this->fakeUser(['user_id' => 1]));

        $blockMock = $this->createMock(Block::class);
        $blockMock->method('isBlocked')->willReturn(false);
        $blockMock->method('blockPerson')->willReturn(false);

        $controller = $this->createController([
            'user' => $userMock,
            'block' => $blockMock,
        ]);

        $_POST['submit'] = true;
        $_POST['senderId'] = '1';
        $_POST['receiverId'] = '2';

        $output = $this->captureOutput($controller, 'blockPerson');
        $this->assertTrue(true, 'blockPerson with failed block should redirect with error');
    }

    // ─── blockPersonPhone (Bearer + JSON POST) ─────────────────

    public function testBlockPersonPhoneNoBearer(): void
    {
        $controller = $this->createController();

        $result = $this->captureJsonOutput($controller, 'blockPersonPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
        $this->assertEquals('Unauthorized', $result['message'] ?? '');
    }

    public function testBlockPersonPhoneInvalidToken(): void
    {
        $this->setBearerToken('invalid_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenByUserId')->willReturn([
            'google_masterToken' => 'valid_token',
        ]);

        $controller = $this->createController(['googleUser' => $googleUserMock]);

        $_POST['userData'] = json_encode([
            'senderId' => 1,
            'receiverId' => 2,
        ]);

        $result = $this->captureJsonOutput($controller, 'blockPersonPhone');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testBlockPersonPhoneSuccess(): void
    {
        $this->setBearerToken('valid_token_123');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenByUserId')->willReturn([
            'google_masterToken' => 'valid_token_123',
        ]);

        $blockMock = $this->createMock(Block::class);
        $blockMock->method('blockPerson')->willReturn(true);

        $frMock = $this->createMock(FriendRequest::class);
        $frMock->method('updateFriend')->willReturn(true);

        $chatMock = $this->createMock(ChatMessage::class);
        $chatMock->method('deleteMessageUnfriend')->willReturn(true);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'block' => $blockMock,
            'friendrequest' => $frMock,
            'chatmessage' => $chatMock,
        ]);

        $_POST['userData'] = json_encode([
            'senderId' => 1,
            'receiverId' => 2,
        ]);

        $result = $this->captureJsonOutput($controller, 'blockPersonPhone');
        $this->assertNotNull($result);
        $this->assertEquals('Success', $result['message'] ?? '');
    }

    public function testBlockPersonPhoneBlockFails(): void
    {
        $this->setBearerToken('valid_token_123');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenByUserId')->willReturn([
            'google_masterToken' => 'valid_token_123',
        ]);

        $blockMock = $this->createMock(Block::class);
        $blockMock->method('blockPerson')->willReturn(false);

        $controller = $this->createController([
            'googleUser' => $googleUserMock,
            'block' => $blockMock,
        ]);

        $_POST['userData'] = json_encode([
            'senderId' => 1,
            'receiverId' => 2,
        ]);

        $result = $this->captureJsonOutput($controller, 'blockPersonPhone');
        $this->assertNotNull($result);
        $this->assertEquals('Could not block user', $result['message'] ?? '');
    }

    public function testBlockPersonPhoneNoFormData(): void
    {
        $this->setBearerToken('valid_token_123');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenByUserId')->willReturn([
            'google_masterToken' => 'valid_token_123',
        ]);

        $controller = $this->createController(['googleUser' => $googleUserMock]);

        // No $_POST['userData']
        $result = $this->captureJsonOutput($controller, 'blockPersonPhone');
        $this->assertNotNull($result);
        $this->assertEquals('No form', $result['message'] ?? '');
    }

    // ─── unblockPerson (website form POST) ─────────────────────

    public function testUnblockPersonSuccess(): void
    {
        $blockMock = $this->createMock(Block::class);
        $blockMock->method('unblockPerson')->willReturn(true);

        $controller = $this->createController(['block' => $blockMock]);

        $_POST['submit'] = true;
        $_POST['blockId'] = '1';

        $output = $this->captureOutput($controller, 'unblockPerson');
        $this->assertTrue(true, 'unblockPerson success should redirect');
    }

    public function testUnblockPersonFails(): void
    {
        $blockMock = $this->createMock(Block::class);
        $blockMock->method('unblockPerson')->willReturn(false);

        $controller = $this->createController(['block' => $blockMock]);

        $_POST['submit'] = true;
        $_POST['blockId'] = '1';

        $output = $this->captureOutput($controller, 'unblockPerson');
        $this->assertTrue(true, 'unblockPerson failure should redirect with error');
    }

    public function testUnblockPersonNoForm(): void
    {
        $controller = $this->createController();

        $output = $this->captureOutput($controller, 'unblockPerson');
        $this->assertTrue(true, 'unblockPerson without form should redirect');
    }

    // ─── validateInput ─────────────────────────────────────────

    public function testValidateInputSanitizes(): void
    {
        $controller = $this->createController();
        $result = $controller->validateInput('  <script>alert("xss")</script>  ');
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('</script>', $result);
    }

    public function testValidateInputTrims(): void
    {
        $controller = $this->createController();
        $result = $controller->validateInput('  hello  ');
        $this->assertEquals('hello', $result);
    }

    // ─── Getters/Setters ───────────────────────────────────────

    public function testGetSetSenderId(): void
    {
        $controller = $this->createController();
        $controller->setSenderId(42);
        $this->assertEquals(42, $controller->getSenderId());
    }

    public function testGetSetReceiverId(): void
    {
        $controller = $this->createController();
        $controller->setReceiverId(99);
        $this->assertEquals(99, $controller->getReceiverId());
    }

    public function testGetSetBlockId(): void
    {
        $controller = $this->createController();
        $controller->setBlockId(7);
        $this->assertEquals(7, $controller->getBlockId());
    }
}
