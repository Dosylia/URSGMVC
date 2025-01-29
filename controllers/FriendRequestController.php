<?php

namespace controllers;

use models\FriendRequest;
use models\User;
use models\Block;
use models\GoogleUser;
use models\ChatMessage;
use traits\SecurityController;

class FriendRequestController
{
    use SecurityController;

    private FriendRequest $friendrequest;
    private User $user;
    private Block $block;
    private GoogleUser $googleUser;
    private ChatMessage $chatmessage;
    private ?int $frId = null;
    private ?int $userId = null;
    private ?int $senderId = null;
    private ?int $receiverId = null;
    private ?int $friendId = null;

    public function __construct()
    {
        $this->friendrequest = new FriendRequest();
        $this->user = new User();
        $this->block = new Block();
        $this -> googleUser = new GoogleUser();
        $this->chatmessage = new ChatMessage();
    }

    public function pageFriendlist(): void
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf()
        ) {
            // Get important datas
            $user = $this->user->getUserByUsername($_SESSION['username']);
            $allUsers = $this->user->getAllUsers();
            $getFriendlist = $this->friendrequest->getFriendlist($_SESSION['userId']);
            $getBlocklist = $this->block->getBlocklist($_SESSION['userId']);
            $current_url = "https://ur-sg.com/friendlistPage";
            $template = "views/swiping/swiping_friendlist";
            $page_title = "URSG - Friendlist";
            require "views/layoutSwiping.phtml";
        } else {
            header("Location: /");
            exit();
        }
    }

    public function getFriendRequestPhone()
    {
        if (isset($_POST['userId'])) {
            $userId = $_POST['userId'];
            $this->setUserId((int)$userId);

            $friendRequest = $this->friendrequest->getFriendRequest($this->getUserId());

            $amount = 1;
            $user = $this->user->getUserById($userId);

            if ($user['user_isVip'] == 1) {
                $amount = 2;
            }
            $addCurrency = $this->user->addCurrency($userId, $amount);
            $addCurrencySnapshot = $this->user->addCurrencySnapshot($userId, $amount);

            if ($friendRequest) {
                $data = [
                    'message' => 'Success',
                    'friendRequest' => $friendRequest,
                ];

                echo json_encode($data);
            } else {
                echo json_encode(['success' => false, 'message' => 'No friend requests found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
        }
    }

    public function getFriendlistWebsite(): void
    {
        // Validate Authorization Header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
    
        $token = $matches[1];
    
        if (!isset($_POST['userId'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }
    
        $userId = (int)$_POST['userId'];
    
        // Validate Token for User
        if (!$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }
    
        $this->setUserId($userId);
    
        $getFriendlist = $this->friendrequest->getFriendlist($this->getUserId());
    
        if ($getFriendlist) {
            $formattedFriendList = [];
    
            foreach ($getFriendlist as $friend) {
                if ($userId == $friend['sender_id']) {
                    $friendId = $friend['receiver_id'];
                    $friendUsername = $friend['receiver_username'];
                    $friendPicture = $friend['receiver_picture'];
                    $friendGame = $friend['receiver_game'];
                    $friendOnline = $friend['receiver_isOnline'];
                    $friendIsLookingGame = $friend['receiver_isLookingGame'];
                    $friendIsLookingGameUser = $friend['sender_isLookingGame'];
                } else {
                    $friendId = $friend['sender_id'];
                    $friendUsername = $friend['sender_username'];
                    $friendPicture = $friend['sender_picture'];
                    $friendGame = $friend['sender_game'];
                    $friendOnline = $friend['sender_isOnline'];
                    $friendIsLookingGame = $friend['sender_isLookingGame'];
                    $friendIsLookingGameUser = $friend['receiver_isLookingGame'];
                }
    
                // Add friend to the list, excluding the user themselves
                if ($userId != $friendId) {
                    $formattedFriendList[] = [
                        'fr_id' => $friend['fr_id'],
                        'friend_id' => $friendId,
                        'friend_username' => $friendUsername,
                        'friend_picture' => $friendPicture,
                        'friend_game' => $friendGame,
                        'friend_online' => $friendOnline,
                        'friend_isLookingGame' => $friendIsLookingGame,
                        'friend_isLookingGameUser' => $friendIsLookingGameUser,
                        'latest_message_date' => $friend['latest_message_date'],
                    ];
                }
            }
    
            // Check if there are any friends to return
            if (count($formattedFriendList) > 0) {
                $data = [
                    'success' => true,
                    'friendlist' => $formattedFriendList,
                ];
                echo json_encode($data);
            } else {
                echo json_encode(['success' => false, 'error' => 'No friends found']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'No friends found']);
        }
    }

    public function getFriendlist()
    {
        if (isset($_POST['userId'])) {
            $userId = $_POST['userId'];
            $this->setUserId((int)$userId);

            if (isset($_POST['isNotReactNative'])) {
                if (isset($_SESSION)) {
                    $user = $this->user->getUserById($_SESSION['userId']);
    
                    if ($user['user_id'] != $this->getUserId())
                    {
                        echo json_encode(['success' => false, 'error' => 'Request not allowed']);
                        return;
                    }
                }
            }
    
            $getFriendlist = $this->friendrequest->getFriendlist($this->getUserId());
    
            if ($getFriendlist) {
                $formattedFriendList = [];
    
                foreach ($getFriendlist as $friend) {
                    if ($userId == $friend['sender_id']) {
                        $friendId = $friend['receiver_id'];
                        $friendUsername = $friend['receiver_username'];
                        $friendPicture = $friend['receiver_picture'];
                        $friendGame = $friend['receiver_game'];
                    } else {
                        $friendId = $friend['sender_id'];
                        $friendUsername = $friend['sender_username'];
                        $friendPicture = $friend['sender_picture'];
                        $friendGame = $friend['sender_game'];
                    }
    
                    // Add friend to the list, excluding the user themselves
                    if ($userId != $friendId) {
                        $formattedFriendList[] = [
                            'fr_id' => $friend['fr_id'],
                            'friend_id' => $friendId,
                            'friend_username' => $friendUsername,
                            'friend_picture' => $friendPicture,
                            'friend_game' => $friendGame,
                            'latest_message_date' => $friend['latest_message_date']
                        ];
                    }
                }
    
                // Check if there are any friends to return
                if (count($formattedFriendList) > 0) {
                    $data = [
                        'success' => true,
                        'friendlist' => $formattedFriendList,
                    ];
                    echo json_encode($data);
                } else {
                    echo json_encode(['success' => false, 'error' => 'No friends found']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'No friends found']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
        }
    }

    public function getFriendlistPhone(): void
    {
        // Validate Authorization Header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
    
        $token = $matches[1];
    
        if (!isset($_POST['userId'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }
    
        $userId = (int)$_POST['userId'];
    
        // Validate Token for User
        if (!$this->validateToken($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }
    
        $this->setUserId($userId);
    
        $getFriendlist = $this->friendrequest->getFriendlist($this->getUserId());
    
        if ($getFriendlist) {
            $formattedFriendList = [];
    
            foreach ($getFriendlist as $friend) {
                if ($userId == $friend['sender_id']) {
                    $friendId = $friend['receiver_id'];
                    $friendUsername = $friend['receiver_username'];
                    $friendPicture = $friend['receiver_picture'];
                    $friendGame = $friend['receiver_game'];
                    $friendOnline = $friend['receiver_isOnline'];
                } else {
                    $friendId = $friend['sender_id'];
                    $friendUsername = $friend['sender_username'];
                    $friendPicture = $friend['sender_picture'];
                    $friendGame = $friend['sender_game'];
                    $friendOnline = $friend['sender_isOnline'];
                }
    
                // Add friend to the list, excluding the user themselves
                if ($userId != $friendId) {
                    $formattedFriendList[] = [
                        'fr_id' => $friend['fr_id'],
                        'friend_id' => $friendId,
                        'friend_username' => $friendUsername,
                        'friend_picture' => $friendPicture,
                        'friend_game' => $friendGame,
                        'friend_online' => $friendOnline,
                        'latest_message_date' => $friend['latest_message_date']
                    ];
                }
            }
    
            // Check if there are any friends to return
            if (count($formattedFriendList) > 0) {
                $data = [
                    'success' => true,
                    'friendlist' => $formattedFriendList,
                ];
                echo json_encode($data);
            } else {
                echo json_encode(['success' => false, 'error' => 'No friends found']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'No friends found']);
        }
    }
    

    public function swipeStatus(): void
    {
        if (isset($_POST['swipe_yes'])) {
            $requestDate = date('Y-m-d H:i:s');
            $status = 'pending';
            $amount = 10;

            $user = $this->user->getUserById($_POST["senderId"]);

            // if ($user['user_isVip'] == 1) {
            //     $amount = 12;
            // }

            $senderId = $this->validateInput($_POST["senderId"]);
            $this->setSenderId((int)$senderId);
            $receiverId = $this->validateInput($_POST["receiverId"]);
            $this->setReceiverId((int)$receiverId);


            $checkIfPending = $this->friendrequest->checkifPending($this->getSenderId(), $this->getReceiverId());

            if ($checkIfPending) {
                $updateFriendRequest = $this->friendrequest->acceptFriendRequest($checkIfPending['fr_id']);
                if ($updateFriendRequest) {
                    // $addCurrency = $this->user->addCurrency($this->getSenderId(), $amount);
                    // $addCurrencySnapshot = $this->user->addCurrencySnapshot($this->getSenderId(), $amount);
                    echo json_encode(['success' => true, 'error' => 'Swipped No, updated']);
                }
            } else {
                $swipeStatusYes = $this->friendrequest->swipeStatusYes($this->getSenderId(), $this->getReceiverId(), $requestDate, $status);
                // $addCurrency = $this->user->addCurrency($this->getSenderId(), $amount);
                // $addCurrencySnapshot = $this->user->addCurrencySnapshot($this->getSenderId(), $amount);
                echo json_encode(['success' => true, 'error' => 'Swipped yes, created']);
            }


        } elseif (isset($_POST['swipe_no'])) {
            $requestDate = date('Y-m-d H:i:s');
            $status = 'rejected';
            $amount = 10;

            $user = $this->user->getUserById($_POST["senderId"]);

            // if ($user['user_isVip'] == 1) {
            //     $amount = 12;
            // }

            $senderId = $this->validateInput($_POST["senderId"]);
            $this->setSenderId((int)$senderId);
            $receiverId = $this->validateInput($_POST["receiverId"]);
            $this->setReceiverId((int)$receiverId);

            $checkIfPending = $this->friendrequest->checkifPending($this->getSenderId(), $this->getReceiverId());

            if ($checkIfPending) {
                $updateFriendRequest = $this->friendrequest->rejectFriendRequest($checkIfPending['fr_id']);
                if ($updateFriendRequest) {
                    // $addCurrency = $this->user->addCurrency($this->getSenderId(), $amount);
                    // $addCurrencySnapshot = $this->user->addCurrencySnapshot($this->getSenderId(), $amount);
                    echo json_encode(['success' => true, 'error' => 'Swipped No, updated']);
                }
            } else {
                $swipeStatusNo = $this->friendrequest->swipeStatusNo($this->getSenderId(), $this->getReceiverId(), $requestDate, $status);
                // $addCurrency = $this->user->addCurrency($this->getSenderId(), $amount);
                // $addCurrencySnapshot = $this->user->addCurrencySnapshot($this->getSenderId(), $amount);
                echo json_encode(['success' => true, 'error' => 'Swipped No, created']);
            }

        } else {
            echo json_encode(['success' => false, 'error' => 'Proper data were not sent']);
        }
    }

    public function swipeStatusPhone(): void
    {
        // Validate Authorization Header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
    
        $token = $matches[1];
    
        // Check if swipe data is set (either swipe_yes or swipe_no)
        if (isset($_POST['swipe_yes'])) {
            // Check for required fields in POST data
            if (!isset($_POST['senderId']) || !isset($_POST['receiverId'])) {
                echo json_encode(['success' => false, 'error' => 'Invalid request']);
                return;
            }
    
            $senderId = $this->validateInput($_POST["senderId"]);
            $receiverId = $this->validateInput($_POST["receiverId"]);
            
            // Validate token for user
            if (!$this->validateToken($token, $senderId)) {
                echo json_encode(['success' => false, 'error' => 'Invalid token']);
                return;
            }
    
            $this->setSenderId((int)$senderId);
            $this->setReceiverId((int)$receiverId);
    
            // Initialize request data
            $requestDate = date('Y-m-d H:i:s');
            $status = 'pending';
    
            // Check if friend request is pending
            $checkIfPending = $this->friendrequest->checkifPending($this->getSenderId(), $this->getReceiverId());
    
            if ($checkIfPending) {
                // Update friend request to accepted
                $updateFriendRequest = $this->friendrequest->acceptFriendRequest($checkIfPending['fr_id']);
                if ($updateFriendRequest) {
                    echo json_encode(['success' => true, 'message' => 'Swipe No, updated']);
                }
            } else {
                // Create new friend request with status 'pending'
                $swipeStatusYes = $this->friendrequest->swipeStatusYes($this->getSenderId(), $this->getReceiverId(), $requestDate, $status);
                echo json_encode(['success' => true, 'message' => 'Swipe Yes, created']);
            }
    
        } elseif (isset($_POST['swipe_no'])) {
            // Check for required fields in POST data
            if (!isset($_POST['senderId']) || !isset($_POST['receiverId'])) {
                echo json_encode(['success' => false, 'error' => 'Invalid request']);
                return;
            }
    
            $senderId = $this->validateInput($_POST["senderId"]);
            $receiverId = $this->validateInput($_POST["receiverId"]);
            
            // Validate token for user
            if (!$this->validateToken($token, $senderId)) {
                echo json_encode(['success' => false, 'error' => 'Invalid token']);
                return;
            }
    
            $this->setSenderId((int)$senderId);
            $this->setReceiverId((int)$receiverId);
    
            // Initialize request data
            $requestDate = date('Y-m-d H:i:s');
            $status = 'rejected';
    
            // Check if friend request is pending
            $checkIfPending = $this->friendrequest->checkifPending($this->getSenderId(), $this->getReceiverId());
    
            if ($checkIfPending) {
                // Update friend request to rejected
                $updateFriendRequest = $this->friendrequest->rejectFriendRequest($checkIfPending['fr_id']);
                if ($updateFriendRequest) {
                    echo json_encode(['success' => true, 'message' => 'Swipe No, updated']);
                }
            } else {
                // Create new friend request with status 'rejected'
                $swipeStatusNo = $this->friendrequest->swipeStatusNo($this->getSenderId(), $this->getReceiverId(), $requestDate, $status);
                echo json_encode(['success' => true, 'message' => 'Swipe No, created']);
            }
    
        } else {
            echo json_encode(['success' => false, 'error' => 'Proper data were not sent']);
        }
    }
    

    public function swipeStatusWebsite(): void
    {
        if (isset($_POST['swipe_yes'])) {
            $requestDate = date('Y-m-d H:i:s');
            $status = 'pending';
            $amount = 10;

            // // Validate Authorization Header
            // $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

            // if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            //     echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            //     return;
            // }

            // $token = $matches[1];

            // // Validate Token for User
            // if (!$this->validateTokenWebsite($token, $_POST["senderId"])) {
            //     echo json_encode(['success' => false, 'error' => 'Invalid token']);
            //     return;
            // }



            if (isset($_SESSION)) {

                $user = $this->user->getUserById($_SESSION['userId']);

                if ($user['user_id'] != $_POST["senderId"])
                {
                    echo json_encode(['success' => false, 'message' => 'Request not allowed']);
                    return;
                }

                if ($user['user_id'] == $_POST["receiverId"])
                {
                    echo json_encode(['success' => false, 'message' => 'Cant swipe yourself, weirdo']);
                    return;
                }
            }


            $user = $this->user->getUserById($_POST["senderId"]);

            // if ($user['user_isVip'] == 1) {
            //     $amount = 12;
            // }

            $senderId = $this->validateInput($_POST["senderId"]);
            $this->setSenderId((int)$senderId);
            $receiverId = $this->validateInput($_POST["receiverId"]);
            $this->setReceiverId((int)$receiverId);


            $checkIfPending = $this->friendrequest->checkifPending($this->getSenderId(), $this->getReceiverId());

            if ($checkIfPending) {
                $updateFriendRequest = $this->friendrequest->acceptFriendRequest($checkIfPending['fr_id']);
                if ($updateFriendRequest) {
                    // $addCurrency = $this->user->addCurrency($this->getSenderId(), $amount);
                    // $addCurrencySnapshot = $this->user->addCurrencySnapshot($this->getSenderId(), $amount);
                    echo json_encode(['success' => true, 'error' => 'Swipped No, updated']);
                }
            } else {
                $swipeStatusYes = $this->friendrequest->swipeStatusYes($this->getSenderId(), $this->getReceiverId(), $requestDate, $status);
                // $addCurrency = $this->user->addCurrency($this->getSenderId(), $amount);
                // $addCurrencySnapshot = $this->user->addCurrencySnapshot($this->getSenderId(), $amount);
                echo json_encode(['success' => true, 'error' => 'Swipped yes, created']);
            }


        } elseif (isset($_POST['swipe_no'])) {
            $requestDate = date('Y-m-d H:i:s');
            $status = 'rejected';
            $amount = 10;

            // // Validate Authorization Header
            // $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

            // if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            //     echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            //     return;
            // }

            // $token = $matches[1];

            // // Validate Token for User
            // if (!$this->validateTokenWebsite($token, $_POST["senderId"])) {
            //     echo json_encode(['success' => false, 'error' => 'Invalid token']);
            //     return;
            // }

            if (isset($_SESSION)) {

                $user = $this->user->getUserById($_SESSION['userId']);

                if ($user['user_id'] != $_POST["senderId"])
                {
                    echo json_encode(['success' => false, 'message' => 'Request not allowed']);
                    return;
                }
            }


            $user = $this->user->getUserById($_POST["senderId"]);

            // if ($user['user_isVip'] == 1) {
            //     $amount = 12;
            // }

            $senderId = $this->validateInput($_POST["senderId"]);
            $this->setSenderId((int)$senderId);
            $receiverId = $this->validateInput($_POST["receiverId"]);
            $this->setReceiverId((int)$receiverId);

            $checkIfPending = $this->friendrequest->checkifPending($this->getSenderId(), $this->getReceiverId());

            if ($checkIfPending) {
                $updateFriendRequest = $this->friendrequest->rejectFriendRequest($checkIfPending['fr_id']);
                if ($updateFriendRequest) {
                    // $addCurrency = $this->user->addCurrency($this->getSenderId(), $amount);
                    // $addCurrencySnapshot = $this->user->addCurrencySnapshot($this->getSenderId(), $amount);
                    echo json_encode(['success' => true, 'error' => 'Swipped No, updated']);
                }
            } else {
                $swipeStatusNo = $this->friendrequest->swipeStatusNo($this->getSenderId(), $this->getReceiverId(), $requestDate, $status);
                // $addCurrency = $this->user->addCurrency($this->getSenderId(), $amount);
                // $addCurrencySnapshot = $this->user->addCurrencySnapshot($this->getSenderId(), $amount);
                echo json_encode(['success' => true, 'error' => 'Swipped No, created']);
            }

        } else {
            echo json_encode(['success' => false, 'error' => 'Proper data were not sent']);
        }
    }

    public function sendNotificationsPhone($userId, $message, $friendId) {
        $deviceToken = $this->user->getToken($userId);
    
        if ($deviceToken) {
            $friendData = $this->user->getUserById($friendId);
            $title = "URSG - Match with " . $friendData['user_username'];
            $body = $message;
    
            $expoPushUrl = 'https://exp.host/--/api/v2/push/send';
    
            $notificationPayload = [
                'to' => $deviceToken['user_token'],
                'sound' => 'default',
                'title' => $title,
                'body' => $body,
            ];
    
            $headers = [
                'Content-Type: application/json',
            ];
    
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $expoPushUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notificationPayload));
    
            $result = curl_exec($ch);
    
            if ($result === FALSE) {
                die('Curl failed: ' . curl_error($ch));
            }
    
            curl_close($ch);

            return $result;
        }
    }

    public function acceptFriendRequestPhone(): void
    {
        if (isset($_POST["friendId"]) && isset($_POST["frId"])) {
        $frId = $this->validateInput($_POST["frId"]);
        $friendId = $this->validateInput($_POST["friendId"]);
        $this->setFrId((int)$frId);
        $this->setFriendId((int)$friendId);

        $userId = $this -> friendrequest -> getUserIdByFrId($this->getFrId());
        $user = $this->user->getUserById($userId);

        $updateStatus = $this->friendrequest->acceptFriendRequest($this->getFrId());

        if ($updateStatus) {
            $sendNotifications = $this->sendNotificationsPhone($friendId, "You have matched with ". $user['user_username'], $userId);
            echo json_encode(['success' => false, 'message' => 'Success', 'fr_id' => $this->getFrId()]);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Could not accept it']);
            exit();
        }
        } else {
            echo json_encode(['success' => false, 'message' => 'Proper data were not sent']);
            exit();
        }
    }

    public function refuseFriendRequestPhone(): void
    {
        if (isset($_POST["friendId"]) && isset($_POST["frId"])) {
        $frId = $this->validateInput($_POST["frId"]);
        $friendId = $this->validateInput($_POST["friendId"]);
        $this->setFrId((int)$frId);
        $this->setFriendId((int)$friendId);

        $updateStatus = $this->friendrequest->rejectFriendRequest($this->getFrId());

        if ($updateStatus) {
            echo json_encode(['success' => true, 'message' => 'Success', 'fr_id' => $this->getFrId()]);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Could not accept it']);
            exit();
        }
        } else {
            echo json_encode(['success' => false, 'message' => 'Proper data were not sent']);
            exit();
        }
    }
    
    public function updateFriendWebsite()
    {
        if (isset($_POST['param'])) {
            $data = json_decode($_POST['param']);

            if (isset($data->frId) && isset($data->userId) && isset($data->status)) {
                $frId = $data->frId;
                $userId = $data->userId;
                $status = $data->status;

                $user = $this->user->getUserById($_SESSION['userId']);
                if (isset($_SESSION)) {

                    if ($user['user_id'] != $userId)
                    {
                        echo json_encode(['success' => false, 'message' => 'Request not allowed']);
                        return;
                    }
                }

                if ($status == 'accepted') {
                    $updateFriend = $this->friendrequest->acceptFriendRequest($frId);
                    if ($updateFriend) {
                        echo json_encode(['success' => true, 'message' => 'Friend request accepted']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Could not accept friend request']);
                    }
                } else {
                    $updateFriend = $this->friendrequest->rejectFriendRequest($frId);
                    if ($updateFriend) {
                        echo json_encode(['success' => true, 'message' => 'Friend request refused']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Could not refuse friend request']);
                    }
                }

            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid data received']);
            }


        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
        }
    }

    public function getFriendRequest(): void
    {
        if (isset($_POST['userId'])) {
            $userId = $_POST['userId'];
            $this->setUserId((int)$userId);

            $pendingCount = $this->friendrequest->countFriendRequest($this->getUserId());

            $lastRequestTime = $this->user->getLastRequestTime($userId);
            $currentTime = time();

            if ($currentTime - $lastRequestTime > 20) {
                $amount = 2;
                $user = $this->user->getUserById($userId);
    
                if ($user['user_isVip'] == 1) {
                    $amount = 3;
                }
                $addCurrency = $this->user->addCurrency($userId, $amount);
                $addCurrencySnapshot = $this->user->addCurrencySnapshot($userId, $amount);

                if ($addCurrency) {
                    $this->user->updateLastRequestTime($userId);
                }
            }
    

            if ($pendingCount !== false) {
                $data = [
                    'success' => true,
                    'pendingCount' => ['pendingFriendRequest' => $pendingCount]
                ];

                echo json_encode($data);
            } else {
                echo json_encode(['success' => false, 'error' => 'No friend requests found']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
        }
    }

    public function getFriendRequestReact(): void
    {
        // Validate Authorization Header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
    
        $token = $matches[1];
    
        if (!isset($_POST['userId'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }
    
        $userId = (int)$_POST['userId'];
    
        // Validate Token for User
        if (!$this->validateToken($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }
    
        $this->setUserId($userId);

        $lastActivity = $this->user->selectLastActivity($userId);
        $currentTime = time();
        $shouldLogActivity = false;
        
        if ($lastActivity) {
            $lastActivityTime = strtotime($lastActivity['activity_time']);
            $timeDifference = $currentTime - $lastActivityTime;
        
            if ($timeDifference > 3600) {
                $shouldLogActivity = true;
            }
        } else {
            $shouldLogActivity = true;
        }
        
        if ($shouldLogActivity) {
            $this->user->logUserActivity($userId); 
        }

    
        // Fetch pending friend request count
        $pendingCount = $this->friendrequest->countFriendRequest($this->getUserId());
    
        // Currency update logic
        $lastRequestTime = $this->user->getLastRequestTime($userId);
    
        if ($currentTime - $lastRequestTime > 20) {
            $amount = 2;
            $user = $this->user->getUserById($userId);
    
            if ($user['user_isVip'] == 1) {
                $amount = 3;
            }
    
            $addCurrency = $this->user->addCurrency($userId, $amount);
            $addCurrencySnapshot = $this->user->addCurrencySnapshot($userId, $amount);
    
            if ($addCurrency) {
                $this->user->updateLastRequestTime($userId);
            }
        }
    
        // Response with pending count
        if ($pendingCount !== false) {
            $data = [
                'success' => true,
                'pendingCount' => ['pendingFriendRequest' => $pendingCount]
            ];
    
            echo json_encode($data);
        } else {
            echo json_encode(['success' => false, 'error' => 'No friend requests found']);
        }
    }
    

    public function getFriendRequestWebsite(): void
    {
        if (isset($_POST['userId'])) {
            $userId = $_POST['userId'];
            $this->setUserId((int)$userId);

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

            $lastActivity = $this->user->selectLastActivity($userId);
            $currentTime = time();
            $shouldLogActivity = false;
            
            if ($lastActivity) {
                $lastActivityTime = strtotime($lastActivity['activity_time']);
                $timeDifference = $currentTime - $lastActivityTime;
            
                if ($timeDifference > 3600) {
                    $shouldLogActivity = true;
                }
            } else {
                $shouldLogActivity = true;
            }
            
            if ($shouldLogActivity) {
                $this->user->logUserActivity($userId); 
            }

            $pendingCount = $this->friendrequest->countFriendRequest($this->getUserId());

            $lastRequestTime = $this->user->getLastRequestTime($userId);

            if ($currentTime - $lastRequestTime > 20) {
                $amount = 2;
                $user = $this->user->getUserById($userId);
    
                if ($user['user_isVip'] == 1) {
                    $amount = 3;
                }
                $addCurrency = $this->user->addCurrency($userId, $amount);
                $addCurrencySnapshot = $this->user->addCurrencySnapshot($userId, $amount);

                if ($addCurrency) {
                    $this->user->updateLastRequestTime($userId);
                }
            }    

            if ($pendingCount !== false) {
                $data = [
                    'success' => true,
                    'pendingCount' => ['pendingFriendRequest' => $pendingCount]
                ];

                echo json_encode($data);
            } else {
                echo json_encode(['success' => false, 'error' => 'No friend requests found']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
        }
    }

    public function deleteFriendRequestAfterWeek()
    {
        require_once 'keys.php';

        $token = $_GET['token'] ?? null;

        if (!isset($token) || $token !== $tokenRefresh) { 
            header("Location: /?message=Unauthorized");
            exit();
        }
        
        try {
            $deleteFriendRequest = $this->friendrequest->deleteFriendRequestAfterWeek();

            if ($deleteFriendRequest) {
                echo "Old friend requests deleted successfully.";
            } else {
                throw new \Exception("Failed to delete old friend requests.");
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            echo "An error occurred: " . $e->getMessage();
        }
    }

    public function unfriendPerson(): void
    {
        if (isset($_POST['submit']))
        {
            $senderId = $this->validateInput($_POST["senderId"]);
            $this->setSenderId((int) $senderId);            
            $receiverId = $this->validateInput($_POST["receiverId"]);
            $this->setReceiverId((int) $receiverId);
            $date = date("Y-m-d H:i:s");

            $user = $this->user->getUserById($_SESSION['userId']);

            if ($user['user_id'] !== $this->getSenderId()) {
                header("location:/userProfile?message=Unauthorized");
                exit;
            }

            $status = 'rejected';
            $updateFriend = $this->friendrequest->updateFriend($this->getSenderId(), $this->getReceiverId(), $status);

            if ($updateFriend)
            {
                $deleteMessage = $this->chatmessage->deleteMessageUnfriend($this->getSenderId(), $this->getReceiverId());
                header("location:/friendlistPage?message=User unfriended");
                exit();  
            }
            else
            {
                header("location:/friendlistPage?message=Could not unfriend user");
                exit();
            }

         
        }
        else
        {
            header("location:/friendlistPage?message=No form");
            exit();    
        }
    }

    public function unfriendPersonPhone(): void
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
    
        $token = $matches[1];


        $response = array('message' => 'Error');
        if (isset($_POST['userData']))
        {
            $data = json_decode($_POST['userData']);
            $senderId = $this->validateInput($data->senderId);
            $this->setSenderId((int) $senderId);            
            $receiverId = $this->validateInput($data->receiverId);
            $this->setReceiverId((int) $receiverId);
            $date = date("Y-m-d H:i:s");

            // Validate token for user
            if (!$this->validateToken($token, $senderId)) {
                echo json_encode(['success' => false, 'error' => 'Invalid token']);
                return;
            }
                

            $updateFriend = $this->friendrequest->updateFriend($this->getSenderId(), $this->getReceiverId());

            if ($updateFriend)
            {
                $deleteMessage = $this->chatmessage->deleteMessageUnfriend($this->getSenderId(), $this->getReceiverId());
                $response = array('message' => 'Success');
                header('Content-Type: application/json');
                echo json_encode($response);
                exit(); 
            }
            else
            {
                $response = array('message' => 'Could not unfriend user');
                header('Content-Type: application/json');
                echo json_encode($response);
                exit(); 
            }
        }
        else
        {
            $response = array('message' => 'No form');
            header('Content-Type: application/json');
            echo json_encode($response);
            exit(); 
        }
    }

    public function validateInput(string $input): string
    {
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }
    public function validateToken($token, $userId): bool
    {
        $storedTokenData = $this->googleUser->getMasterTokenByUserId($userId);
    
        if ($storedTokenData && isset($storedTokenData['google_masterToken'])) {
            $storedToken = $storedTokenData['google_masterToken'];
            return hash_equals($storedToken, $token);
        }
    
        return false;
    }

    public function validateTokenWebsite($token, $userId): bool
    {
        $storedTokenData = $this->googleUser->getMasterTokenWebsiteByUserId($userId);
    
        if ($storedTokenData && isset($storedTokenData['google_masterTokenWebsite'])) {
            $storedToken = $storedTokenData['google_masterTokenWebsite'];
            return hash_equals($storedToken, $token);
        }
    
        return false;
    }

    public function getFrId(): ?int
    {
        return $this->frId;
    }

    public function setFrId(int $frId): void
    {
        $this->frId = $frId;
    }

    public function getSenderId(): ?int
    {
        return $this->senderId;
    }

    public function setSenderId(int $senderId): void
    {
        $this->senderId = $senderId;
    }

    public function getReceiverId(): ?int
    {
        return $this->receiverId;
    }

    public function setReceiverId(int $receiverId): void
    {
        $this->receiverId = $receiverId;
    }

    public function getFriendId(): ?int
    {
        return $this->friendId;
    }

    public function setFriendId(int $friendId): void
    {
        $this->friendId = $friendId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }
}
