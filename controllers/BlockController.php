<?php

namespace controllers;

use models\Block;
use models\FriendRequest;
use models\User;
use models\GoogleUser;
use models\ChatMessage;

use traits\SecurityController;

class BlockController
{
    use SecurityController;

    private Block $block;
    private FriendRequest $friendrequest;
    private User $user;
    private GoogleUser $googleUser;
    private ChatMessage $chatmessage;
    private int $senderId;
    private int $receiverId;
    private int $blockId;

    public function __construct()
    {
        $this->block = new Block();
        $this->friendrequest = new FriendRequest();
        $this -> user = new User();
        $this -> googleUser = new GoogleUser();
        $this->chatmessage = new ChatMessage();
    }

    public function blockPerson(): void
    {
        if (isset($_POST['submit']))
        {
            $senderId = $this->validateInput($_POST["senderId"]);
            $this->setSenderId((int) $senderId);            
            $receiverId = $this->validateInput($_POST["receiverId"]);
            $this->setReceiverId((int) $receiverId);
            $date = date("Y-m-d H:i:s");

            $user = $this->user->getUserById($_SESSION['userId']);

            if ($user['user_id'] !== $this->getSenderId()) {
                header("location:/userProfile?message=Unauthorized");
                exit;
            }

            $blockPerson = $this->block->blockPerson($this->getSenderId(), $this->getReceiverId(), $date);

            if ($blockPerson)
            {
                $updateFriend = $this->friendrequest->updateFriend($this->getSenderId(), $this->getReceiverId());
                $deleteMessage = $this->chatmessage->deleteMessageUnfriend($this->getSenderId(), $this->getReceiverId());

                if ($updateFriend)
                {
                    header("location:/friendlistPage?message=User blocked");
                    exit();  
                }
                else
                {
                    header("location:/friendlistPage?message=Could not block user");
                    exit();
                }
            }
            else
            {
                header("location:/friendlistPage?message=Could not block user");
                exit();
            }
        }
        else
        {
            header("location:/friendlistPage?message=No form");
            exit();    
        }
    }

    public function blockPersonPhone(): void
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
    
        $token = $matches[1];


        $response = array('message' => 'Error');
        if (isset($_POST['userData']))
        {
            $data = json_decode($_POST['userData']);
            $senderId = $this->validateInput($data->senderId);
            $this->setSenderId((int) $senderId);            
            $receiverId = $this->validateInput($data->receiverId);
            $this->setReceiverId((int) $receiverId);
            $date = date("Y-m-d H:i:s");

            // Validate token for user
            if (!$this->validateToken($token, $senderId)) {
                echo json_encode(['success' => false, 'error' => 'Invalid token']);
                return;
            }
                

            $blockPerson = $this->block->blockPerson($this->getSenderId(), $this->getReceiverId(), $date);

            if ($blockPerson)
            {
                $updateFriend = $this->friendrequest->updateFriend($this->getSenderId(), $this->getReceiverId());
                $deleteMessage = $this->chatmessage->deleteMessageUnfriend($this->getSenderId(), $this->getReceiverId());

                if ($updateFriend)
                {
                    $response = array('message' => 'Success');
                    header('Content-Type: application/json');
                    echo json_encode($response);
                    exit(); 
                }
                else
                {
                    $response = array('message' => 'Could not block user');
                    header('Content-Type: application/json');
                    echo json_encode($response);
                    exit(); 
                }
            }
            else
            {
                $response = array('message' => 'Could not block user');
                header('Content-Type: application/json');
                echo json_encode($response);
                exit(); 
            }
        }
        else
        {
            $response = array('message' => 'No form');
            header('Content-Type: application/json');
            echo json_encode($response);
            exit(); 
        }
    }

    public function unblockPerson(): void
    {
        if (isset($_POST['submit']))
        {
            $blockId = $this->validateInput($_POST["blockId"]);
            $this->setBlockId((int) $blockId);

            $unblockPerson = $this->block->unblockPerson($this->getBlockId());

            if ($unblockPerson)
            {
                header("location:/friendlistPage?message=User unblocked");
                exit();  
            }
            else
            {
                header("location:/friendlistPage?message=Could not unblock user");
                exit();
            }
        }
        else
        {
            header("location:/friendlistPage?message=No form");
            exit();    
        }
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

    public function getBlockId(): int
    {
        return $this->blockId;
    }

    public function setBlockId(int $blockId): void
    {
        $this->blockId = $blockId;
    }
}
