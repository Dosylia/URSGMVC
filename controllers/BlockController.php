<?php

namespace controllers;

use models\Block;
use models\FriendRequest;

use traits\SecurityController;

class BlockController
{
    use SecurityController;

    private Block $block;
    private FriendRequest $friendrequest;
    private int $senderId;
    private int $receiverId;
    private int $blockId;

    public function __construct()
    {
        $this->block = new Block();
        $this->friendrequest = new FriendRequest();
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

            $blockPerson = $this->block->blockPerson($this->getSenderId(), $this->getReceiverId(), $date);

            if ($blockPerson)
            {
                $updateFriend = $this->friendrequest->updateFriend($this->getSenderId(), $this->getReceiverId());

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
