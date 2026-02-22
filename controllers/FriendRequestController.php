<?php

namespace controllers;

use models\FriendRequest;
use models\User;
use models\Block;
use models\GoogleUser;
use models\ChatMessage;
use models\Items;
use traits\SecurityController;

use traits\Translatable;

class FriendRequestController
{
    use SecurityController;
    use Translatable;

    private FriendRequest $friendrequest;
    private User $user;
    private Block $block;
    private GoogleUser $googleUser;
    private ChatMessage $chatmessage;
    private Items $items;
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
        $this->items = new Items();
    }

    public function getGoogleUserModel(): GoogleUser
    {
        return $this->googleUser;
    }

    public function pageFriendlist(): void
    {
        $this->requireUserSessionOrRedirect($redirectUrl = '/');
        // Get important datas
        $this->initializeLanguage();
        $user = $this->user->getUserByUsername($_SESSION['username']);
        $allUsers = $this->user->getAllUsers();
        $getFriendlist = $this->friendrequest->getFriendlist($_SESSION['userId']);
        $getBlocklist = $this->block->getBlocklist($_SESSION['userId']);
        $page_css = ['friendlist'];
        $current_url = "https://ur-sg.com/friendlistPage";
        $template = "views/swiping/swiping_friendlist";
        $picture = "ursg-preview-small";
        $page_title = "URSG - Friendlist";
        require "views/layoutSwiping.phtml";
    }

    public function addFriendAndChat() 
    {
        if (isset($_POST['userId'])) {
            $userId = $_POST['userId'];
            $this->setUserId((int)$userId);
            $friendId = $_POST['friendId'];

            $token = $this->getBearerTokenOrJsonError();
            $this->initializeLanguage();
            if (!$token) {
                return;
            }

            // Validate Token for User
            if (!$this->validateTokenWebsite($token, $this->getUserId())) {
                echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_token')]);
                return;
            }

            if ($userId === $friendId) {
                echo json_encode(['success' => false, 'message' => $this->_('messages.add_self_friend')]);
                return;
            }

            $checkIfPending = $this->friendrequest->checkifPending($this->getUserId(), $_POST['friendId']);

            if ($checkIfPending) {
                $updateFriendRequest = $this->friendrequest->acceptFriendRequest($checkIfPending['fr_id']);
                if ($updateFriendRequest) {
                    echo json_encode(['success' => true, 'message' => $this->_('messages.success_friend_request_accepted')]);
                }
            }

            $requestDate = date('Y-m-d H:i:s');
            $addFriend = $this->friendrequest->addFriend($userId, $friendId, $requestDate);

            if ($addFriend) {
                echo json_encode(['success' => true, 'message' => $this->_('messages.success_friend_request_sent')]);
            } else {
                echo json_encode(['success' => false, 'message' => $this->_('messages.friend_request_failed')]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_request')]);
        }
    }

        public function addFriendAndChatPhone() 
    {
        if (isset($_POST['userId'])) {
            $userId = $_POST['userId'];
            $this->setUserId((int)$userId);
            $friendId = $_POST['friendId'];

            $token = $this->getBearerTokenOrJsonError();
            $this->initializeLanguage();
            if (!$token) {
                return;
            }

            // Validate Token for User
            if (!$this->validateToken($token, $this->getUserId())) {
                echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_token')]);
                return;
            }

            if ($userId === $friendId) {
                echo json_encode(['success' => false, 'message' => $this->_('messages.add_self_friend')]);
                return;
            }

            $checkIfPending = $this->friendrequest->checkifPending($this->getUserId(), $_POST['friendId']);

            if ($checkIfPending) {
                $updateFriendRequest = $this->friendrequest->acceptFriendRequest($checkIfPending['fr_id']);
                if ($updateFriendRequest) {
                    echo json_encode(['success' => true, 'message' => $this->_('messages.success_friend_request_accepted')]);
                }
            }

            $requestDate = date('Y-m-d H:i:s');
            $addFriend = $this->friendrequest->addFriend($userId, $friendId, $requestDate);

            if ($addFriend) {
                echo json_encode(['success' => true, 'message' => $this->_('messages.success_friend_request_sent')]);
            } else {
                echo json_encode(['success' => false, 'message' => $this->_('messages.friend_request_failed')]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_request')]);
        }
    }

    public function getFriendRequestPhone()
    {
        $this->initializeLanguage();
        if (!isset($_POST['userId'])) {
            echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_request')]);
            return;
        }

        $userId = $_POST['userId'];
        $this->setUserId((int)$userId);

        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }

        if (!$this->validateToken($token, $this->getUserId())) {
            echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_token')]);
            return;
        }

        // --- Activity tracking ---
        $lastActivity = $this->user->selectLastActivity($userId);
        if (!$lastActivity || (time() - strtotime($lastActivity['activity_time'])) > 3600) {
            $this->user->logUserActivity($userId);
        }

        
        // --- Rewards & streaks ---
        $rewardData = $this->handleUserRewards($userId);

        // --- Fetch friend requests ---
        $friendRequest = $this->friendrequest->getFriendRequest($this->getUserId());

        $this->user->markUserOnline($userId);

        // --- Fetch friend requests ---
        if ($friendRequest) {
            $data = array_merge([
                'message' => $this->_('messages.success'),
                'friendRequest' => $friendRequest,
            ], $rewardData);

            echo json_encode($data);
        } else {
            echo json_encode(array_merge([
                'success' => false, 
                'message' => $this->_('messages.no_friend_requests'), 
            ], $rewardData));
        }
    }

    public function getAcceptedFriendRequestWebsite(): void
    {
        $this->initializeLanguage();
        if (isset($_POST['userId'])) {
            $userId = $_POST['userId'];
            $this->setUserId((int)$userId);

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

            // Validate Token for User
            if (!$this->validateTokenWebsite($token, $userId)) {
                echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_token')]);
                return;
            }

            $acceptedFriendRequest = $this->friendrequest->getAcceptedFriendRequest($this->getUserId());

            if ($acceptedFriendRequest) {
                $data = [
                    'success' => true,
                    'acceptedFriendRequest' => $acceptedFriendRequest,
                ];
                echo json_encode($data);
            } else {
                echo json_encode(['success' => false, 'message' => $this->_('messages.no_unread_messages')]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_request')]);
        }
    }

    public function updateNotificationFriendRequestAcceptedWebsite(): void
    {
        $this->initializeLanguage();
        if (isset($_POST['userId'])) {
            $userId = $_POST['userId'];
            $frId = $_POST['frId'];
            $this->setUserId((int)$userId);

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

            // Validate Token for User
            if (!$this->validateTokenWebsite($token, $userId)) {
                echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_token')]);
                return;
            }

            $updateNotification = $this->friendrequest->updateNotificationFriendRequestAccepted($frId);

            if ($updateNotification) {
                echo json_encode(['success' => true, 'message' => $this->_('messages.success_accepted_notification_updated')]);
            } else {
                echo json_encode(['success' => false, 'message' => $this->_('messages.update_notification_failed')]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_request')]);
        }
    }

    public function updateNotificationFriendRequestPendingWebsite(): void
    {
        $this->initializeLanguage();
        if (isset($_POST['userId'])) {
            $userId = $_POST['userId'];
            $frId = $_POST['frId'];
            $this->setUserId((int)$userId);

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

            // Validate Token for User
            if (!$this->validateTokenWebsite($token, $userId)) {
                echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_token')]);
                return;
            }

            $updateNotification = $this->friendrequest->updateNotificationFriendRequestPending($frId);

            if ($updateNotification) {
                echo json_encode(['success' => true, 'message' => $this->_('success_pending_notification_updated')]);
            } else {
                echo json_encode(['success' => false, 'message' => $this->_('messages.update_notification_failed')]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_request')]);
        }
    }

    public function getFriendlistWebsite(): void
    {
        $token = $this->getBearerTokenOrJsonError();
        $this->initializeLanguage();
        if (!$token) {
            return;
        }
    
        if (!isset($_POST['userId'])) {
            echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_request')]);
            return;
        }
    
        $userId = (int)$_POST['userId'];
    
        // Validate Token for User
        if (!$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_token')]);
            return;
        }

        setcookie("auth_token", $token, [
            'expires' => time() + 60 * 60 * 24 * 60,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    
        $this->setUserId($userId);
    
        // Also add random chats
        $getFriendlist = $this->friendrequest->getFriendlist($this->getUserId());

        //Fetch random chats and convert them to the same format as friend list
        $randomChatSessions = $this->chatmessage->getRandomChatsSessions($this->getUserId());
        $randomChatList = $this->convertToFriendListFormat($randomChatSessions, $userId);

        // Merge friend list and random chat list
        $mergedList = [];

        if ($randomChatList) {
            foreach ($randomChatList ?: [] as $item) {
                $mergedList[] = $this->filterAssocKeys($item);
            }
        }

        if ($getFriendlist) {
             foreach ($getFriendlist as $item) {
                $mergedList[] = $this->filterAssocKeys($item);
            }
        }
    
        if ($mergedList) {
            $formattedFriendList = [];
    
            foreach ($mergedList as $friend) {
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
                        'is_random_chat' => isset($friend['is_random_chat']) ? $friend['is_random_chat'] : false
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
                echo json_encode(['success' => false, 'message' => $this->_('messages.no_friends_found')]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => $this->_('messages.no_friends_found')]);
        }
    }

    private function convertToFriendListFormat($randomChatSessions, $userId) {
        $formattedList = [];
    
        foreach ($randomChatSessions as $session) {
            $initiator = $this->user->getUserById($session['initiator_user_id']);
            $target = $this->user->getUserById($session['target_user_id']);

            $isSender = ($userId == $session['initiator_user_id']);
            $sender = $isSender ? $initiator : $target;
            $receiver = $isSender ? $target : $initiator;
            $formattedList[] = [
                'fr_id' => null,
                'fr_senderId' => $sender['user_id'],
                'fr_receiverId' => $receiver['user_id'],
                'fr_date' => null,
                'fr_status' => 'random_chat',
                'fr_acceptedAt' => null,
                'sender_id' => $sender['user_id'],
                'sender_username' => $sender['user_username'],
                'sender_picture' => $sender['user_picture'],
                'sender_game' => $sender['user_game'],
                'sender_lastRequestTime' => $sender['user_lastRequestTime'],
                'receiver_id' => $receiver['user_id'],
                'receiver_username' => $receiver['user_username'],
                'receiver_picture' => $receiver['user_picture'],
                'receiver_game' => $receiver['user_game'],
                'receiver_lastRequestTime' => $receiver['user_lastRequestTime'],
                'latest_message_date' => null,
                'sender_isOnline' => $sender['user_isOnline'],
                'receiver_isOnline' => $receiver['user_isOnline'],
                'sender_lastSeen' => $sender['user_lastSeen'],
                'receiver_lastSeen' => $receiver['user_lastSeen'],
                'sender_isLookingGame' => $sender['user_requestIsLooking'] ? 1 : 0,
                'receiver_isLookingGame' => $receiver['user_requestIsLooking'] ? 1 : 0,
                'is_random_chat' => true
            ];
        }
    
        return $formattedList;
    }

    private function filterAssocKeys($array) {
        return array_filter($array, function($key) {
            return !is_numeric($key);
        }, ARRAY_FILTER_USE_KEY);
    }

    public function getFriendlist()
    {
        $this->initializeLanguage();
        if (isset($_POST['userId'])) {
            $userId = $_POST['userId'];
            $this->setUserId((int)$userId);

            if (isset($_POST['isNotReactNative'])) {
                if (isset($_SESSION)) {
                    $user = $this->user->getUserById($_SESSION['userId']);

                    if ($user['user_id'] != $this->getUserId())
                    {
                        echo json_encode(['success' => false, 'message' => $this->_('messages.request_not_allowed')]);
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
                    echo json_encode(['success' => false, 'message' => $this->_('messages.no_friends_found')]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => $this->_('messages.no_friends_found')]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_request')]);
        }
    }

    public function getFriendlistPhone(): void
    {
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }
    
        if (!isset($_POST['userId'])) {
            $this->initializeLanguage();
            echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_request')]);
            return;
        }
    
        $userId = (int)$_POST['userId'];
    
        // Validate Token for User
        if (!$this->validateToken($token, $userId)) {
            $this->initializeLanguage();
            echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_token')]);
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
                $this->initializeLanguage();
                echo json_encode(['success' => false, 'message' => $this->_('messages.no_friends_found')]);
            }
        } else {
            $this->initializeLanguage();
            echo json_encode(['success' => false, 'message' => $this->_('messages.no_friends_found')]);
        }
    }
    

    public function swipeStatus(): void
    {
        if (isset($_POST['swipe_yes'])) {
            $requestDate = date('Y-m-d H:i:s');
            $status = 'pending';
            $amount = 10;

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

            $token = $matches[1];
            $senderId = $this->validateInput($_POST["senderId"]);

            // Validate Token for User
            if (!$this->validateTokenWebsite($token, $senderId)) {
                echo json_encode(['success' => false, 'message' => 'Invalid token']);
                return;
            }
            
            $user = $this->user->getUserById($senderId);
            $this->setSenderId((int)$senderId);
            $receiverId = $this->validateInput($_POST["receiverId"]);
            $this->setReceiverId((int)$receiverId);


            $checkIfPending = $this->friendrequest->checkifPending($this->getSenderId(), $this->getReceiverId());

            if ($checkIfPending) {
                $updateFriendRequest = $this->friendrequest->acceptFriendRequest($checkIfPending['fr_id']);
                if ($updateFriendRequest) {
                    echo json_encode(['success' => true, 'message' => 'Swipped No, updated']);
                }
            } else {
                $swipeStatusYes = $this->friendrequest->swipeStatusYes($this->getSenderId(), $this->getReceiverId(), $requestDate, $status);
                echo json_encode(['success' => true, 'message' => 'Swipped yes, created']);
            }


        } elseif (isset($_POST['swipe_no'])) {

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

            $senderId = $this->validateInput($_POST["senderId"]);

            // Validate Token for User
            if (!$this->validateTokenWebsite($token, $senderId)) {
                echo json_encode(['success' => false, 'message' => 'Invalid token']);
                return;
            }


            $requestDate = date('Y-m-d H:i:s');
            $status = 'rejected';
            $amount = 10;

            $user = $this->user->getUserById($senderId);
            $this->setSenderId((int)$senderId);
            $receiverId = $this->validateInput($_POST["receiverId"]);
            $this->setReceiverId((int)$receiverId);

            $checkIfPending = $this->friendrequest->checkifPending($this->getSenderId(), $this->getReceiverId());

            if ($checkIfPending) {
                $updateFriendRequest = $this->friendrequest->rejectFriendRequest($checkIfPending['fr_id']);
                if ($updateFriendRequest) {
                    echo json_encode(['success' => true, 'message' => 'Swipped No, updated']);
                }
            } else {
                $swipeStatusNo = $this->friendrequest->swipeStatusNo($this->getSenderId(), $this->getReceiverId(), $requestDate, $status);
                echo json_encode(['success' => true, 'message' => 'Swipped No, created']);
            }

        } else {
            echo json_encode(['success' => false, 'message' => 'Proper data were not sent']);
        }
    }

    public function swipeStatusPhone(): void
    {    
        // Check if swipe data is set (either swipe_yes or swipe_no)
        if (isset($_POST['swipe_yes'])) {
            // Check for required fields in POST data

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }
        
            if (!isset($_POST['userId'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid request']);
                return;
            }
        
            $senderId = $this->validateInput($_POST["senderId"]);
        
            // Validate Token for User
            if (!$this->validateToken($token, $senderId)) {
                echo json_encode(['success' => false, 'message' => 'Invalid token']);
                return;
            }

            if (!isset($_POST['senderId']) || !isset($_POST['receiverId'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid request']);
                return;
            }
    
            $receiverId = $this->validateInput($_POST["receiverId"]);
    
            $this->setSenderId((int)$senderId);
            $this->setReceiverId((int)$receiverId);
    
            // Initialize request data
            $requestDate = date('Y-m-d H:i:s');
            $status = 'pending';

            if ($this->block->isBlocked($this->getSenderId(), $this->getReceiverId())) {
                echo json_encode(["status" => "error", "message" => "You can't send a friend request to a blocked user."]);
                return;
            }

            $checkIfPending = $this->friendrequest->checkifPending($this->getSenderId(), $this->getReceiverId());

            if ($checkIfPending) {
                $updateFriendRequest = $this->friendrequest->acceptFriendRequest($checkIfPending['fr_id']);
                if ($updateFriendRequest) {
                    echo json_encode(['success' => true, 'message' => 'Accepted friend request directly']);
                }
            } else {
                // Check if there is already a friend request with those 
                $checkOldFriendRequest = $this->friendrequest->checkOldFriendRequest($this->getSenderId(), $this->getReceiverId());

                if ($checkOldFriendRequest)
                {
                    // Update old one
                    $pendingFriendrequest = $this->friendrequest->pendingFriendrequest(
                        $checkOldFriendRequest['fr_id'],
                        $this->getSenderId(),
                        $this->getReceiverId()
                    );
                } else {
                    // Create new one
                    $swipeStatusYes = $this->friendrequest->swipeStatusYes($this->getSenderId(), $this->getReceiverId(), $requestDate, $status);
                }
                echo json_encode(['success' => true, 'message' => 'Swipped yes, created']);
            }
    
        } elseif (isset($_POST['swipe_no'])) {
            // Check for required fields in POST data
            if (!isset($_POST['senderId']) || !isset($_POST['receiverId'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid request']);
                return;
            }
    
            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }
        
            if (!isset($_POST['userId'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid request']);
                return;
            }
        
            $senderId = $this->validateInput($_POST["senderId"]);
            $receiverId = $this->validateInput($_POST["receiverId"]);
            
            // Validate token for user
            if (!$this->validateToken($token, $senderId)) {
                echo json_encode(['success' => false, 'message' => 'Invalid token']);
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
                $updateFriendRequest = $this->friendrequest->rejectFriendRequest($checkIfPending['fr_id']);
                if ($updateFriendRequest) {
                    echo json_encode(['success' => true, 'message' => 'Swipped No, updated']);
                }
            } else {
                $checkOldFriendRequest = $this->friendrequest->checkOldFriendRequest($this->getSenderId(), $this->getReceiverId());
                if ($checkOldFriendRequest)
                {
                    // Update old one
                    $rejectFriendRequest = $this->friendrequest->rejectFriendRequest($checkOldFriendRequest['fr_id']);
                } else {
                    // Create new one
                    $swipeStatusNo = $this->friendrequest->swipeStatusNo($this->getSenderId(), $this->getReceiverId(), $requestDate, $status);
                }
                echo json_encode(['success' => true, 'message' => 'Swipped No, created']);
            }
    
        } else {
            echo json_encode(['success' => false, 'message' => 'Proper data were not sent']);
        }
    }
    

    public function swipeStatusWebsite(): void
    {
        if (isset($_POST['swipe_yes'])) {
            $requestDate = date('Y-m-d H:i:s');
            $status = 'pending';
            $amount = 10;

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

            // Validate Token for User
            if (!$this->validateTokenWebsite($token, $_POST["senderId"])) {
                echo json_encode(['success' => false, 'message' => 'Invalid token']);
                return;
            }

            $user = $this->user->getUserById($_POST["senderId"]);
            $senderId = $this->validateInput($_POST["senderId"]);
            $this->setSenderId((int)$senderId);
            $receiverId = $this->validateInput($_POST["receiverId"]);
            $this->setReceiverId((int)$receiverId);

            if ($this->block->isBlocked($this->getSenderId(), $this->getReceiverId())) {
                echo json_encode(["status" => "error", "message" => "You can't send a friend request to a blocked user."]);
                return;
            }

            $checkIfPending = $this->friendrequest->checkifPending($this->getSenderId(), $this->getReceiverId());

            if ($checkIfPending) {
                $updateFriendRequest = $this->friendrequest->acceptFriendRequest($checkIfPending['fr_id']);
                if ($updateFriendRequest) {
                    echo json_encode(['success' => true, 'message' => 'Accepted friend request directly']);
                }
            } else {
                // Check if there is already a friend request with those 
                $checkOldFriendRequest = $this->friendrequest->checkOldFriendRequest($this->getSenderId(), $this->getReceiverId());

                if ($checkOldFriendRequest)
                {
                    // Update old one
                    $pendingFriendrequest = $this->friendrequest->pendingFriendrequest(
                        $checkOldFriendRequest['fr_id'],
                        $this->getSenderId(),
                        $this->getReceiverId()
                    );
                } else {
                    // Create new one
                    $swipeStatusYes = $this->friendrequest->swipeStatusYes($this->getSenderId(), $this->getReceiverId(), $requestDate, $status);
                }
                echo json_encode(['success' => true, 'message' => 'Swipped yes, created']);
            }


        } elseif (isset($_POST['swipe_no'])) {
            $requestDate = date('Y-m-d H:i:s');
            $status = 'rejected';
            $amount = 10;

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

            // Validate Token for User
            if (!$this->validateTokenWebsite($token, $_POST["senderId"])) {
                echo json_encode(['success' => false, 'message' => 'Invalid token']);
                return;
            }

            $user = $this->user->getUserById($_POST["senderId"]);
            $senderId = $this->validateInput($_POST["senderId"]);
            $this->setSenderId((int)$senderId);
            $receiverId = $this->validateInput($_POST["receiverId"]);
            $this->setReceiverId((int)$receiverId);

            $checkIfPending = $this->friendrequest->checkifPending($this->getSenderId(), $this->getReceiverId());

            if ($checkIfPending) {
                $updateFriendRequest = $this->friendrequest->rejectFriendRequest($checkIfPending['fr_id']);
                if ($updateFriendRequest) {
                    echo json_encode(['success' => true, 'message' => 'Swipped No, updated']);
                }
            } else {
                $checkOldFriendRequest = $this->friendrequest->checkOldFriendRequest($this->getSenderId(), $this->getReceiverId());
                if ($checkOldFriendRequest)
                {
                    // Update old one
                    $rejectFriendRequest = $this->friendrequest->rejectFriendRequest($checkOldFriendRequest['fr_id']);
                } else {
                    // Create new one
                    $swipeStatusNo = $this->friendrequest->swipeStatusNo($this->getSenderId(), $this->getReceiverId(), $requestDate, $status);
                }
                echo json_encode(['success' => true, 'message' => 'Swipped No, created']);
            }

        } else {
            echo json_encode(['success' => false, 'message' => 'Proper data were not sent']);
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

        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }

        // Validate Token for User
        if (!$this->validateToken($token, $userId)) {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            return;
        }

        $updateStatus = $this->friendrequest->acceptFriendRequest($this->getFrId());

        if ($updateStatus) {
            $sendNotifications = $this->sendNotificationsPhone($friendId, "You have matched with ". $user['user_username'], $userId);
            echo json_encode(['success' => false, 'message' => 'Success', 'fr_id' => $this->getFrId()]);
            return;
        } else {
            echo json_encode(['success' => false, 'message' => 'Could not accept it']);
            return;
        }
        } else {
            echo json_encode(['success' => false, 'message' => 'Proper data were not sent']);
            return;
        }
    }

    public function refuseFriendRequestPhone(): void
    {
        if (isset($_POST["friendId"]) && isset($_POST["frId"])) {
        $frId = $this->validateInput($_POST["frId"]);
        $friendId = $this->validateInput($_POST["friendId"]);
        $this->setFrId((int)$frId);
        $this->setFriendId((int)$friendId);

        $userId = $this -> friendrequest -> getUserIdByFrId($this->getFrId());

        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }

        // Validate Token for User
        if (!$this->validateToken($token, $userId)) {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            return;
        }

        $updateStatus = $this->friendrequest->rejectFriendRequest($this->getFrId());

        if ($updateStatus) {
            echo json_encode(['success' => true, 'message' => 'Success', 'fr_id' => $this->getFrId()]);
            return;
        } else {
            echo json_encode(['success' => false, 'message' => 'Could not accept it']);
            return;
        }
        } else {
            echo json_encode(['success' => false, 'message' => 'Proper data were not sent']);
            return;
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

    public function getFriendRequestReact(): void
    {
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }
    
        if (!isset($_POST['userId'])) {
            $this->initializeLanguage();
            echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_request')]);
            return;
        }
    
        $userId = (int)$_POST['userId'];
    
        // Validate Token for User
        if (!$this->validateToken($token, $userId)) {
            $this->initializeLanguage();
            echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_token')]);
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
    
            if ($user['user_isGold'] == 1) {
                $amount = 3;
            }
    
            $addCurrency = $this->user->addCurrency($userId, $amount);
            $addCurrencySnapshot = $this->user->addCurrencySnapshot($userId, $amount);
    
            if ($addCurrency) {
                $this->user->updateLastRequestTime($userId);
                $this->user->markUserOnline($userId);
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
            $this->initializeLanguage();
            echo json_encode(['success' => false, 'message' => $this->_('messages.no_friend_requests')]);
        }
    }
    

    public function getFriendRequestWebsite(): void
    {
        if (!isset($_POST['userId'])) {
            $this->initializeLanguage();
            echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_request')]);
            return;
        }

        $userId = (int)$_POST['userId'];
        $this->setUserId($userId);

        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }

        if (!$this->validateTokenWebsite($token, $userId)) {
            $this->initializeLanguage();
            echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_token')]);
            return;
        }

        // --- Activity tracking ---
        $lastActivity = $this->user->selectLastActivity($userId);
        if (!$lastActivity || (time() - strtotime($lastActivity['activity_time'])) > 3600) {
            $this->user->logUserActivity($userId);
        }

        // --- Rewards & streaks ---
        $rewardData = $this->handleUserRewards($userId);

        // --- Fetch friend requests ---
        $pendingRequests = $this->friendrequest->getPendingFriendRequests($userId);

        $this->user->markUserOnline($userId);

        if ($pendingRequests) {
            echo json_encode(array_merge([
                'success' => true,
                'pendingRequests' => $pendingRequests,
            ], $rewardData));
        } else {
            $this->initializeLanguage();
            echo json_encode(array_merge([
                'success' => false,
                'message' => $this->_('messages.no_friend_requests'),
            ], $rewardData));
        }
    }

    public function handleUserRewards(int $userId): array
    {
        $user = $this->user->getUserById($userId);
        $streak = $user['user_streak'];
        $lastRequestTime = strtotime($user['user_lastRequestTime']);
        $lastRewardTime = strtotime($user['user_lastReward']);
        $currentTime = time();

        $givenDailyReward = false;
        $givenRequestReward = false;
        $rewardAmount = 0;
        $amountGiven = 0;

        // --- Handle streak + daily reward ---
        if (date('Y-m-d', $lastRequestTime) > date('Y-m-d', $lastRewardTime)) {
            if ($this->user->updateLastRewardTime($userId)) {
                $givenDailyReward = true;

                $lastRewardDate = date('Y-m-d', $lastRewardTime);
                $today = date('Y-m-d');
                $yesterday = date('Y-m-d', strtotime('-1 day'));

                if ($lastRewardDate === $yesterday) {
                    $this->user->incrementStreak($userId);
                    $streak++;
                } elseif ($lastRewardDate !== $today) {
                    $this->user->resetStreak($userId);
                    $streak = 0;
                }
                $rewardAmount = 500 + ($streak * 100);
                $this->user->addCurrency($userId, $rewardAmount);
            }
        }

        // --- Handle badge milestone ---
        if ($streak >= 10) {
            $badge = $this->items->getBadgeByName("10 days Streak");
            if ($badge && !$this->items->userOwnsItem($userId, $badge['items_id'])) {
                $this->items->addItemToUser($userId, $badge['items_id']);
            }
        }

        // --- Handle request-time reward ---
        $lastRequestTimeDb = $this->user->getLastRequestTime($userId);
        if ($currentTime - $lastRequestTimeDb > 100) {
            $amount = 10;
            $amountGiven = 10;
            if ($user['user_isGold'] == 1) {
                $amount = 15;
                $amountGiven = 15;
            }
            if ($this->user->addCurrency($userId, $amount)) {
                $this->user->addCurrencySnapshot($userId, $amount);
                $this->user->updateLastRequestTime($userId);
                $givenRequestReward = true;
            }
        }

        return [
            'givenDailyReward' => $givenDailyReward,
            'givenRequestReward' => $givenRequestReward,
            'rewardAmount' => $rewardAmount,
            'amountGiven' => $amountGiven,
            'streak' => $streak,
            'lastRequestTimeDb' => $lastRequestTimeDb, 
            'currentTime' => $currentTime,
        ];
    }
    

    public function deleteFriendRequestAfterWeek()
    {
        require_once 'keys.php';

        $token = $_GET['token'] ?? null;

        if (!isset($token) || $token !== $tokenRefresh) { 
            header("Location: /?message=Unauthorized");
            return;
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

            if ((int)$user['user_id'] !== (int)$this->getSenderId()) {
                header("location:/userProfile?message=Unauthorized");
                return;
            }

            $status = 'rejected';
            $updateFriend = $this->friendrequest->updateFriend($this->getSenderId(), $this->getReceiverId(), $status);

            if ($updateFriend)
            {
                $deleteMessage = $this->chatmessage->deleteMessageUnfriend($this->getSenderId(), $this->getReceiverId());
                header("location:/friendlistPage?message=User unfriended");
                return;  
            }
            else
            {
                header("location:/friendlistPage?message=Could not unfriend user");
                return;
            }

         
        }
        else
        {
            header("location:/friendlistPage?message=No form");
            return;    
        }
    }

    public function unfriendPersonPhone(): void
    {
        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }

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
                echo json_encode(['success' => false, 'message' => 'Invalid token']);
                return;
            }
                

            $updateFriend = $this->friendrequest->updateFriend($this->getSenderId(), $this->getReceiverId());

            if ($updateFriend)
            {
                $deleteMessage = $this->chatmessage->deleteMessageUnfriend($this->getSenderId(), $this->getReceiverId());
                echo json_encode(['message' => 'Success']);
                return; 
            }
            else
            {
                echo json_encode(['message' => 'Could not unfriend user']);
                return; 
            }
        }
        else
        {
            echo json_encode(['message' => 'No form']);
            return; 
        }
    }

    public function addAsFriendWebsite()
    {

        if (isset($_POST['senderId']) && isset($_POST['receiverId'])) {
            $senderId = $this->validateInput($_POST["senderId"]);
            $this->setSenderId((int)$senderId);
            $receiverId = $this->validateInput($_POST["receiverId"]);
            $this->setReceiverId((int)$receiverId);
            $date = date("Y-m-d H:i:s");

            $userId = $this->getSenderId();

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }
        
            // Validate Token for User
            if (!$this->validateTokenWebsite($token, $senderId)) {
                echo json_encode(['success' => false, 'message' => 'Invalid token']);
                return;
            }

            // Check status of friend request between those 2 users, if there is already a pending one, accept it, if there is already an accepted one, return that they are already friends, if there is a rejected one, return that the previous request was rejected
            $checkStatus = $this->friendrequest->checkStatus($this->getSenderId(), $this->getReceiverId());

            if ($checkStatus && $checkStatus['fr_status'] === 'pending') {
                // This should only work if it's the receiver that accepts the friend request, otherwise it would update it to accepted when the sender tries to send a new friend request while it's still pending
                if ($checkStatus['fr_receiverId'] === $this->getSenderId()) {
                    $updateFriendRequest = $this->friendrequest->acceptFriendRequest($checkStatus['fr_id']);
                    if ($updateFriendRequest) {
                        echo json_encode(['success' => true, 'message' => 'Friend request accepted']);
                        return;
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Friend request is pending, waiting for the other user to accept it']);
                    return;
                }
            } else if ($checkStatus && $checkStatus['fr_status'] === 'accepted') {
                echo json_encode(['success' => false, 'message' => 'You are already friends']);
                return;
            } else if ($checkStatus && $checkStatus['fr_status'] === 'rejected') {
                // Person that refused in past, will make it as pending but if it's person that already got refused it will return that the previous request was rejected, this is to prevent spam of friend requests after being refused
                if ($checkStatus['fr_senderId'] === $this->getSenderId()) {
                    $updateFriendRequest = $this->friendrequest->pendingFriendrequest(
                        $checkStatus['fr_id'],
                        $this->getSenderId(),
                        $this->getReceiverId()
                    );
                    if ($updateFriendRequest) {
                        echo json_encode(['success' => true, 'message' => 'Successfully sent friend request']);
                        return;
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to send friend request']);
                        return;
                    }
                }
                
                echo json_encode(['success' => false, 'message' => 'Your previous friend request was rejected, please wait before sending a new one']);
                return;
            }

            $status = 'pending';
            $swipeStatusYes = $this->friendrequest->swipeStatusYes($this->getSenderId(), $this->getReceiverId(), $date, $status);

            if ($swipeStatusYes) {
                echo json_encode(['success' => true, 'message' => 'Successfully sent friend request']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send friend request']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No form']);
        }
    }

    public function validateInput(string $input): string
    {
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
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
