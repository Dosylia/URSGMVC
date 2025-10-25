<?php

namespace controllers;

use models\Payment;
use models\User;
use models\GoogleUser;
use models\Items;

use traits\SecurityController;
use traits\Translatable;

require 'vendor/autoload.php';

use Stripe\Stripe;
use Stripe\Checkout\Session;

class PaymentController
{
    use SecurityController;
    use Translatable;

    private Payment $payment;
    private User $user;
    private GoogleUser $googleUser;
    private Items $items;

    public function __construct()
    {
        $this->payment = new Payment();
        $this->user = new User();
        $this->googleUser = new GoogleUser();
        $this->items = new Items();
        Stripe::setApiKey($_ENV['stripe_secret_key']);
    }

    public function getGoogleUserModel(): GoogleUser
    {
        return $this->googleUser;
    }

    public function createStripeCheckout($userId, $productName, $amountEUR, $reward, $type)
    {
        $baseUrl = rtrim($_ENV['base_url'], '/') . '/';

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $productName,
                    ],
                    'unit_amount' => $amountEUR * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $baseUrl . 'paymentSuccess?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $baseUrl . 'paymentCancel',
        ]);

        // Record pending transaction
        $this->payment->createTransaction($userId, $session->id, $amountEUR, $reward, $type);

        echo json_encode([
            'success' => true,
            'stripeUrl' => $session->url
        ]);
        exit;
    }

    // 💰 Buy virtual currency
    public function buyCurrencyWebsite(): void
    {
        if (!isset($_POST['param'])) {
            echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            return;
        }

        $token = $this->getBearerTokenOrJsonError();
        $data = json_decode($_POST['param']);

        if (!$token) {
            echo json_encode(['success' => false, 'message' => 'Missing or invalid token']);
            return;
        }

        if (!isset($data->userId, $data->itemId)) {
            echo json_encode(['success' => false, 'message' => 'Missing userId or itemId']);
            return;
        }

        $userId = $this->validateInput($data->userId);
        $itemId = $this->validateInput($data->itemId);

        if (!$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'message' => 'Token validation failed']);
            return;
        }

        $user = $this->user->getUserById($_SESSION['userId']);
        if (!$user || $user['user_id'] !== (int)$userId) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $itemData = $this->items->getItemById($itemId);
        if (!$itemData) {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
            return;
        }

        $amountUSD = 5;
        $reward = 50000; // e.g., 50,000 SoulHard

        try {
            $this->createStripeCheckout($userId, $itemData['items_name'], $amountUSD, $reward, 'currency');
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Payment creation failed: ' . $e->getMessage()]);
            return;
        }
    }


    // ⭐ Buy Gold / VIP role
    public function buyPremiumAscendWebsite(): void
    {
        if (!isset($_POST['param'])) {
            echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            return;
        }

        $token = $this->getBearerTokenOrJsonError();
        $data = json_decode($_POST['param']);

        if (!$token) {
            echo json_encode(['success' => false, 'message' => 'Missing or invalid token']);
            return;
        }

        if (!isset($data->userId)) {
            echo json_encode(['success' => false, 'message' => 'Missing userId']);
            return;
        }

        $userId = $this->validateInput($data->userId);

        if (!$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'message' => 'Token validation failed']);
            return;
        }

        $user = $this->user->getUserById($_SESSION['userId']);
        if (!$user || $user['user_id'] !== (int)$userId) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $amountUSD = 5.00;
        $reward = 'Ascend'; // type marker

        try {
            $this->createStripeCheckout($userId, 'URSG Gold Ascend', $amountUSD, $reward, 'Ascend');
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Payment creation failed: ' . $e->getMessage()]);
            return;
        }
    }

    public function paymentSuccess(): void
    {
        if (isset($_GET['session_id'])) {
            $sessionId = $this->validateInput($_GET['session_id']);
            $transaction = $this->payment->getTransactionBySessionId($sessionId);

            if (!$transaction) {
                echo json_encode(['success' => false, 'message' => 'Transaction not found']);
                return;
            }

            if ($transaction['status'] === 'paid') {
                echo json_encode(['success' => false, 'message' => 'Transaction already processed']);
                return;
            }

            $user = $this->user->getUserById($_SESSION['userId']);
            if ($user['user_id'] !== (int)$transaction['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                return;
            }

            // Use transaction data for amount/currency
            $amount = $transaction['amount'];

            if ($transaction['type'] === 'currency') {
                $currency = $transaction['soulhard_amount'];
                $this->user->addCurrency($transaction['user_id'], $transaction['soulhard_amount']);
                $this->payment->updateTransactionStatus($sessionId, 'paid');
                header("Location: " . rtrim($_ENV['base_url'], '/') . '/store?message=Payment successful! You have received ' . $currency . ' SoulHard');
                return;
            } elseif ($transaction['type'] === 'Ascend') {
                $this->user->grantAscendRole($transaction['user_id']);
                $this->payment->updateTransactionStatus($sessionId, 'paid');
                header("Location: " . rtrim($_ENV['base_url'], '/') . '/store?message=Payment successful! You have received the URSG Gold Ascend');
                return;
            }
        } else {
            echo json_encode(['success' => false, 'message' => "Missing parameters"]);
            return;
        }
    }

    public function paymentCancel(): void
    {
        header("Location: " . rtrim($_ENV['base_url'], '/') . '/store?message=Payment cancelled');
        exit;
    }

    public function handleWebhook()
    {
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $endpoint_secret = $_ENV['stripe_webhook_secret'];

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (\Exception $e) {
            http_response_code(400);
            exit;
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $sessionId = $session->id;
            error_log("Webhook received for session: $sessionId");

            $transaction = $this->payment->getTransactionBySessionId($sessionId);

            if ($transaction) {
                $this->payment->updateTransactionStatus($sessionId, 'paid');

                if ($transaction['type'] === 'currency') {
                    error_log("Adding currency to user ID: " . $transaction['user_id']);
                    $this->user->addCurrency($transaction['user_id'], $transaction['soulhard_amount']);
                } elseif ($transaction['type'] === 'Ascend') {
                    error_log("Granting Ascend role to user ID: " . $transaction['user_id']);
                    $this->user->grantAscendRole($transaction['user_id']);
                }
            }
        }

        http_response_code(200);
    }

    public function validateInput($input) 
    {
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }

}
