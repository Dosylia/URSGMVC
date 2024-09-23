<?php

namespace controllers;

use models\Items;
use models\FriendRequest;
use models\User;

use traits\SecurityController;

class ItemsController
{
    use SecurityController;

    private Items $items;
    private User $user;

    public function __construct()
    {
        $this-> items = new Items();
        $this -> user = new User();

    }

    public function pageStore()
    {

        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectLeague() && $this->isConnectLeagueLf())
        {

            // Get important datas
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $allUsers = $this-> user -> getAllUsers();
            $items = $this-> items -> getItems();
            $current_url = "https://ur-sg.com/store";
            $template = "views/swiping/store";
            $page_title = "URSG - Profile";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }
}
