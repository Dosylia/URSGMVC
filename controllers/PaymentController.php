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

    public function createStripeCheckout($userId, $productName, $amountUSD, $reward, $type)
    {
        $baseUrl = rtrim($_ENV['base_url'], '/') . '/';

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $productName,
                    ],
                    'unit_amount' => $amountUSD * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $baseUrl . 'paymentSuccess?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $baseUrl . 'paymentCancel',
        ]);

        // Record pending transaction
        $this->payment->createTransaction($userId, $session->id, $amountUSD, $reward, $type);

        // Redirect to Stripe Checkout
        header("Location: " . $session->url);
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

        $amountUSD = (float)$itemData['item_price'];
        $reward = 50000; // e.g., 50,000 SoulHard

        try {
            $this->createStripeCheckout($userId, $itemData['name'], $amountUSD, $reward, 'currency');
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Payment creation failed: ' . $e->getMessage()]);
            return;
        }
    }


    // ⭐ Buy Premium / VIP role
    public function buyPremiumBoostWebsite(): void
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
        $reward = 'boost'; // type marker

        try {
            $this->createStripeCheckout($userId, 'URSG Premium Boost', $amountUSD, $reward, 'vip');
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Payment creation failed: ' . $e->getMessage()]);
            return;
        }
    }

    public function paymentSuccess(): void
    {
        if (isset($_GET['session_id'], $_GET['userId'], $_GET['amount'], $_GET['currency'])) {
            $sessionId = $this->validateInput($_GET['session_id']);
            $userId = $this->validateInput($_GET['userId']);
            $amount = $this->validateInput($_GET['amount']);
            $currency = $this->validateInput($_GET['currency']);

            $user = $this->user->getUserById($_SESSION['userId']);

            if ($user['user_id'] !== (int) $userId) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                return;
            }

            // Redirect to store with success message
            echo json_encode(['success' => true, 'message' => "Payment successful! You have received $currency SoulHard."]);
            return;
        } else {
            echo json_encode(['success' => false, 'message' => "Missing parameters"]);
            return;
        }
    }

    public function paymentCancel(): void
    {
        echo json_encode(['success' => false, 'message' => "Payment cancelled"]);
        return;
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

            $transaction = $this->payment->getTransactionBySessionId($sessionId);

            if ($transaction) {
                $this->payment->updateTransactionStatus($sessionId, 'paid');

                if ($transaction['type'] === 'currency') {
                    $this->user->addCurrency($transaction['user_id'], $transaction['soulhard_amount']);
                } elseif ($transaction['type'] === 'boost') {
                    $this->user->grantBoostRole($transaction['user_id']);
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
