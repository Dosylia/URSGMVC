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
        $this->requireUserSessionOrRedirect($redirectUrl = '/');
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
    public function getItems()
    {
        $response = array('message' => 'Error');
        if (isset($_POST['items'])) 
        {
            $items = $this-> items -> getItemsExceptBadges();

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

    public function ownGoldEmotesPhone()
    {
        $response = array('message' => 'Error');
        if (isset($_POST['userId'])) 
        {
            $userId = $_POST['userId'];

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

            // Validate Token for User
            if (!$this->validateToken($token, $userId)) {
                echo json_encode(['success' => false, 'error' => 'Invalid token']);
                return;
            }

            $ownedGoldEmotes = $this-> items -> ownGoldEmotes($_POST['userId']);

            if ($ownedGoldEmotes)
            {
                $response = array(
                    'ownGoldEmotes' => true,
                    'message' => 'Success'
                );

                header('Content-Type: application/json');
                echo json_encode($response);
                exit;  
            } else {
                $response = array(
                    'ownGoldEmotes' => false,
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

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

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

                    // Base price
                    $price = $item['items_price'];

                    // Apply Gold discount if user is Gold
                    if ($user['user_isGold'] == 1) {
                        $price = $price * 0.8; // 20% off
                    }

                    // Apply item discount if it exists
                    $itemDiscount = $item['items_discount'] ?? 0; // default 0%
                    if ($itemDiscount > 0) {
                        $price = $price * (1 - $itemDiscount / 100);
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
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }
    
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
                    // Base price
                    $price = $item['items_price'];

                    // Apply Gold discount if user is Gold
                    if ($user['user_isGold'] == 1) {
                        $price = $price * 0.8; // 20% off
                    }

                    // Apply item discount if it exists
                    $itemDiscount = $item['items_discount'] ?? 0; // default 0%
                    if ($itemDiscount > 0) {
                        $price = $price * (1 - $itemDiscount / 100);
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

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

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

                    // Base price
                    $price = $item['items_price'];

                    // Apply Gold discount if user is Gold
                    if ($user['user_isGold'] == 1) {
                        $price = $price * 0.8; // 20% off
                    }

                    // Apply item discount if it exists
                    $itemDiscount = $item['items_discount'] ?? 0; // default 0%
                    if ($itemDiscount > 0) {
                        $price = $price * (1 - $itemDiscount / 100);
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

                    // Base price
                    $price = $item['items_price'];

                    // Apply Gold discount if user is Gold
                    if ($user['user_isGold'] == 1) {
                        $price = $price * 0.8; // 20% off
                    }

                    // Apply item discount if it exists
                    $itemDiscount = $item['items_discount'] ?? 0; // default 0%
                    if ($itemDiscount > 0) {
                        $price = $price * (1 - $itemDiscount / 100);
                    }

                    if ($user['user_currency'] >= $price) {
                        $this->items->buyItem($itemId, $userId);
                        $this->user->buyGold($userId);
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
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }
    
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

                    // Base price
                    $price = $item['items_price'];

                    // Apply Gold discount if user is Gold
                    if ($user['user_isGold'] == 1) {
                        $price = $price * 0.8; // 20% off
                    }

                    // Apply item discount if it exists
                    $itemDiscount = $item['items_discount'] ?? 0; // default 0%
                    if ($itemDiscount > 0) {
                        $price = $price * (1 - $itemDiscount / 100);
                    }

                    // Check if the user has enough currency
                    if ($user['user_currency'] >= $price) {
                        $this->items->buyItem($itemId, $userId);
                        $this->user->buyGold($userId);
    
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

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

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

                    // Base price
                    $price = $item['items_price'];

                    // Apply Gold discount if user is Gold
                    if ($user['user_isGold'] == 1) {
                        $price = $price * 0.8; // 20% off
                    }

                    // Apply item discount if it exists
                    $itemDiscount = $item['items_discount'] ?? 0; // default 0%
                    if ($itemDiscount > 0) {
                        $price = $price * (1 - $itemDiscount / 100);
                    }

                    if ($user['user_currency'] >= $price) {
                        $this->items->buyItem($itemId, $userId);
                        $this->user->buyGold($userId);
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
                
                $token = $this->getBearerTokenOrJsonError();
                if (!$token) {
                    return;
                }

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
                $cathegory = $data->category;


                $token = $this->getBearerTokenOrJsonError();
                if (!$token) {
                    return;
                }

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
                        if ($ownedItem['items_category'] == $cathegory) {
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

                $token = $this->getBearerTokenOrJsonError();
                if (!$token) {
                    return;
                }

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

    public function removeBadgeWebsite()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);
            if (isset($data->badgeId) && isset($data->userId)) {
                $badgeId = $data->badgeId;
                $userId = $data->userId;

                $token = $this->getBearerTokenOrJsonError();
                if (!$token) {
                    return;
                }

                if (!$this->validateTokenWebsite($token, $userId)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid token']);
                    return;
                }

                if ($badgeId && $userId) {
                    $removeBadge = $this->items->removeItems($badgeId, $userId);

                    if ($removeBadge) {
                        echo json_encode(['success' => true, 'message' => 'Badge removed successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Badge not removed']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid badge or user']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid data received']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
        }
    }

    public function useBadgeWebsite()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);

            if (isset($data->badgeId) && isset($data->userId)) {
                $badgeId = $data->badgeId;
                $userId = $data->userId;

                $token = $this->getBearerTokenOrJsonError();
                if (!$token) {
                    return;
                }

                if (!$this->validateTokenWebsite($token, $userId)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid token']);
                    return;
                }

                if ($badgeId && $userId) {
                    $useBadge = $this->items->useItems($badgeId, $userId);

                    if ($useBadge) {
                        echo json_encode(['success' => true, 'message' => 'Badge used successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Badge not used']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid badge or user']);
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

                $token = $this->getBearerTokenOrJsonError();
                if (!$token) {
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
