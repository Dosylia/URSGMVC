<?php

namespace controllers;

use models\ChatMessage;
use models\User;
use models\FriendRequest;

use traits\SecurityController;

class ChatMessageController
{
    use SecurityController;

    private ChatMessage $chatmessage;
    private User $user;
    private FriendRequest $friendrequest;
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
            
            // ARCANE EVENT
            $totalPiltoverCurrency = 0;
            $totalZaunCurrency = 0;

            foreach ($usersAll as $user) {
                if ($user['user_arcane'] === 'Piltover') {
                    $totalPiltoverCurrency += $user['user_currency'];
                } elseif ($user['user_arcane'] === 'Zaun') {
                    $totalZaunCurrency += $user['user_currency'];
                }
            }

            $totalCurrency = $totalPiltoverCurrency + $totalZaunCurrency;
            $piltoverPercentage = $totalCurrency > 0 ? ($totalPiltoverCurrency / $totalCurrency) * 100 : 0;
            $zaunPercentage = 100 - $piltoverPercentage; 


            $current_url = "https://ur-sg.com/persoChat";
            $template = "views/swiping/swiping_persomessage";
            $page_title = "URSG - Chat";
            require "views/layoutSwiping.phtml";
        } else {
            header("Location: /");
            exit();
        }
    }

    public function sendMessageData(): void
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);

            $status = "unread";

            $this->setSenderId($data->senderId);
            $this->setReceiverId($data->receiverId);
            $this->setMessage($this->validateInput($data->message));

            $insertMessage = $this->chatmessage->insertMessage($this->getSenderId(), $this->getReceiverId(), $this->getMessage(), $status);

            if ($insertMessage) {
                $amount = 10;
                $user = $this->user->getUserById($this->getSenderId());

                if ($user['user_isVip'] == 1) {
                    $amount = 12;
                }
                $addCurrency = $this->user->addCurrency($this->getSenderId(), $amount);
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
