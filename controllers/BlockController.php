<?php

namespace controllers;

use models\Block;
use models\FriendRequest;
use models\User;
use models\GoogleUser;
use models\ChatMessage;

use traits\SecurityController;
use traits\Translatable;

class BlockController
{
    use SecurityController;
    use Translatable;

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

    public function getGoogleUserModel(): GoogleUser
    {
        return $this->googleUser;
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
                return;
            }

            if ($this->block->isBlocked($this->getSenderId(), $this->getReceiverId())) {
                header("location:/friendlistPage?message=User already blocked");
                return;  
            }

            $blockPerson = $this->block->blockPerson($this->getSenderId(), $this->getReceiverId(), $date);

            if ($blockPerson)
            {
                $updateFriend = $this->friendrequest->updateFriend($this->getSenderId(), $this->getReceiverId());
                $deleteMessage = $this->chatmessage->deleteMessageUnfriend($this->getSenderId(), $this->getReceiverId());

                if ($updateFriend)
                {
                    header("location:/friendlistPage?message=User blocked");
                    return;  
                }
                else
                {
                    header("location:/friendlistPage?message=Could not block user");
                    return;
                }
            }
            else
            {
                header("location:/friendlistPage?message=Could not block user");
                return;
            }
        }
        else
        {
            header("location:/friendlistPage?message=No form");
            return;    
        }
    }

    public function blockPersonPhone(): void
    {
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }

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
                echo json_encode(['success' => false, 'message' => 'Invalid token']);
                return;
            }
                

            $blockPerson = $this->block->blockPerson($this->getSenderId(), $this->getReceiverId(), $date);

            if ($blockPerson)
            {
                $updateFriend = $this->friendrequest->updateFriend($this->getSenderId(), $this->getReceiverId());
                $deleteMessage = $this->chatmessage->deleteMessageUnfriend($this->getSenderId(), $this->getReceiverId());

                if ($updateFriend)
                {
                    echo json_encode(['message' => 'Success']);
                    return; 
                }
                else
                {
                    echo json_encode(['message' => 'Could not block user']);
                    return; 
                }
            }
            else
            {
                echo json_encode(['message' => 'Could not block user']);
                return; 
            }
        }
        else
        {
            echo json_encode(['message' => 'No form']);
            return; 
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
                return;  
            }
            else
            {
                header("location:/friendlistPage?message=Could not unblock user");
                return;
            }
        }
        else
        {
            header("location:/friendlistPage?message=No form");
            return;    
        }
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
