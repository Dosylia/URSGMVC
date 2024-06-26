<?php

namespace controllers;

use models\FriendRequest;
use models\User;
use traits\SecurityController;

class FriendRequestController
{
    use SecurityController;

    private FriendRequest $friendrequest;
    private User $user;


    
    public function __construct()
    {
        $this -> friendreuest = new FriendRequest();
        $this -> user = new User();
    }

    public function pageswiping()
    {
        $user = $this-> user -> getUserByUsername($_SESSION['username']);
        $template = "views/swiping/swiping_main";
        $title = "Swipe test";
        $page_title = "URSG - Swiping";
        require "views/layoutHome.phtml";
    }

}
