<?php

namespace controllers;

use models\Items;
use models\FriendRequest;
use models\User;
use models\GoogleUser;

use traits\SecurityController;
use traits\Translatable;

class ItemsController
{
    use SecurityController;
    use Translatable;

    private Items $items;
    private User $user;
    private GoogleUser $googleUser;

    public function __construct()
    {
        $this-> items = new Items();
        $this -> user = new User();
        $this -> googleUser = new GoogleUser();

    }

    public function getGoogleUserModel(): GoogleUser
    {
        return $this->googleUser;
    }

    public function pageStore()
    {

        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf()
        )
        {

            // Get important datas
            $this->initializeLanguage();
            $user = $this-> user -> getUserById($_SESSION['userId']);
            $allUsers = $this-> user -> getAllUsers();
            $items = $this-> items -> getItems();
            $ownedItems = $this-> items -> getOwnedItems($_SESSION['userId']);

            if (!is_array($ownedItems)) {
                $ownedItems = [];
            }

            $page_css = ['store_leaderboard'];
            $current_url = "https://ur-sg.com/store";
            $template = "views/swiping/store";
            $page_title = "URSG - Store";
            $picture = "ursg-preview-small";
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

    public function ownVIPEmotesPhone()
    {
        $response = array('message' => 'Error');
        if (isset($_POST['userId'])) 
        {
            $userId = $_POST['userId'];
            // Validate Authorization Header
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

            if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                return;
            }

            $token = $matches[1];

            // Validate Token for User
            if (!$this->validateToken($token, $userId)) {
                echo json_encode(['success' => false, 'error' => 'Invalid token']);
                return;
            }

            $ownedVIPEmotes = $this-> items -> ownVIPEmotes($_POST['userId']);

            if ($ownedVIPEmotes)
            {
                $response = array(
                    'ownVIPEmotes' => true,
                    'message' => 'Success'
                );

                header('Content-Type: application/json');
                echo json_encode($response);
                exit;  
            } else {
                $response = array(
                    'ownVIPEmotes' => false,
                    'message' => 'Success'
                );

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

    public function getOwnedItemsPhone()
    {
        $response = array('message' => 'Error');
        if (isset($_POST['userId'])) 
        {
            $userId = $_POST['userId'];
            // Validate Authorization Header
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

            if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                return;
            }

            $token = $matches[1];

            // Validate Token for User
            if (!$this->validateToken($token, $userId)) {
                echo json_encode(['success' => false, 'error' => 'Invalid token']);
                return;
            }
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

                $price = $item['items_price'];
                if ($user['user_isVip'] == 1) {
                    $price = $item['items_price'] * 0.8;
                }

                if ($item && $user) {
                    if ($user['user_currency'] >= $price) {
                        $this->items->buyItem($itemId, $userId);
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

    public function buyItemPhone()
    {
        // Validate Authorization Header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
    
        $token = $matches[1];
    
        // Check if 'param' is set in POST data
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);
    
            // Check if required fields 'itemId' and 'userId' are set
            if (isset($data->itemId) && isset($data->userId)) {
                $itemId = $data->itemId;
                $userId = $data->userId;
    
                // Validate token for the user
                if (!$this->validateToken($token, $userId)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid token']);
                    return;
                }
    
                $item = $this->items->getItemById($itemId);
                $user = $this->user->getUserById($userId);
                $ownedItems = $this->items->getOwnedItems($userId);
    
                // Check if the user already owns the item
                if ($ownedItems) {
                    foreach ($ownedItems as $ownedItem) {
                        if ($item['items_id'] == $ownedItem['items_id']) {
                            echo json_encode(['success' => false, 'message' => 'Item already owned']);
                            return;
                        }
                    }
                }
    
                // Check if the item and user exist
                if ($item && $user) {
                    // Check if the user has enough currency
                $price = $item['items_price'];
                if ($user['user_isVip'] == 1) {
                    $price = $item['items_price'] * 0.8;
                }

                    if ($user['user_currency'] >= $price) {
                        $this->items->buyItem($itemId, $userId);
    
                        // Update user's currency after purchase
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
    

    public function buyItemWebsite()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);

            if (isset($data->itemId) && isset($data->userId)) {
                $itemId = $data->itemId;
                $userId = $data->userId;

                // Validate Authorization Header
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

                if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                    return;
                }

                $token = $matches[1];

                // Validate Token for User
                if (!$this->validateTokenWebsite($token, $userId)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid token']);
                    return;
                }


                $user = $this->user->getUserById($_SESSION['userId']);
                if (isset($_SESSION)) {

                    if ($user['user_id'] != $userId)
                    {
                        echo json_encode(['success' => false, 'message' => 'Request not allowed']);
                        return;
                    }
                }

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

                $price = $item['items_price'];
                if ($user['user_isVip'] == 1) {
                    $price = $item['items_price'] * 0.8;
                }

                    if ($user['user_currency'] >= $price) {
                        $this->items->buyItem($itemId, $userId);
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

                $price = $item['items_price'];
                if ($user['user_isVip'] == 1) {
                    $price = $item['items_price'] * 0.8;
                }

                    if ($user['user_currency'] >= $price) {
                        $this->items->buyItem($itemId, $userId);
                        $this->user->buyPremium($userId);
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

    public function buyRolePhone()
    {
        // Validate Authorization Header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
    
        $token = $matches[1];
    
        // Check if 'param' is set in POST data
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);
    
            // Check if required fields 'itemId' and 'userId' are set
            if (isset($data->itemId) && isset($data->userId)) {
                $itemId = $data->itemId;
                $userId = $data->userId;
    
                // Validate token for the user
                if (!$this->validateToken($token, $userId)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid token']);
                    return;
                }
    
                $item = $this->items->getItemById($itemId);
                $user = $this->user->getUserById($userId);
                $ownedItems = $this->items->getOwnedItems($userId);
    
                // Check if the user already owns the item
                if ($ownedItems) {
                    foreach ($ownedItems as $ownedItem) {
                        if ($item['items_id'] == $ownedItem['items_id']) {
                            echo json_encode(['success' => false, 'message' => 'Role already owned']);
                            return;
                        }
                    }
                }
    
                // Check if the item and user exist
                if ($item && $user) {

                $price = $item['items_price'];
                if ($user['user_isVip'] == 1) {
                    $price = $item['items_price'] * 0.8;
                }

                    // Check if the user has enough currency
                    if ($user['user_currency'] >= $price) {
                        $this->items->buyItem($itemId, $userId);
                        $this->user->buyPremium($userId);
    
                        // Update user's currency after purchase
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

    public function buyRoleWebsite()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);

            if (isset($data->itemId) && isset($data->userId)) {
                $itemId = $data->itemId;
                $userId = $data->userId;

                // Validate Authorization Header
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

                if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                    return;
                }

                $token = $matches[1];

                // Validate Token for User
                if (!$this->validateTokenWebsite($token, $userId)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid token']);
                    return;
                }

                $user = $this->user->getUserById($_SESSION['userId']);
                if (isset($_SESSION)) {

                    if ($user['user_id'] != $userId)
                    {
                        echo json_encode(['success' => false, 'message' => 'Request not allowed']);
                        return;
                    }
                }

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

                $price = $item['items_price'];
                if ($user['user_isVip'] == 1) {
                    $price = $item['items_price'] * 0.8;
                }

                    if ($user['user_currency'] >= $price) {
                        $this->items->buyItem($itemId, $userId);
                        $this->user->buyPremium($userId);
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

                // Validate Authorization Header
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

                if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                    return;
                }

                $token = $matches[1];

                // Validate Token for User
                if (!$this->validateToken($token, $userId)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid token']);
                    return;
                }

                $ownedItems = $this->items->getOwnedItems($userId);

                if ($ownedItems) {
                    foreach ($ownedItems as $ownedItem) {
                        if ($ownedItem['items_category'] == 'profile Picture') {
                            $this->items->removeItems($ownedItem['userItems_id'], $userId);
                        }
                    }
                } 

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

    public function usePictureFrameWebsite()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);

            if (isset($data->itemId) && isset($data->userId)) {
                $itemId = $data->itemId;
                $userId = $data->userId;

                // Validate Authorization Header
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

                if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                    return;
                }

                $token = $matches[1];

                // Validate Token for User
                if (!$this->validateTokenWebsite($token, $userId)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid token']);
                    return;
                }

                $user = $this->user->getUserById($_SESSION['userId']);
                if (isset($_SESSION)) {

                    if ($user['user_id'] != $userId)
                    {
                        echo json_encode(['success' => false, 'message' => 'Request not allowed']);
                        return;
                    }
                }

                $ownedItems = $this->items->getOwnedItems($userId);

                if ($ownedItems) {
                    foreach ($ownedItems as $ownedItem) {
                        if ($ownedItem['items_category'] == 'profile Picture') {
                            $this->items->removeItems($ownedItem['userItems_id'], $userId);
                        }
                    }
                } 

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

                // Validate Authorization Header
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

                if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                    return;
                }
            
                $token = $matches[1];

                if (!$this->validateToken($token, $userId)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid token']);
                    return;
                }

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

    public function removePictureFrameWebsite()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);

            if (isset($data->itemId) && isset($data->userId)) {
                $itemId = $data->itemId;
                $userId = $data->userId;

                // // Validate Authorization Header
                // $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

                // if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                //     echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                //     return;
                // }

                // $token = $matches[1];

                // // Validate Token for User
                // if (!$this->validateTokenWebsite($token, $userId)) {
                //     echo json_encode(['success' => false, 'error' => 'Invalid token']);
                //     return;
                // }

                $user = $this->user->getUserById($_SESSION['userId']);
                if (isset($_SESSION)) {

                    if ($user['user_id'] != $userId)
                    {
                        echo json_encode(['success' => false, 'message' => 'Request not allowed']);
                        return;
                    }
                }

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
