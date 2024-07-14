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
        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague() && $this->isConnectLeagueLf()) {
            $user = $this->user->getUserById($_SESSION['userId']);
            $usersAll = $this->user->getAllUsers();
            $friendRequest = $this->friendrequest->getFriendRequest($_SESSION['userId']);
            $getFriendlist = $this->friendrequest->getFriendlist($_SESSION['userId']);
            $firstFriend = reset($getFriendlist);

            if (isset($_GET['friend_id'])) {
                $friendId = $_GET['friend_id'];
                $friendChat = $this->user->getUserById($friendId);
            } else {
                if (isset($firstFriend)) {
                    $friendId = ($user['user_id'] == $firstFriend['sender_id']) ? $firstFriend['receiver_id'] : $firstFriend['sender_id'];
                    $friendChat = $this->user->getUserById($friendId);
                }
            }

            $template = "views/swiping/swiping_persomessage";
            $page_title = "URSG - Chat";
            require "views/layoutSwiping.phtml";
        } else {
            header("Location: index.php");
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
            $this->setMessage($data->message);

            $insertMessage = $this->chatmessage->insertMessage($this->getSenderId(), $this->getReceiverId(), $this->getMessage(), $status);

            if ($insertMessage) {
                echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
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
            $this->setFriendId($_POST['friendId']);

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
                        'user_picture' => $user['user_picture']
                    ],
                    'messages' => $messages
                ];

                echo json_encode($data);
            } else {
                echo json_encode(['success' => false, 'error' => 'Could not retrieve data']);
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
