<?php

namespace controllers;

use models\FriendRequest;
use models\User;
use models\GoogleUser;
use models\PlayerFinder;

use traits\SecurityController;
use traits\Translatable;

class PlayerFinderController
{
    use SecurityController;
    use Translatable;

    private FriendRequest $friendrequest;
    private User $user;
    private GoogleUser $googleUser;
    private PlayerFinder $playerFinder;

    public function __construct()
    {
        $this->friendrequest = new FriendRequest();
        $this -> user = new User();
        $this -> googleUser = new GoogleUser();
        $this -> playerFinder = new PlayerFinder();
    }

    public function getGoogleUserModel(): GoogleUser
    {
        return $this->googleUser;
    }

    public function playerfinderPage()
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) && 
            $this->isConnectLf()
        )
        {
            $user = $this->user->getUserById($_SESSION['userId']);
            $lol_ranks = ["Unranked", "Iron", "Bronze", "Silver", "Gold", "Platinum", "Emerald", "Diamond", "Master", "Grand Master", "Challenger"];
            $lol_roles = ["Support", "AD Carry", "Mid laner", "Jungler", "Top laner"];
            $valorant_ranks = ["Unranked", "Iron", "Bronze", "Silver", "Gold", "Platinum", "Diamond", "Ascendant", "Immortal", "Radiant"];
            $valorant_roles = ["Controller", "Duelist", "Initiator", "Sentinel"];
                        $regionAbbreviations = [
                "Europe West" => "EUW",
                "North America" => "NA",
                "Europe Nordic & East" => "EUNE",
                "Brazil" => "BR",
                "Latin America North" => "LAN",
                "Latin America South" => "LAS",
                "Oceania" => "OCE",
                "Russia" => "RU",
                "Turkey" => "TR",
                "Japan" => "JP",
                "Korea" => "KR",
            ];

            $playerFinderPost = $this->playerFinder->getPlayerFinderPost($user['user_id']);

            if ($playerFinderPost && !empty($playerFinderPost['pf_peopleInterest'])) {
                $interested = json_decode($playerFinderPost['pf_peopleInterest'], true);

                if (!empty($interested)) {
                    $userIds = array_map(fn($entry) => $entry['userId'], $interested);
                    $interestedData = $this->user->getUsersByIds($userIds, $_SESSION['userId']);
                } else {
                    $interestedData = [];
                }
            } else {
                $interestedData = [];
            }

            $availableRoles = [
                'League of Legends' => array_merge(['Any'], $lol_roles),
                'Valorant' => array_merge(['Any'], $valorant_roles)
            ];

            $availableRanks = [
                'League of Legends' => array_merge(['Any'], $lol_ranks),
                'Valorant' => array_merge(['Any'], $valorant_ranks)
            ];

            if ($user['user_game'] === 'League of Legends') {
                $availableRanksCreate = $lol_ranks;
                $availableRolesCreate = $lol_roles;
            } elseif ($user['user_game'] === 'Valorant') {
                $availableRanksCreate = $valorant_ranks;
                $availableRolesCreate = $valorant_roles;
            }
            $this->initializeLanguage();
            $page_css = ['playerfinder', 'tools/offline_modal'];
            $playerFinderAll = $this->playerFinder->getAllPlayerFinderPost();
            $current_url = "https://ur-sg.com/playerFinder";
            $template = "views/swiping/playerfinder";
            $page_title = "URSG - Player Finder";
            $picture = "ursg-preview-small";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            $interestedData = [];
            $playerFinderAll = $this->playerFinder->getAllPlayerFinderPost();
            $lol_ranks = ["Unranked", "Iron", "Bronze", "Silver", "Gold", "Platinum", "Emerald", "Diamond", "Master", "Grand Master", "Challenger"];
            $lol_roles = ["Support", "AD Carry", "Mid laner", "Jungler", "Top laner", "Fill"];
            $valorant_ranks = ["Unranked", "Iron", "Bronze", "Silver", "Gold", "Platinum", "Diamond", "Ascendant", "Immortal", "Radiant"];
            $valorant_roles = ["Controller", "Duelist", "Initiator", "Sentinel", "Fill"];
            $regionAbbreviations = [
                "Europe West" => "EUW",
                "North America" => "NA",
                "Europe Nordic & East" => "EUNE",
                "Brazil" => "BR",
                "Latin America North" => "LAN",
                "Latin America South" => "LAS",
                "Oceania" => "OCE",
                "Russia" => "RU",
                "Turkey" => "TR",
                "Japan" => "JP",
                "Korea" => "KR",
            ];

            $availableRoles = [
                'League of Legends' => array_merge(['Any'], $lol_roles),
                'Valorant' => array_merge(['Any'], $valorant_roles)
            ];

            $availableRanks = [
                'League of Legends' => array_merge(['Any'], $lol_ranks),
                'Valorant' => array_merge(['Any'], $valorant_ranks)
            ];
            $this->initializeLanguage();
            $page_css = ['playerfinder', 'tools/offline_modal'];
            $current_url = "https://ur-sg.com/playerFinder";
            $template = "views/swiping/playerfinder";
            $page_title = "URSG - Player Finder";
            $picture = "ursg-preview-small";
            require "views/layoutSwiping_noheader.phtml";
        }
    }

    public function addPlayerFinderPost()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
    
        $token = $matches[1];
    
        // Decode raw JSON input
        $input = json_decode(file_get_contents('php://input'), true);
    
        if (!isset($input['userId'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }
    
        $userId = (int) $input['userId'];
        $voiceChat = $input['voiceChat'] ?? null;
        $role = $input['roleLookingFor'] ?? null;
        $rank = $input['rankLookingFor'] ?? null;
        $description = $input['description'] ?? null;

        if ($voiceChat == "true") {
            $voiceChat = 1;
        } else {
            $voiceChat = 0;
        }
    
        // Validate Token for User
        if (!$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }

        $user = $this->user->getUserById($userId);
        $oldTime = $user['user_requestIsLooking'];

        $setStatus = $this->user->userIsLookingForGame($userId);

        $getPlayerFinderPost = $this->playerFinder->getPlayerFinderPost($userId);

        if ($getPlayerFinderPost) {
            $deletePlayerFinderPost = $this->playerFinder->deletePlayerFinderPost($getPlayerFinderPost['pf_id']);

            if (!$deletePlayerFinderPost) {
                echo json_encode(['success' => false, 'error' => 'Failed to delete existing Player Finder post']);
                return;
            }
        }

        $addPlayerFinderPost = $this->playerFinder->addPlayerFinderPost(
            $role,
            $rank,
            $description,
            $voiceChat,
            $user['user_game'],
            $userId
        );

        if ($addPlayerFinderPost) {
            echo json_encode(['success' => true, 'message' => 'Player Finder post added successfully', 'oldTime' => $oldTime]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to add Player Finder post']);
        }
    }

    public function deletePlayerFinderPost()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
    
        $token = $matches[1];
    
        // Decode raw JSON input
        $input = json_decode(file_get_contents('php://input'), true);
    
        if (!isset($input['userId'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }
    
        $userId = (int) $input['userId'];
        $postId = $input['postId'] ?? null;
    
        // Validate Token for User
        if (!$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }

        $getPlayerFinderPost = $this->playerFinder->getPlayerFinderPostById($postId);

        if (!$getPlayerFinderPost) {
            echo json_encode(['success' => false, 'error' => 'No Player Finder post found']);
            return;
        }

        if ($getPlayerFinderPost['user_id'] != $userId) {
            echo json_encode(['success' => false, 'error' => 'You cannot delete this post']);
            return;
        }
    
        $deletePlayerFinderPost = $this->playerFinder->deletePlayerFinderPost($postId);
    
        if ($deletePlayerFinderPost) {
            echo json_encode(['success' => true, 'message' => 'Player Finder post deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete Player Finder post']);
        }
    }

    public function playWithThem() 
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
    
        $token = $matches[1];
    
        // Decode raw JSON input
        $input = json_decode(file_get_contents('php://input'), true);
    
        if (!isset($input['userId'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }
    
        $userId = (int) $input['userId'];
        $postId = $input['postId'] ?? null;
    
        // Validate Token for User
        if (!$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }

        $getPlayerFinderPost = $this->playerFinder->getPlayerFinderPostById($postId);

        if (!$getPlayerFinderPost) {
            echo json_encode(['success' => false, 'error' => 'No Player Finder post found']);
            return;
        }

        if ($getPlayerFinderPost['user_id'] == $userId) {
            echo json_encode(['success' => false, 'error' => 'You cannot play with yourself']);
            return;
        }

        $interested = json_decode($getPlayerFinderPost['pf_peopleInterest'], true);
        if (!is_array($interested)) $interested = [];

        $alreadyInterested = false;
        foreach ($interested as $entry) {
            if ($entry['userId'] == $userId) {
                $alreadyInterested = true;
                break;
            }
        }

        if (!$alreadyInterested) {
            $interested[] = ['userId' => $userId, 'seen' => false];
        }

        $playWithThem = $this->playerFinder->updatePeopleInterest($postId, $interested);
    
        if ($playWithThem) {
            // Check if they are friends, if yes add isFriend yes, to redirect them to chat on front end
            $friendRequest = $this->friendrequest->getFriendStatus($userId, $getPlayerFinderPost['user_id']);

            if ($friendRequest === "accepted") {
                echo json_encode([
                    'success' => true,
                    'message' => 'Redirecting to friend chat',
                    'isFriend' => true,
                    'friendId' => $getPlayerFinderPost['user_id']
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => 'They will know you want to play with them',
                    'isFriend' => false
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to play with them']);
        }
    }
    
    public function getInterestedPeople()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
    
        $token = $matches[1];

        $userId = $_POST['userId'] ?? null;
    
        if (!isset($userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }
    
        // Validate Token for User
        if (!$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }

        $getPlayerFinderPost = $this->playerFinder->getPlayerFinderPost($userId);

        if (!$getPlayerFinderPost) {
            echo json_encode(['success' => false, 'error' => 'No Player Finder post found']);
            return;
        }

        $interested = json_decode($getPlayerFinderPost['pf_peopleInterest'], true);
        if (!is_array($interested)) $interested = [];

        $unseenUsers = array_filter($interested, fn($entry) => !$entry['seen']);
        $unseenIds = array_map(fn($entry) => $entry['userId'], $unseenUsers);

        if (empty($unseenIds)) {
            echo json_encode(['success' => true, 'interestedUsers' => false]);
            return;
        }

        $users = $this->user->getUsersByIds($unseenIds, $userId);

        $notifications = [];
        foreach ($users as $user) {
            $notifications[] = [
                'fr_id' => $getPlayerFinderPost['pf_id'],
                'userId' => $userId, 
                'friendId' => $user['user_id'],
                'user_username' => $user['user_username'],
                'pf_status' => 'unseen',
            ];
        }

        echo json_encode([
            'success' => true,
            'interestedUsers' => $notifications
        ]);
    }

        public function addPlayerFinderPostPhone()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
    
        $token = $matches[1];
    
        // Decode raw JSON input
        $input = json_decode(file_get_contents('php://input'), true);
    
        if (!isset($input['userId'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }
    
        $userId = (int) $input['userId'];
        $voiceChat = $input['voiceChat'] ?? null;
        $role = $input['roleLookingFor'] ?? null;
        $rank = $input['rankLookingFor'] ?? null;
        $description = $input['description'] ?? null;

        if ($voiceChat == "true") {
            $voiceChat = 1;
        } else {
            $voiceChat = 0;
        }
    
        // Validate Token for User
        if (!$this->validateToken($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }

        $user = $this->user->getUserById($userId);
        $oldTime = $user['user_requestIsLooking'];

        $setStatus = $this->user->userIsLookingForGame($userId);

        $getPlayerFinderPost = $this->playerFinder->getPlayerFinderPost($userId);

        if ($getPlayerFinderPost) {
            $deletePlayerFinderPost = $this->playerFinder->deletePlayerFinderPost($getPlayerFinderPost['pf_id']);

            if (!$deletePlayerFinderPost) {
                echo json_encode(['success' => false, 'error' => 'Failed to delete existing Player Finder post']);
                return;
            }
        }

        $addPlayerFinderPost = $this->playerFinder->addPlayerFinderPost(
            $role,
            $rank,
            $description,
            $voiceChat,
            $user['user_game'],
            $userId
        );

        if ($addPlayerFinderPost) {
            echo json_encode(['success' => true, 'message' => 'Player Finder post added successfully', 'oldTime' => $oldTime]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to add Player Finder post']);
        }
    }

    public function getPlayerFinderPostsPhone()
    {
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
    
        $userId = (int) $_POST['userId'];
    
        // Validate Token for User
        if (!$this->validateToken($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }

        $playerFinderPosts = $this->playerFinder->getAllPlayerFinderPost();

        if ($playerFinderPosts) {
            echo json_encode(['success' => true, 'posts' => $playerFinderPosts]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No Player Finder posts found']);
        }
    }

    public function deletePlayerFinderPostPhone()
    {
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
    
        $userId = (int) $_POST['userId'];
        $postId = $_POST['postId'] ?? null;
    
        // Validate Token for User
        if (!$this->validateToken($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }

        $getPlayerFinderPost = $this->playerFinder->getPlayerFinderPostById($postId);

        if (!$getPlayerFinderPost) {
            echo json_encode(['success' => false, 'error' => 'No Player Finder post found']);
            return;
        }

        if ($getPlayerFinderPost['user_id'] != $userId) {
            echo json_encode(['success' => false, 'error' => 'You cannot delete this post']);
            return;
        }
    
        $deletePlayerFinderPost = $this->playerFinder->deletePlayerFinderPost($postId);
    
        if ($deletePlayerFinderPost) {
            echo json_encode(['success' => true, 'message' => 'Player Finder post deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete Player Finder post']);
        }
    }

    public function playWithThemPhone() 
    {
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
    
        $userId = (int) $_POST['userId'];
        $postId = $_POST['postId'] ?? null;
    
        // Validate Token for User
        if (!$this->validateToken($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }

        $getPlayerFinderPost = $this->playerFinder->getPlayerFinderPostById($postId);

        if (!$getPlayerFinderPost) {
            echo json_encode(['success' => false, 'error' => 'No Player Finder post found']);
            return;
        }

        if ($getPlayerFinderPost['user_id'] == $userId) {
            echo json_encode(['success' => false, 'error' => 'You cannot play with yourself']);
            return;
        }

        $interested = json_decode($getPlayerFinderPost['pf_peopleInterest'], true);
        if (!is_array($interested)) $interested = [];

        $alreadyInterested = false;
        foreach ($interested as $entry) {
            if ($entry['userId'] == $userId) {
                $alreadyInterested = true;
                break;
            }
        }

        if (!$alreadyInterested) {
            $interested[] = ['userId' => $userId, 'seen' => false];
        }

        $playWithThem = $this->playerFinder->updatePeopleInterest($postId, $interested);
    
        if ($playWithThem) {
            // Check if they are friends, if yes add isFriend yes, to redirect them to chat on front end
            $friendRequest = $this->friendrequest->getFriendStatus($userId, $getPlayerFinderPost['user_id']);

            if ($friendRequest === "accepted") {
                echo json_encode([
                    'success' => true,
                    'message' => 'Redirecting to friend chat',
                    'isFriend' => true,
                    'friendId' => $getPlayerFinderPost['user_id']
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => 'They will know you want to play with them',
                    'isFriend' => false
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to play with them']);
        }
    }
    
    public function getInterestedPeoplePhone()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
    
        $token = $matches[1];

        $userId = $_POST['userId'] ?? null;
    
        if (!isset($userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }
    
        // Validate Token for User
        if (!$this->validateToken($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }

        $getPlayerFinderPost = $this->playerFinder->getPlayerFinderPost($userId);

        if (!$getPlayerFinderPost) {
            echo json_encode(['success' => false, 'error' => 'No Player Finder post found']);
            return;
        }

        $interested = json_decode($getPlayerFinderPost['pf_peopleInterest'], true);
        if (!is_array($interested)) $interested = [];

        $unseenUsers = array_filter($interested, fn($entry) => !$entry['seen']);
        $unseenIds = array_map(fn($entry) => $entry['userId'], $unseenUsers);

        if (empty($unseenIds)) {
            echo json_encode(['success' => true, 'interestedUsers' => false]);
            return;
        }

        $users = $this->user->getUsersByIds($unseenIds, $userId);

        $notifications = [];
        foreach ($users as $user) {
            $notifications[] = [
                'fr_id' => $getPlayerFinderPost['pf_id'],
                'userId' => $userId, 
                'friendId' => $user['user_id'],
                'user_username' => $user['user_username'],
                'pf_status' => 'unseen',
            ];
        }

        echo json_encode([
            'success' => true,
            'interestedUsers' => $notifications
        ]);
    }

    public function markInterestAsSeen()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $token = $matches[1];

        $userId = $_POST['userId'] ?? null;
        $postId = $_POST['postId'] ?? null;

        if (!$userId || !$postId || !$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }

        $post = $this->playerFinder->getPlayerFinderPostById($postId);
        if (!$post || $post['user_id'] != $userId) {
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            return;
        }

        $interested = json_decode($post['pf_peopleInterest'], true);
        if (!is_array($interested)) $interested = [];

        foreach ($interested as &$entry) {
            if (!$entry['seen']) {
                $entry['seen'] = true;
            }
        }

        $update = $this->playerFinder->updatePeopleInterest($postId, $interested);
        echo json_encode(['success' => $update]);
    }

    public function editPlayerPost()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
    
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
    
        $token = $matches[1];
        $userId = $_POST['userId'] ?? null;
    
        if (!isset($userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }
    
        $postId = $_POST['postId'] ?? null;
        $description = $_POST['description'] ?? null;
        $role = $_POST['role'] ?? null;
        $rank = $_POST['rank'] ?? null;
    
        // Validate Token for User
        if (!$this->validateTokenWebsite($token, $userId)) {
            echo json_encode(['success' => false, 'error' => 'Invalid token']);
            return;
        }

        $getPlayerFinderPost = $this->playerFinder->getPlayerFinderPostById($postId);

        if (!$getPlayerFinderPost) {
            echo json_encode(['success' => false, 'error' => 'No Player Finder post found']);
            return;
        }

        if ($getPlayerFinderPost['user_id'] != $userId) {
            echo json_encode(['success' => false, 'error' => 'You cannot update this post']);
            return;
        }
    
        $updateDescPlayerFinder = $this->playerFinder->editPlayerPost($postId, $role, $rank, $description);
    
        if ($updateDescPlayerFinder) {
            echo json_encode(['success' => true, 'message' => 'Description updated successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update description']);
        }
    }
}
