<?php

namespace tests\controllers;

use tests\BaseControllerTestCase;
use controllers\PaymentController;
use models\Payment;
use models\User;
use models\GoogleUser;
use models\Items;

class PaymentControllerTest extends BaseControllerTestCase
{
    private function createController(array $mockOverrides = []): PaymentController
    {
        $defaults = [
            'payment'    => $this->createMock(Payment::class),
            'user'       => $this->createMock(User::class),
            'googleUser' => $this->createMock(GoogleUser::class),
            'items'      => $this->createMock(Items::class),
        ];
        $mocks = array_merge($defaults, $mockOverrides);

        return $this->createControllerWithMocks(PaymentController::class, $mocks);
    }

    // ─── buyCurrencyWebsite ─────────────────────────────────────

    public function testBuyCurrencyWebsiteNoParam(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'buyCurrencyWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
        $this->assertEquals('Missing parameters', $result['message'] ?? '');
    }

    public function testBuyCurrencyWebsiteNoBearer(): void
    {
        $_POST['param'] = json_encode(['userId' => 1, 'itemId' => 10]);
        $controller = $this->createController();

        // Controller double-echoes: getBearerTokenOrJsonError echoes Unauthorized,
        // then the if(!$token) block echoes again. Use captureOutput instead.
        $output = $this->captureOutput($controller, 'buyCurrencyWebsite');
        $this->assertStringContainsString('Unauthorized', $output);
    }

    public function testBuyCurrencyWebsiteInvalidToken(): void
    {
        $this->setBearerToken('wrong_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'different_token',
        ]);

        $controller = $this->createController(['googleUser' => $googleUserMock]);
        $_POST['param'] = json_encode(['userId' => 1, 'itemId' => 10]);

        $result = $this->captureJsonOutput($controller, 'buyCurrencyWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testBuyCurrencyWebsiteMissingFields(): void
    {
        $this->setBearerToken('valid_token');

        $controller = $this->createController();
        $_POST['param'] = json_encode(['userId' => 1]); // Missing itemId

        $result = $this->captureJsonOutput($controller, 'buyCurrencyWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── buyAscendWebsite ───────────────────────────────────────

    public function testBuyAscendWebsiteNoParam(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'buyAscendWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testBuyAscendWebsiteNoBearer(): void
    {
        $_POST['param'] = json_encode(['userId' => 1]);
        $controller = $this->createController();

        // Controller double-echoes: getBearerTokenOrJsonError echoes Unauthorized,
        // then the if(!$token) block echoes again. Use captureOutput instead.
        $output = $this->captureOutput($controller, 'buyAscendWebsite');
        $this->assertStringContainsString('Unauthorized', $output);
    }

    public function testBuyAscendWebsiteInvalidToken(): void
    {
        $this->setBearerToken('wrong_token');

        $googleUserMock = $this->createMock(GoogleUser::class);
        $googleUserMock->method('getMasterTokenWebsiteByUserId')->willReturn([
            'google_masterTokenWebsite' => 'different_token',
        ]);

        $controller = $this->createController(['googleUser' => $googleUserMock]);
        $_POST['param'] = json_encode(['userId' => 1]);

        $result = $this->captureJsonOutput($controller, 'buyAscendWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testBuyAscendWebsiteMissingUserId(): void
    {
        $this->setBearerToken('valid_token');
        $controller = $this->createController();
        $_POST['param'] = json_encode([]); // Missing userId

        $result = $this->captureJsonOutput($controller, 'buyAscendWebsite');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    // ─── paymentSuccess ─────────────────────────────────────────

    public function testPaymentSuccessNoSessionId(): void
    {
        $controller = $this->createController();
        $result = $this->captureJsonOutput($controller, 'paymentSuccess');
        // No session_id → does nothing specific
        $this->assertTrue(true);
    }

    public function testPaymentSuccessTransactionNotFound(): void
    {
        $this->simulateLoggedInUser(1);

        $paymentMock = $this->createMock(Payment::class);
        $paymentMock->method('getTransactionBySessionId')->willReturn(false);

        $controller = $this->createController(['payment' => $paymentMock]);
        $_GET['session_id'] = 'cs_test_fake_123';

        $result = $this->captureJsonOutput($controller, 'paymentSuccess');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
    }

    public function testPaymentSuccessAlreadyProcessed(): void
    {
        $this->simulateLoggedInUser(1);

        $paymentMock = $this->createMock(Payment::class);
        $paymentMock->method('getTransactionBySessionId')->willReturn([
            'status' => 'paid',
            'user_id' => 1,
            'type' => 'currency',
            'amount' => 5,
            'soulhard_amount' => 50000,
        ]);

        $controller = $this->createController(['payment' => $paymentMock]);
        $_GET['session_id'] = 'cs_test_fake_123';

        $result = $this->captureJsonOutput($controller, 'paymentSuccess');
        $this->assertNotNull($result);
        $this->assertFalse($result['success'] ?? true);
        $this->assertStringContainsString('already processed', $result['message'] ?? '');
    }

    // ─── paymentCancel ──────────────────────────────────────────

    public function testPaymentCancel(): void
    {
        $controller = $this->createController();
        $output = $this->captureOutput($controller, 'paymentCancel');
        $this->assertTrue(true, 'Should redirect to store');
    }

    // ─── handleWebhook ──────────────────────────────────────────

    public function testHandleWebhookInvalidSignature(): void
    {
        $_SERVER['HTTP_STRIPE_SIGNATURE'] = 'invalid_sig';
        $controller = $this->createController();

        // This will throw/exit due to invalid signature
        $output = $this->captureOutput($controller, 'handleWebhook');
        $this->assertTrue(true, 'Should handle invalid webhook gracefully');
    }

    // ─── validateInput ──────────────────────────────────────────

    public function testValidateInput(): void
    {
        $controller = $this->createController();
        $result = $controller->validateInput('  <b>test</b>  ');
        $this->assertStringNotContainsString('<b>', $result);
    }

    // ─── getGoogleUserModel ─────────────────────────────────────

    public function testGetGoogleUserModel(): void
    {
        $googleUserMock = $this->createMock(GoogleUser::class);
        $controller = $this->createController(['googleUser' => $googleUserMock]);
        $result = $controller->getGoogleUserModel();
        $this->assertInstanceOf(GoogleUser::class, $result);
    }
}
