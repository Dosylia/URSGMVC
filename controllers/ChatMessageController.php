<?php

namespace controllers;

use models\ChatMessage;
use models\User;
use models\FriendRequest;
use models\GoogleUser;
use models\Items;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use traits\SecurityController;
use traits\Translatable;

require 'vendor/autoload.php';

class ChatMessageController
{
    use SecurityController;
    use Translatable;

    private ChatMessage $chatmessage;
    private User $user;
    private FriendRequest $friendrequest;
    private GoogleUser $googleUser;
    private Items $items;
    private int $senderId;
    private int $receiverId;
    private string $message;
    private int $userId;
    private int $friendId;

    public function __construct()
    {
        $this->chatmessage = new ChatMessage();
        $this->user = new User();
        $this->friendrequest = new FriendRequest();
        $this -> googleUser = new GoogleUser();
        $this-> items = new Items();
    }

    public function getGoogleUserModel(): GoogleUser
    {
        return $this->googleUser;
    }

    public function pagePersoMessage(): void
    {
    $this->requireUserSessionOrRedirect($redirectUrl = '/');
    $this->initializeLanguage();
    $user = $this->user->getUserById($_SESSION['userId']);
    $getFriendlist = $this->friendrequest->getFriendlist($_SESSION['userId']);
    $ownVIPEmotes = $this->items->ownVIPEmotes($_SESSION['userId']);

        if ($getFriendlist) {
            $firstFriend = reset($getFriendlist);

            if (isset($_GET['friend_id'])) {
                $friendId = $_GET['friend_id'];

                $isFriend = false;
                foreach ($getFriendlist as $friend) {
                    if ($friendId == $friend['sender_id'] || $friendId == $friend['receiver_id']) {
                        if ($friendId == $user['user_id']) {
                            $isFriend = false;
                            break;
                        }
                        $isFriend = true;
                        break;
                    }
                }

                if ($isFriend) {
                    $friendChat = $this->user->getUserById($friendId);
                } else {
                    header("Location: /persoChat?msg=You are not friends with this user.");
                    exit();
                }

            } else {
                if (isset($firstFriend)) {
                    $friendId = ($user['user_id'] == $firstFriend['sender_id']) ? $firstFriend['receiver_id'] : $firstFriend['sender_id'];
                    $friendChat = $this->user->getUserById($friendId);
                }
            }
        }

        $page_css = ['chat'];
        $current_url = "https://ur-sg.com/persoChat";
        $template = "views/swiping/swiping_persomessage";
        $picture = "chat-preview";
        $page_title = "URSG - Chat";
        require "views/layoutSwiping.phtml";
    }

    public function messageStream(): void
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        $userId = $_GET['userId'] ?? null;
        $friendId = $_GET['friendId'] ?? null;
        $token = $_GET['token'] ?? null;

        // Validate token and IDs (use your existing validation logic)
        if (!$this->validateTokenWebsite($token, $userId)) {
            echo "event: error\ndata: Invalid token\n\n";
            ob_flush();
            flush();
            exit;
        }

        // Set initial last message ID
        $lastMessageId = $_GET['lastId'] ?? 0;

