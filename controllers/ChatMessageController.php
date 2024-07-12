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
    private $senderId;
    private $receiverId;
    private $message;
    private $userId;
    private $friendId;

    
    public function __construct()
    {
        $this -> chatmessage = new ChatMessage();
        $this -> user = new User();
        $this -> friendrequest = new FriendRequest();

    }

    public function pagePersoMessage()
    {
        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague() && $this->isConnectLeagueLf()) {
            // Get important data
            $user = $this->user->getUserById($_SESSION['userId']);
            $usersAll = $this->user->getAllUsers();
            $friendRequest = $this->friendrequest->getFriendRequest($_SESSION['userId']);
            $getFriendlist = $this->friendrequest->getFriendlist($_SESSION['userId']);
    
            if (isset($_GET['friend_id'])) {
                $friendId = $_GET['friend_id'];
                $friendChat = $this->user->getUserById($friendId);
            }
    
            $template = "views/swiping/swiping_persomessage";
            $page_title = "URSG - Chat";
            require "views/layoutSwiping.phtml";
        } else {
            header("Location: index.php");
            exit();
        }
    }

    public function sendMessageData()
    {
        if (isset($_POST['param']))
        {
            $data = json_decode($_POST['param']);
            
            $status = "unread";
            
            $senderId = $data->senderId;
            $this->setSenderId($senderId);
            $receiverId = $data->receiverId;
            $this->setReceiverId($receiverId);
            $message = $data->message;
            $this->setMessage($message);
    
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

    public function getMessageData()
    {
        if (isset($_POST['userId']) && isset($_POST['friendId'])) {
            $userId = $_POST['userId'];
            $this->setUserId($userId);
            $friendId = $_POST['friendId'];
            $this->setFriendId($friendId);
    
            $messages = $this->chatmessage->getMessage($this->getUserId(), $this->getFriendId());
            $friend = $this->user->getUserById($friendId);
            $user = $this->user->getUserById($userId);

            if($messages)
            {
                $status = "read";
                $updateStatus = $this->chatmessage->updateMessageStatus($status, $userId, $friendId);
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
    
                // Send JSON response
                echo json_encode($data);
            } else {
                $data = [
                    'success' => true,
                    'friend' => [
                        'user_id' => $friend['user_id'],
                        'user_username' => $friend['user_username'],
                        'user_picture' => $friend['user_picture']
                    ]
                ];

                echo json_encode($data);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
        }
    }

    public function deleteOldMessage()
    {
        try {
            $this->chatmessage->createRecentMessagesTable();
            $deleteOldMessage = $this->chatmessage->deleteOldMessage();

            if ($deleteOldMessage)
            {
                echo "Old messages deleted successfully.";
            }
            else
            {
                throw new Exception("Failed to delete old messages.");
            }

        } catch (Exception $e){
            error_log($e->getMessage());
            echo "An error occurred: " . $e->getMessage();
        }
    }

    public function getUnreadMessage()
    {
        if (isset($_POST['userId'])) {
            $userId = $_POST['userId'];
            $this->setUserId($userId);
    
            $unreadCounts = $this->chatmessage->countMessage($this->getUserId());
    
            if ($unreadCounts !== false) {
                // If $unreadCounts is not already an array, convert it to an array of objects
                if (!is_array($unreadCounts)) {
                    $unreadCounts = [$unreadCounts];
                }
    
                $data = [
                    'success' => true,
                    'unreadCount' => $unreadCounts
                ];
    
                // Send JSON response
                echo json_encode($data);
            } else {
                echo json_encode(['success' => false, 'error' => 'No unread messages found']);
            }
    
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
        }
    }

    public function getSenderId()
    {
        return $this->senderId;
    }

    public function setSenderId($senderId)
    {
        $this->senderId = $senderId;
    }

    public function getReceiverId()
    {
        return $this->receiverId;
    }

    public function setReceiverId($receiverId)
    {
        $this->receiverId = $receiverId;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function getFriendId()
    {
        return $this->friendId;
    }

    public function setFriendId($friendId)
    {
        $this->friendId = $friendId;
    }

}
