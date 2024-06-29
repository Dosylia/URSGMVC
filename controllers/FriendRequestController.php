<?php

namespace controllers;

use models\FriendRequest;
use models\User;
use models\ChatMessage;
use traits\SecurityController;

class FriendRequestController
{
    use SecurityController;

    private FriendRequest $friendrequest;
    private User $user;
    private ChatMessage $chatmessage;
    private $frId;

    
    public function __construct()
    {
        $this -> friendrequest = new FriendRequest();
        $this -> user = new User();
        $this -> chatmessage = new ChatMessage();
    }

    public function acceptFriendRequest()
    {
        $frId = $this->validateInput($_GET["fr_id"]);
        $this->setFrId($frId);

        $updateStatus = $this-> friendrequest -> acceptFriendRequest($this->getFrId());

        if ($updateStatus)
        {
            header("location:index.php?action=userProfile&message=Friend request accepted");
            exit();  
        }
        else
        {
            header("location:index.php?action=userProfile&message=Could not accept it");
            exit();
        }
    }

    public function rejectFriendRequest()
    {
        $frId = $this->validateInput($_GET["fr_id"]);
        $this->setFrId($frId);

        $updateStatus = $this-> friendrequest -> rejectFriendRequest($this->getFrId());

        if ($updateStatus)
        {
            header("location:index.php?action=userProfile&message=Friend request rejected");
            exit();  
        }
        else
        {
            header("location:index.php?action=userProfile&message=Could not accept it");
            exit();
        }
    }

    public function validateInput($input) 
    {
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }

    public function getFrId()
    {
        return $this->frId;
    }

    public function setFrId($frId)
    {
        $this->frId = $frId;
    }

}
