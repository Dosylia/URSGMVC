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
            $page_title = "URSG - Store";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            header("Location: /");
            exit();
        }
    }
    public function getItems()
    {
        $response = array('message' => 'Error');
        if (isset($_POST['items'])) 
        {
            $items = $this-> items -> getItems();

            if ($items)
            {
                $response = array(
                    'items' => $items,
                    'message' => 'Success'
                );

                header('Content-Type: application/json');
                echo json_encode($response);
                exit;  
            } else {
                $response = array('message' => 'Couldnt get all items');
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;  
            }

        } else {
            $response = array('message' => 'Cant access this');
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;  
        }
    }

    public function getOwnedItems()
    {
        $response = array('message' => 'Error');
        if (isset($_POST['userId'])) 
        {
            $items = $this-> items -> getOwnedItems($_POST['userId']);

            if ($items)
            {
                $response = array(
                    'items' => $items,
                    'message' => 'Success'
                );

                header('Content-Type: application/json');
                echo json_encode($response);
                exit;  
            } else {
                $response = array('message' => 'Couldnt get all items');
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;  
            }

        } else {
            $response = array('message' => 'Cant access this');
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;  
        }
    }

    public function buyItem()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);

            if (isset($data->itemId) && isset($data->userId)) {
                $itemId = $data->itemId;
                $userId = $data->userId;

                $item = $this->items->getItemById($itemId);
                $user = $this->user->getUserById($userId);
                $ownedItems = $this->items->getOwnedItems($userId);

                if ($ownedItems) {
                    foreach ($ownedItems as $ownedItem) {
                        if ($item['items_id'] == $ownedItem['items_id']) {
                            echo json_encode(['success' => false, 'message' => 'Item already owned']);
                            return;
                        }
                    }
                }

                if ($item && $user) {
                    if ($user['user_currency'] >= $item['items_price']) {
                        $this->items->buyItem($itemId, $userId);
                        $price = $item['items_price'];
                        if ($user['user_isVip'] == 1) {
                            $price = $item['items_price'] * 0.8;
                        } 
                        $this->user->updateCurrency($userId, $user['user_currency'] - $price);
                        echo json_encode(['success' => true, 'message' => 'Item bought successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Not enough currency']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid item or user']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid data received']);
            }


        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
        }
    }

    public function buyRole()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);

            if (isset($data->itemId) && isset($data->userId)) {
                $itemId = $data->itemId;
                $userId = $data->userId;

                $item = $this->items->getItemById($itemId);
                $user = $this->user->getUserById($userId);
                $ownedItems = $this->items->getOwnedItems($userId);

                if ($ownedItems) {
                    foreach ($ownedItems as $ownedItem) {
                        if ($item['items_id'] == $ownedItem['items_id']) {
                            echo json_encode(['success' => false, 'message' => 'Role already owned']);
                            return;
                        }
                    }
                }

                if ($item && $user) {
                    if ($user['user_currency'] >= $item['items_price']) {
                        $this->items->buyItem($itemId, $userId);
                        $this->user->buyPremium($userId);
                        $price = $item['items_price'];
                        if ($user['user_isVip'] == 1) {
                            $price = $item['items_price'] * 0.8;
                        } 
                        $this->user->updateCurrency($userId, $user['user_currency'] - $price);
                        echo json_encode(['success' => true, 'message' => 'Role bought successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Not enough currency']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid item or user']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid data received']);
            }


        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
        }
    }

    public function usePictureFrame()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);

            if (isset($data->itemId) && isset($data->userId)) {
                $itemId = $data->itemId;
                $userId = $data->userId;

                if ($itemId && $userId) {
                    $useItems = $this->items->useItems($itemId, $userId);

                    if ($useItems) {
                        echo json_encode(['success' => true, 'message' => 'Frame used successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Frame not used']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid item or user']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid data received']);
            }


        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
        }
    }

    public function removePictureFrame()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);

            if (isset($data->itemId) && isset($data->userId)) {
                $itemId = $data->itemId;
                $userId = $data->userId;

                if ($itemId && $userId) {
                    $removeItems = $this->items->removeItems($itemId, $userId);

                    if ($removeItems) {
                        echo json_encode(['success' => true, 'message' => 'Frame removed successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Frame not removed']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid item or user']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid data received']);
            }


        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
        }
    }
}
