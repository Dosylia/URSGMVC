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
    private $senderId;
    private $receiverId;

    
    public function __construct()
    {
        $this -> block = new Block();
        $this -> friendrequest = new FriendRequest();
    }

    public function blockPerson()
    {
        if (isset($_POST['submit']))
        {
            $senderId = $this->validateInput($_POST["senderId"]);
            $this->setSenderId($senderId);            
            $receiverId = $this->validateInput($_POST["receiverId"]);
            $this->setReceiverId($receiverId);
            $date = date("Y-m-d H:i:s");


            $blockPerson = $this-> block -> blockPerson($this->getSenderId(), $this->getReceiverId(), $date);

            if ($blockPerson)
            {
                $updateFriend = $this-> friendrequest-> updateFriend($this->getSenderId(), $this->getReceiverId());

                if($updateFriend)
                {
                    header("location:index.php?action=friendlistPage&message=User blocked");
                    exit();  

                }
                else
                {
                    header("location:index.php?action=friendlistPage&message=Could not block user");
                    exit();

                }
            }
            else
            {
                header("location:index.php?action=friendlistPage&message=Could not block user");
                exit();

            }

        }
        else
        {
            header("location:index.php?action=friendlistPage&message=No form");
            exit();    
        }
    }

    public function unblockPerson()
    {
        if (isset($_POST['submit']))
        {
            $blockId = $this->validateInput($_POST["blockId"]);
            $this->setBlockId($blockId);            

            $unblockPerson = $this-> block -> unblockPerson($this->getBlockId());

            if ($unblockPerson)
            {

                    header("location:index.php?action=friendlistPage&message=User unblocked");
                    exit();  
            }
            else
            {
                header("location:index.php?action=friendlistPage&message=Could not unblock user");
                exit();

            }

        }
        else
        {
            header("location:index.php?action=friendlistPage&message=No form");
            exit();    
        }
    }

    public function validateInput($input) 
    {
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
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

    public function getBlockId()
    {
        return $this->blockId;
    }

    public function setBlockId($blockId)
    {
        $this->blockId = $blockId;
    }

}