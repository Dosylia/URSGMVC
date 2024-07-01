<?php

namespace controllers;

use models\FriendRequest;
use models\User;
use models\ChatMessage;
use models\Block;
use traits\SecurityController;

class FriendRequestController
{
    use SecurityController;

    private FriendRequest $friendrequest;
    private User $user;
    private ChatMessage $chatmessage;
    private Block $block;
    private $frId;

    
    public function __construct()
    {
        $this -> friendrequest = new FriendRequest();
        $this -> user = new User();
        $this -> chatmessage = new ChatMessage();
        $this -> block = new Block();
    }

    public function pageFriendlist()
    {

        if (isset($_SESSION['mode'])) {
            $mode = $_SESSION['mode'];
          } else {
            $mode = 'light';
          }
          
          $darkMode = ($mode === 'dark');

          
        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague() && $this->isConnectLeagueLf())
        {

            // Get important datas
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
            $allUsers = $this-> user -> getAllUsers();
            $unreadCount = $this-> chatmessage -> countMessage($_SESSION['userId']);
            $pendingCount = $this-> friendrequest -> countFriendRequest($_SESSION['userId']);
            $getFriendlist = $this-> friendrequest -> getFriendlist($_SESSION['userId']);
            $getBlocklist = $this-> block -> getBlocklist($_SESSION['userId']);

            $template = "views/swiping/swiping_friendlist";
            $page_title = "URSG - Friendlist";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            header("Location: index.php");
            exit();
        }
    }

    public function swipeStatus()
    {
        if(isset($_POST['swipe_yes']))
        {
            $requestDate = date('Y-m-d H:i:s');
            $status = 'pending';

            $senderId = $this->validateInput($_POST["sender_id"]);
            $this->setSenderId($senderId);
            $receiverId = $this->validateInput($_POST["receiver_id"]);
            $this->setReceiverId($receiverId);

            $swipeStatusYes = $this->friendrequest->swipeStatus($this->getSenderId(), $this->getReceiverId(), $requestDate, $status);

            if ($swipeStatusYes)
            {
                header("location:index.php?action=swiping");
                exit();                  
            }

        } 
        elseif (isset($_POST['swipe_no']))
        {
            $requestDate = date('Y-m-d H:i:s');
            $status = 'rejected';

            $senderId = $this->validateInput($_POST["sender_id"]);
            $this->setSenderId($senderId);
            $receiverId = $this->validateInput($_POST["receiver_id"]);
            $this->setReceiverId($receiverId);

            $swipeStatusNo = $this->friendrequest->swipeStatus($this->getSenderId(), $this->getReceiverId(), $requestDate, $status);

            if ($swipeStatusNo)
            {
                header("location:index.php?action=swiping");
                exit();                  
            }

        }
        else
        {
            header("location:index.php?action=swiping&message=Couldnt add/reject user");
            exit();             
        }

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


}
