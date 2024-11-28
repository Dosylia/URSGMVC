<?php

namespace controllers;

use models\ChatMessage;
use models\User;
use models\FriendRequest;
use models\GoogleUser;

use traits\SecurityController;

class ChatMessageController
{
    use SecurityController;

    private ChatMessage $chatmessage;
    private User $user;
    private FriendRequest $friendrequest;
    private GoogleUser $googleUser;
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
    }

    public function pagePersoMessage(): void
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf()
        ) {
            $user = $this->user->getUserById($_SESSION['userId']);
            $usersAll = $this->user->getAllUsers();
            $friendRequest = $this->friendrequest->getFriendRequest($_SESSION['userId']);
            $getFriendlist = $this->friendrequest->getFriendlist($_SESSION['userId']);

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

            $current_url = "https://ur-sg.com/persoChat";
            $template = "views/swiping/swiping_persomessage";
            $page_title = "URSG - Chat";
            require "views/layoutSwiping.phtml";
        } else {
            header("Location: /");
            exit();
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
    
                echo json_encode(['success' => true, 'message' => 'Message sent successfully', 'sendNotifications' => $sendNotifications]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send message']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
        }
    }

    public function sendMessageDataPhone(): void
    {
        // Validate Authorization Header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
    
        $token = $matches[1];
    
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
    
        $insertMessage = $this->chatmessage->insertMessage($this->getSenderId(), $this->getReceiverId(), $this->getMessage(), $status);
    
        if ($insertMessage) {
            $sendNotifications = $this->sendNotificationsPhone($this->getReceiverId(), $this->getMessage(), $this->getSenderId());
            echo json_encode(['success' => true, 'message' => 'Message sent successfully', 'sendNotifications' => $sendNotifications]);
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

                if (isset($_SESSION)) {
                    $user = $this->user->getUserById($_SESSION['userId']);
    
                    if ($user['user_id'] != $this->getSenderId())
                    {
                        echo json_encode(['success' => false, 'error' => 'Request not allowed']);
                        return;
                    }
                }
    
            $insertMessage = $this->chatmessage->insertMessage($this->getSenderId(), $this->getReceiverId(), $this->getMessage(), $status);
    
            if ($insertMessage) {
                $userId = $this->getSenderId();

                $sendNotifications = $this->sendNotificationsPhone($this->getReceiverId(), $this->getMessage(), $this->getSenderId());
    
                echo json_encode(['success' => true, 'message' => 'Message sent successfully', 'sendNotifications' => $sendNotifications]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send message']);
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
                        return;
                    }
    
                    $data = [
                        'success' => true,
                        'friend' => [
                            'user_id' => $friend['user_id'],
                            'user_username' => $friend['user_username'],
                            'user_picture' => $friend['user_picture']
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


                if (isset($_SESSION)) {
                    $user = $this->user->getUserById($_SESSION['userId']);
    
                    if ($user['user_id'] != $this->getUserId())
                    {
                        echo json_encode(['success' => false, 'error' => 'Request not allowed']);
                        return;
                    }
                }

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

    public function deleteOldMessage(): void
    {
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
        // Validate Authorization Header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
    
        $token = $matches[1];
    
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

                if (isset($_SESSION)) {
                    $user = $this->user->getUserById($_SESSION['userId']);
    
                    if ($user['user_id'] != $this->getUserId())
                    {
                        echo json_encode(['success' => false, 'error' => 'Request not allowed']);
                        return;
                    }
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

    public function getAccessToken(): string
    {
        $serviceAccountPath = 'serviceAccountKey.json';
        $scopes = ['https://www.googleapis.com/auth/cloud-platform'];
        
        require_once 'vendor/autoload.php';

        $creds = new \Google\Auth\Credentials\ServiceAccountCredentials($scopes, $serviceAccountPath);
        $accessToken = $creds->fetchAuthToken()['access_token'];

        return $accessToken;
    }

    public function validateToken($token, $userId): bool
    {
        $storedTokenData = $this->googleUser->getMasterTokenByUserId($userId);
    
        if ($storedTokenData && isset($storedTokenData['google_masterToken'])) {
            $storedToken = $storedTokenData['google_masterToken'];
            return hash_equals($storedToken, $token);
        }
    
        return false;
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