        // Keep connection open
        while (true) {
            // Get new messages since last check
            $messages = $this->chatmessage->getNewMessages(
                $userId,
                $friendId,
                $lastMessageId
            );

            if (!empty($messages)) {
                $lastMessage = end($messages);
                $lastMessageId = $lastMessage['chat_id'];

                $data = [
                    'messages' => $messages,
                    'friend' => $this->user->getUserById($friendId),
                    'user' => $this->user->getUserById($userId)
                ];

                echo "data: " . json_encode($data) . "\n\n";
                ob_flush();
                flush();
            }

            // Sleep for 1 second before checking again
            sleep(1);

            // Check if client disconnected
            if (connection_aborted()) {
                exit;
            }
        }
    }

    public function sendMessageData(): void // Mobile version
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);
    
            $status = "unread";
    
            $this->setSenderId($data->senderId);
            $this->setReceiverId($data->receiverId);
            $this->setMessage($this->validateInput($data->message));
    
            $insertMessage = $this->chatmessage->insertMessage($this->getSenderId(), $this->getReceiverId(), $this->getMessage(), $status);
    
            if ($insertMessage) {
                $userId = $this->getSenderId();

                // Removed option to earn money by sending messages
                // $lastRequestTime = $this->user->getLastRequestTime($userId);
                // $currentTime = time();
    
                // if ($currentTime - $lastRequestTime > 5) {
                //     $amount = 10;
                //     $user = $this->user->getUserById($userId);
    
                //     if ($user['user_isVip'] == 1) {
                //         $amount = 12;
                //     }
    
                //     $addCurrency = $this->user->addCurrency($userId, $amount);
                //     $addCurrencySnapshot = $this->user->addCurrencySnapshot($userId, $amount);
    
                //     if ($addCurrency) {
                //         $this->user->updateLastRequestTime($userId);
                //     }
                // }

                $sendNotifications = $this->sendNotificationsPhone($this->getReceiverId(), $this->getMessage(), $this->getSenderId());
    
                echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send message']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
        }
    }

    public function sendMessageDataPhone(): void
    {
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }
    
        if (!isset($_POST['param'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
            return;
        }
    
        $data = json_decode($_POST['param']);
    
        if (!isset($data->senderId, $data->receiverId, $data->message)) {
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            return;
        }
    
        // Validate Token for Sender
        if (!$this->validateToken($token, $data->senderId)) {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            return;
        }
    
        // Process Message Sending
        $status = "unread";
    
        $this->setSenderId($data->senderId);
        $this->setReceiverId($data->receiverId);
        $this->setMessage($this->validateInput($data->message));

        $sender = $this->user->getUserById($this->getSenderId());

        $testFriendstatus = $this->friendrequest->getFriendStatus($this->getSenderId(), $this->getReceiverId());

        if ($testFriendstatus != "accepted") {
            echo json_encode(['success' => false, 'error' => 'You are not friends with this user']);
            return;
        }
    
        $insertMessage = $this->chatmessage->insertMessage($this->getSenderId(), $this->getReceiverId(), $this->getMessage(), $status);
        $rawMessage = $data->message;
    
        if ($insertMessage) {
            $friend = $this->user->getUserById($this->getReceiverId());
            $sendNotificationPhone = true;
            $sendNotificationsBrowser = true;

            if ($friend['user_token'] !== NULL)
            {
                $expoToken = $friend['user_token'];
                $type = "phone";
                $sendNotificationsPhone = $this->chatmessage->queueNotificationPhone($this->getReceiverId(), $this->getSenderId(), $rawMessage, $type, $expoToken);

                if ($sendNotificationsPhone) {
                    $sendNotificationPhone = true;
                } else {
                    $sendNotificationPhone = false;
                }
            }

            if ($friend['user_notificationPermission'] == 1) {
                $endPoint = $friend['user_notificationEndPoint'];
                $p256dh = $friend['user_notificationP256dh'];
                $auth = $friend['user_notificationAuth'];
                $type = "browser";
                $sendNotificationsBrowser = $this->chatmessage->queueNotificationWebsite($this->getReceiverId(), $this->getSenderId(), $rawMessage, $type, $endPoint, $p256dh, $auth);

                if ($sendNotificationsBrowser) {
                    $sendNotificationsBrowser = true;
                } else {
                    $sendNotificationsBrowser = false;
                }
            }

            if ($sendNotificationsBrowser && $sendNotificationPhone) {
                echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
            } else {
                echo json_encode(['success' => true, 'message' => 'Message sent successfully but error with notifications']);
            }

        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send message']);
        }
    }
    

    public function sendMessageDataWebsite(): void // Desktop version
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);
    
            $status = "unread";
    
            $this->setSenderId($data->senderId);
            $this->setReceiverId($data->receiverId);
            $this->setMessage($this->validateInput($data->message));

            $replyToChatId = $data->replyToChatId ?? null;

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }
 
             // Validate Token for User
             if (!$this->validateTokenWebsite($token, $this->getSenderId())) {
                 echo json_encode(['success' => false, 'error' => 'Invalid token']);
                 return;
             }

             $user = $this->user->getUserById($_SESSION['userId']);
    
             if ($user['user_id'] != $this->getSenderId())
             {
                 echo json_encode(['success' => false, 'error' => 'Request not allowed']);
                 return;
             }

                $testFriendstatus = $this->friendrequest->getFriendStatus($this->getSenderId(), $this->getReceiverId());

                if ($testFriendstatus != "accepted") {
                    echo json_encode(['success' => false, 'error' => 'You are not friends with this user']);
                    return;
                }

                if ($replyToChatId !== null) {
                    $originalMessage = $this->chatmessage->getMessageById($replyToChatId);
                
                    if (!$originalMessage || 
                        ($originalMessage['chat_senderId'] != $this->getReceiverId() && $originalMessage['chat_receiverId'] != $this->getReceiverId())) {
                        echo json_encode(['success' => false, 'error' => 'Invalid message reference']);
                        return;
                    }
                }
    
            $insertMessage = $this->chatmessage->insertMessageWebsite($this->getSenderId(), $this->getReceiverId(), $this->getMessage(), $replyToChatId, $status);
            $rawMessage = $data->message;
    
            if ($insertMessage) {
                $friend = $this->user->getUserById($this->getReceiverId());
                $sendNotificationPhone = true;
                $sendNotificationsBrowser = true;

                if ($friend['user_token'] !== NULL)
                {
                    $expoToken = $friend['user_token'];
                    $type = "phone";
                    $sendNotificationsPhone = $this->chatmessage->queueNotificationPhone($this->getReceiverId(), $this->getSenderId(), $rawMessage, $type, $expoToken);

                    if ($sendNotificationsPhone) {
                        $sendNotificationPhone = true;
                    } else {
                        $sendNotificationPhone = false;
                    }
                }

                if ($friend['user_notificationPermission'] == 1) {
                    $endPoint = $friend['user_notificationEndPoint'];
                    $p256dh = $friend['user_notificationP256dh'];
                    $auth = $friend['user_notificationAuth'];
                    $type = "browser";
                    $sendNotificationsBrowser = $this->chatmessage->queueNotificationWebsite($this->getReceiverId(), $this->getSenderId(), $rawMessage, $type, $endPoint, $p256dh, $auth);

                    if ($sendNotificationsBrowser) {
                        $sendNotificationsBrowser = true;
                    } else {
                        $sendNotificationsBrowser = false;
                    }
                }

                if ($sendNotificationsBrowser && $sendNotificationPhone) {
                    echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
                } else {
                    echo json_encode(['success' => true, 'message' => 'Message sent successfully but error with notifications']);
                }
    
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send message']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
        }
    }

    public function getAllQueuedNotification()
    {
        require_once 'keys.php';
    
        $tokenAdmin = $_GET['token'] ?? null;
    
        if (!isset($tokenAdmin) || $tokenAdmin !== $tokenRefresh) { 
            http_response_code(401); // Return Unauthorized for cron logs
            echo "❌ Unauthorized.\n";
            exit();
        }
    
        $queuedNotifications = $this->chatmessage->getAllQueuedNotifications();
        if ($queuedNotifications) {
            foreach ($queuedNotifications as $notification) {
                if ($notification['type'] === "browser") {
                    $endPoint = $notification['endpoint'];
                    $p256dh = $notification['p256dh'];
                    $auth = $notification['auth'];
                    $message = $notification['message'];
                    $user = $this->user->getUserById($notification['user_id']);
                    $senderName = $user['user_username'];

                    $sendResult = $this->sendPushNotification($endPoint, $p256dh, $auth, $message, $senderName);

                    if ($sendResult === true || $sendResult === 'invalid') {
                        $this->chatmessage->deleteQueuedNotification($notification['id']);
                        echo "✅ Notification processed and removed.\n";
                    }
                } else {
                    $expoToken = $notification['expoToken'];
                    $message = $notification['message'];
                    $user = $this->user->getUserById($notification['user_id']);
                    $senderName = $user['user_username'];
    
                    if ($this->sendPushNotificationPhone($expoToken, $message, $senderName)) {
                        $this->chatmessage->deleteQueuedNotification($notification['id']);
                        echo "✅ Notification served.\n";
                    }
                }
            }
        } else {
            echo "✅ No notification to serve.\n";
        }
    
        http_response_code(200);
    }
    

    public function uploadChatImage(): void
    {
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }

        $userId = $_SESSION['userId'] ?? null;
    
        if (!$userId || !$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }
    
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Upload error']);
            return;
        }
    
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
        // Check the file extension
        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedExtensions)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file extension']);
            return;
        }
    
        // First check using mime_content_type()
        $fileType = mime_content_type($_FILES['image']['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            // Fallback to finfo if mime_content_type fails
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $fileMimeType = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);
    
            // Second check using finfo
            if (!in_array($fileMimeType, $allowedTypes)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type']);
                return;
            }
        }
    
        // Match your working logic: relative path
        $uploadDir = 'public/upload/chat/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
    
        $filename = uniqid('chat_', true) . '.' . $fileExtension; // Ensure proper extension
        $targetPath = $uploadDir . $filename;
    
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $imageUrl = 'public/upload/chat/' . $filename; // Public URL path
            echo json_encode(['success' => true, 'imageUrl' => $imageUrl]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Could not move uploaded file']);
        }
    }

    public function uploadChatImagePhone(): void
    {
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }

        $userId = $_POST['userId'] ?? null;

        if (!$userId || !$this->validateToken($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }
    
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Upload error']);
            return;
        }
    
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
        // Check the file extension
        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedExtensions)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file extension']);
            return;
        }
    
        // First check using mime_content_type()
        $fileType = mime_content_type($_FILES['image']['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            // Fallback to finfo if mime_content_type fails
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $fileMimeType = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);
    
            // Second check using finfo
            if (!in_array($fileMimeType, $allowedTypes)) {
                echo json_encode(['success' => false, 'message' => 'Invalid file type']);
                return;
            }
        }
    
        // Match your working logic: relative path
        $uploadDir = 'public/upload/chat/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
    
        $filename = uniqid('chat_', true) . '.' . $fileExtension; // Ensure proper extension
        $targetPath = $uploadDir . $filename;
    
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $imageUrl = 'public/upload/chat/' . $filename; // Public URL path
            echo json_encode(['success' => true, 'imageUrl' => $imageUrl]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Could not move uploaded file']);
        }
    }
    

    public function deleteChatImage(): void
    {
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }
        
        $userId = $_SESSION['userId'] ?? null;
        
        if (!$userId || !$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $imageUrl = $data['imageUrl'] ?? null;
        
        if (!$imageUrl) {
            echo json_encode(['success' => false, 'error' => 'Image URL is required']);
            return;
        }

        // Assuming the image file is stored in 'public/upload/chat/'
        $filePath = __DIR__ . '/../public/' . $imageUrl;

        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete the file']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'File not found']);
        }
    }

    
    public function sendPushNotification($endPoint, $p256dh, $auth, $message, $senderName) 
    {
        require 'keys.php';

        // Validate before processing
        if (empty($endPoint) || empty($p256dh) || empty($auth)) {
            error_log("Invalid subscription data");
            return 'invalid';
        }

        $subscription = \Minishlink\WebPush\Subscription::create([
            'endpoint' => $endPoint,
            'keys' => [
                'p256dh' => $p256dh,
                'auth' => $auth
            ]
        ]);

        // Ultra-minimal payload with short keys
        $payload = json_encode([
            't' => mb_substr($senderName, 0, 15), 
            'm' => mb_substr($message, 0, 80)
        ]);

        try {
            // Critical changes: Use aes128gcm + compression
            $webPush = new \Minishlink\WebPush\WebPush([
                'VAPID' => [
                    'publicKey' => $webPushPublicKey,
                    'privateKey' => $webPushPrivateKey,
                    'subject' => 'https://ur-sg.com'
                ]
            ], [
                'contentEncoding' => 'aes128gcm',  
                'compress' => true                 
            ]);

            $webPush->queueNotification($subscription, $payload);

            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    return true;
                } else {
                    $reason = $report->getReason();
                    error_log("Push failed: $endPoint; Reason: $reason");

                    // Handle Mozilla's 413 specifically
                    if (strpos($reason, '413') !== false || 
                        strpos($reason, 'Payload Too Large') !== false) {
                        return 'invalid'; // Delete from queue
                    }

                    // Handle expired subscriptions
                    if (strpos($reason, 'Gone') !== false || 
                        strpos($reason, 'Not Found') !== false) {
                        $this->user->deleteSubscriptionByEndpoint($endPoint);
                        return 'invalid';
                    }
                    
                    return false;
                }
            }
            return false;
        } catch (\Exception $e) {
            error_log('Push error: ' . $e->getMessage());
            return false;
        }
    }
    

    public function deleteMessageWebsite(): void
    {
        if (isset($_POST['userId'])) {
            $userId = $_POST['userId'];
            $chatId = $_POST['chatId'];
            $this->setUserId((int)$userId);

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

            // Validate Token for User
            if (!$this->validateTokenWebsite($token, $userId)) {
                echo json_encode(['success' => false, 'error' => 'Invalid token']);
                return;
            }

            $getMessage = $this->chatmessage->getMessageById($chatId);

            if (!$getMessage) {
                echo json_encode(['success' => false, 'message' => 'Message not found']);
                return;
            }

            if ($getMessage['chat_senderId'] != $userId) {
                echo json_encode(['success' => false, 'message' => 'You are not authorized to delete this message']);
                return;
            }

            $deleteMessage = $this->chatmessage->deleteMessageUser($chatId);

            if ($deleteMessage) {
                echo json_encode(['success' => true, 'message' => 'Pending notification updated']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Could not update notification']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
        }
    }

    public function markMessageAsReadWebsite(): void 
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);
    
            $status = "unread";
    
            $this->setSenderId($data->senderId);
            $this->setReceiverId($data->receiverId);

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

            // Validate Token for User
            if (!$this->validateTokenWebsite($token, $this->getSenderId())) {
                echo json_encode(['success' => false, 'error' => 'Invalid token']);
                return;
            }

            if (isset($_SESSION)) {
                $user = $this->user->getUserById($_SESSION['userId']);

                if ($user['user_id'] != $this->getSenderId())
                {
                    echo json_encode(['success' => false, 'error' => 'Request not allowed']);
                    return;
                }
            }

            $messages = $this->chatmessage->getMessage($this->getSenderId(), $this->getReceiverId());

            if ($messages) {
                $readMessage = $this->chatmessage->updateMessageStatus('read', $this->getSenderId(), $this->getReceiverId());

                if ($readMessage) {
                    echo json_encode(['success' => true, 'message' => 'Message marked as read']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to mark message as read']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'No messages found']);
            }

             
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
        }
    }

    public function getMessageData(): void
    {
        if (isset($_POST['userId']) && isset($_POST['friendId'])) {
            $this->setUserId($_POST['userId']);
            $this->setFriendId((int) $_POST['friendId']);

            $messages = $this->chatmessage->getMessage($this->getUserId(), $this->getFriendId());
            $friend = $this->user->getUserById($this->getFriendId());
            $user = $this->user->getUserById($this->getUserId());

            if ($messages) {
                $this->chatmessage->updateMessageStatus('read', $this->getUserId(), $this->getFriendId());
            }

            if ($messages !== false && $friend !== false && $user !== false) {
                $data = [
                    'success' => true,
                    'friend' => [
                        'user_id' => $friend['user_id'],
                        'user_username' => $friend['user_username'],
                        'user_picture' => $friend['user_picture']
                    ],
                    'user' => [
                        'user_id' => $user['user_id'],
                        'user_username' => $user['user_username'],
                        'user_picture' => $user['user_picture'],
                        'user_hasChatFilter' => $user['user_hasChatFilter']
                    ],
                    'messages' => $messages
                ];

                echo json_encode($data);
            } else {
                $data = [
                    'success' => true,
                    'friend' => [
                        'user_id' => $friend['user_id'],
                        'user_username' => $friend['user_username'],
                        'user_picture' => $friend['user_picture']
                    ],
                ];
                echo json_encode($data);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
        }
    }

    public function getMessageDataPhone(): void
    {
        // Retrieve the Authorization header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1]; // Extract the token from the header
    
            // Validate the token (implement your logic here, e.g., verify JWT or session token)
            if ($this->validateToken($token, $_POST['userId'])) {
                if (isset($_POST['userId']) && isset($_POST['friendId'])) {
                    $this->setUserId($_POST['userId']);
                    $this->setFriendId((int) $_POST['friendId']);
    
                    $messages = $this->chatmessage->getMessage($this->getUserId(), $this->getFriendId());
                    $friend = $this->user->getUserById($this->getFriendId());
                    $user = $this->user->getUserById($this->getUserId());
                    $friendOwnVIPEmotes = $this->items->ownVIPEmotes($this->getFriendId());
                    $userOwnVIPEmotes = $this->items->ownVIPEmotes($this->getUserId());
    
                    if ($messages) {
                        $this->chatmessage->updateMessageStatus('read', $this->getUserId(), $this->getFriendId());
                    }
    
                    if ($messages !== false && $friend !== false && $user !== false) {
                        $data = [
                            'success' => true,
                            'friend' => [
                                'user_id' => $friend['user_id'],
                                'user_username' => $friend['user_username'],
                                'user_picture' => $friend['user_picture'],
                                'ownVIPEmotes' => $friendOwnVIPEmotes,
                            ],
                            'user' => [
                                'user_id' => $user['user_id'],
                                'user_username' => $user['user_username'],
                                'user_picture' => $user['user_picture'],
                                'user_hasChatFilter' => $user['user_hasChatFilter'],
                                'ownVIPEmotes' => $userOwnVIPEmotes,
                            ],
                            'messages' => $messages
                        ];
    
                        echo json_encode($data);
                        return;
                    }
    
                    $data = [
                        'success' => true,
                        'friend' => [
                            'user_id' => $friend['user_id'],
                            'user_username' => $friend['user_username'],
                            'user_picture' => $friend['user_picture'],
                            'ownVIPEmotes' => $friendOwnVIPEmotes,
                        ],
                        'user' => [
                            'user_id' => $user['user_id'],
                            'user_username' => $user['user_username'],
                            'user_picture' => $user['user_picture'],
                            'user_hasChatFilter' => $user['user_hasChatFilter'],
                            'ownVIPEmotes' => $userOwnVIPEmotes,
                        ],
                    ];
                    echo json_encode($data);
                    return;
                }
    
                echo json_encode(['success' => false, 'error' => 'Invalid request']);
                return;
            }
    
            // If token validation fails
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
    
        // If no Authorization header is present
        echo json_encode(['success' => false, 'error' => 'Authorization header missing']);
    }
    

    public function getMessageDataWebsite(): void
    {
        if (isset($_POST['userId']) && isset($_POST['friendId'])) {
            $this->setUserId($_POST['userId']);
            $this->setFriendId((int) $_POST['friendId']);
   
            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

            //  if (!isset($token)) {
            //     echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            //     return;
            //  }
 
             // Validate Token for User
             if (!$this->validateTokenWebsite($token, $this->getUserId())) {
                 echo json_encode(['success' => false, 'error' => 'Invalid token']);
                 return;
             }

            $messages = $this->chatmessage->getMessage($this->getUserId(), $this->getFriendId());
            $friend = $this->user->getUserById($this->getFriendId());
            $user = $this->user->getUserById($this->getUserId());
            $friendOwnVIPEmotes = $this->items->ownVIPEmotes($this->getFriendId());
            $userOwnVIPEmotes = $this->items->ownVIPEmotes($this->getUserId());

            if (!$friend) {
                error_log("Friend not found for ID: " . $this->getFriendId());
                echo json_encode(['success' => false, 'error' => 'Friend not found']);
                return;
            }
            
            if (!$user) {
                error_log("User not found for ID: " . $this->getUserId());
                echo json_encode(['success' => false, 'error' => 'User not found']);
                return;
            }

            if (isset($_POST['firstFriend']) && $_POST['firstFriend'] === "no" && $messages) {
                $this->chatmessage->updateMessageStatus('read', $this->getUserId(), $this->getFriendId());
            }

            if ($messages !== false && is_array($friend) && is_array($user)) {
                $data = [
                    'success' => true,
                    'friend' => [
                        'user_id' => $friend['user_id'],
                        'user_username' => $friend['user_username'],
                        'user_picture' => $friend['user_picture'],
                        'user_lastRequestTime' => $friend['user_lastRequestTime'],
                        'user_isOnline' => $friend['user_isOnline'],
                        'user_isLooking' => $friend['user_isLooking'],
                        'lol_verified' => $friend['lol_verified'],
                        'lol_account' => $friend['lol_account'], 
                        'ownVIPEmotes' => $friendOwnVIPEmotes,
                    ],
                    'user' => [
                        'user_id' => $user['user_id'],
                        'user_username' => $user['user_username'],
                        'user_picture' => $user['user_picture'],
                        'user_hasChatFilter' => $user['user_hasChatFilter'],
                        'ownVIPEmotes' => $userOwnVIPEmotes,
                    ],
                    'messages' => $messages
                ];

                echo json_encode($data);
            } else {
                $data = [
                    'success' => true,
                    'friend' => [
                        'user_id' => $friend['user_id'],
                        'user_username' => $friend['user_username'],
                        'user_picture' => $friend['user_picture'],
                        'ownVIPEmotes' => $friendOwnVIPEmotes,
                    ],
                    'user' => [
                        'user_id' => $user['user_id'],
                        'user_username' => $user['user_username'],
                        'user_picture' => $user['user_picture'],
                        'user_hasChatFilter' => $user['user_hasChatFilter'], 
                        'ownVIPEmotes' => $userOwnVIPEmotes,
                    ],
                ];
                echo json_encode($data);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
        }
    }

    public function deleteOldMessage(): void
    {
        require_once 'keys.php';

        $token = $_GET['token'] ?? null;

        if (!isset($token) || $token !== $tokenRefresh) { 
            header("Location: /?message=Unauthorized");
            exit();
        }
        
        try {
            $this->chatmessage->createRecentMessagesTable();
            $deleteOldMessage = $this->chatmessage->deleteOldMessage();

            if ($deleteOldMessage) {
                echo "Old messages deleted successfully.";
            } else {
                throw new \Exception("Failed to delete old messages.");
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            echo "An error occurred: " . $e->getMessage();
        }
    }

    public function getUnreadMessage(): void
    {
        if (isset($_POST['userId'])) {
            $this->setUserId($_POST['userId']);

            $unreadCounts = $this->chatmessage->countMessage($this->getUserId());

            if ($unreadCounts !== false) {
                if (!is_array($unreadCounts)) {
                    $unreadCounts = [$unreadCounts];
                }

                $data = [
                    'success' => true,
                    'unreadCount' => $unreadCounts
                ];

                echo json_encode($data);
            } else {
                echo json_encode(['success' => false, 'error' => 'No unread messages found']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
        }
    }

    public function getUnreadMessagePhone(): void
    {
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }
    
        if (!isset($_POST['userId'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }
    
        $userId = (int)$_POST['userId'];
    
        // Validate Token for User
        if (!$this->validateToken($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }
    
        $this->setUserId($userId);
    
        // Fetch unread message counts
        $unreadCounts = $this->chatmessage->countMessage($this->getUserId());
    
        if ($unreadCounts !== false) {
            if (!is_array($unreadCounts)) {
                $unreadCounts = [$unreadCounts];
            }
    
            $data = [
                'success' => true,
                'unreadCount' => $unreadCounts
            ];
    
            echo json_encode($data);
        } else {
            echo json_encode(['success' => false, 'error' => 'No unread messages found']);
        }
    }
    

    public function getUnreadMessageWebsite(): void // Website version
    {
        if (isset($_POST['userId'])) {
            $this->setUserId($_POST['userId']);

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

            // Validate Token for User
            if (!$this->validateTokenWebsite($token, $this->getUserId())) {
                echo json_encode(['success' => false, 'error' => 'Invalid token']);
                return;
            }

            $unreadCounts = $this->chatmessage->countMessage($this->getUserId());

            if ($unreadCounts !== false) {
                if (!is_array($unreadCounts)) {
                    $unreadCounts = [$unreadCounts];
                }

                $data = [
                    'success' => true,
                    'unreadCount' => $unreadCounts
                ];

                echo json_encode($data);
            } else {
                echo json_encode(['success' => false, 'error' => 'No unread messages found']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
        }
    }

    public function sendNotificationsPhone($userId, $message, $friendId) {
        $deviceToken = $this->user->getToken($userId);
    
        if ($deviceToken) {
            $friendData = $this->user->getUserById($friendId);
            $title = "URSG - Message from " . $friendData['user_username'];
            $body = $message;
    
            $expoPushUrl = 'https://exp.host/--/api/v2/push/send';
    
            $notificationPayload = [
                'to' => $deviceToken['user_token'],
                'sound' => 'default',
                'title' => $title,
                'body' => $body,
            ];
    
            $headers = [
                'Content-Type: application/json',
            ];
    
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $expoPushUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notificationPayload));
    
            $result = curl_exec($ch);
    
            if ($result === FALSE) {
                die('Curl failed: ' . curl_error($ch));
            }
    
            curl_close($ch);

            return $result;
        }
    }

    public function sendPushNotificationPhone($expoToken, $message, $friendUsername) {

        $title = "URSG - Message from " . $friendUsername;
        $body = $message;

        $expoPushUrl = 'https://exp.host/--/api/v2/push/send';

        $notificationPayload = [
            'to' => $expoToken,
            'sound' => 'default',
            'title' => $title,
            'body' => $body,
        ];

        $headers = [
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $expoPushUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notificationPayload));

        $result = curl_exec($ch);

        if ($result === FALSE) {
            die('Curl failed: ' . curl_error($ch));
        }

        curl_close($ch);

        return $result;
    }

    public function getAccessToken(): string
    {
        $serviceAccountPath = 'serviceAccountKey.json';
        $scopes = ['https://www.googleapis.com/auth/cloud-platform'];
        
        require_once 'vendor/autoload.php';

        $creds = new \Google\Auth\Credentials\ServiceAccountCredentials($scopes, $serviceAccountPath);
        $accessToken = $creds->fetchAuthToken()['access_token'];

        return $accessToken;
    }

    public function validateInput(string $input): string
    {
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }


    public function getSenderId(): int
    {
        return $this->senderId;
    }

    public function setSenderId(int $senderId): void
    {
        $this->senderId = $senderId;
    }

    public function getReceiverId(): int
    {
        return $this->receiverId;
    }

    public function setReceiverId(int $receiverId): void
    {
        $this->receiverId = $receiverId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getFriendId(): int
    {
        return $this->friendId;
    }

    public function setFriendId(int $friendId): void
    {
        $this->friendId = $friendId;
    }
}
