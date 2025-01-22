<?php

namespace controllers;

use models\Admin;
use models\FriendRequest;
use models\User;
use models\GoogleUser;
use models\ChatMessage;

use traits\SecurityController;

class AdminController
{
    use SecurityController;

    private FriendRequest $friendrequest;
    private User $user;
    private GoogleUser $googleUser;
    private ChatMessage $chatmessage;
    private Admin $admin;

    public function __construct()
    {
        $this->friendrequest = new FriendRequest();
        $this -> user = new User();
        $this -> googleUser = new GoogleUser();
        $this->chatmessage = new ChatMessage();
        $this->admin = new Admin();
    }

    public function adminLandingPage(): void
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf() &&
            $this->isModerator()
        )
        {


            $user = $this-> user -> getUserById($_SESSION['userId']);
            $usersOnline = $this-> admin -> countOnlineUsers();

            $current_url = "https://ur-sg.com/admin";
            $template = "views/admin/admin_landing";
            $page_title = "URSG - Admin";
            require "views/layoutAdmin.phtml";
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }
}
