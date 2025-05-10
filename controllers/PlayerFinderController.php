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
            $lol_ranks = ["Unranked", "Iron", "Bronze", "Silver", "Gold", "Platinum", "Emerald", "Diamond", "Master", "Grand Master", "Challenger", "Any"];
            $lol_roles = ["Support", "AD Carry", "Mid laner", "Jungler", "Top laner", "Fill", "Any"];
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
            $playerFinderPost = $this->playerFinder->getPlayerFinderPost($user['user_id']);

            if ($playerFinderPost && !empty($playerFinderPost['pf_peopleInterest'])) {
                $interested = json_decode($playerFinderPost['pf_peopleInterest'], true);

                if (!empty($interested)) {
                    $interestedData = $this->user->getUsersByIds($interested, $_SESSION['userId']);
                } else {
                    $interestedData = [];
                }
            } else {
                $interestedData = [];
            }


            if ($user['user_game'] === 'League of Legends') {
                $availableRanks = $lol_ranks;
                $availableRoles = $lol_roles;
            } elseif ($user['user_game'] === 'Valorant') {
                $availableRanks = $valorant_ranks;
                $availableRoles = $valorant_roles;
            }
            $playerFinderAll = $this->playerFinder->getAllPlayerFinderPost();
            $current_url = "https://ur-sg.com/playerFinder";
            $template = "views/swiping/playerfinder";
            $page_title = "URSG - Player Finder";
            $picture = "ursg-preview-small";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            $playerFinderAll = $this->playerFinder->getAllPlayerFinderPost();
            $lol_ranks = ["Unranked", "Iron", "Bronze", "Silver", "Gold", "Platinum", "Emerald", "Diamond", "Master", "Grand Master", "Challenger", "Any"];
            $lol_roles = ["Support", "AD Carry", "Mid laner", "Jungler", "Top laner", "Fill", "Any"];
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

        $interested = json_decode($getPlayerFinderPost['pf_peopleInterest'], true);

        if (!is_array($interested)) {
            $interested = [];
        }

        if (!in_array($userId, $interested)) {
            $interested[] = $userId;
        }

        $playWithThem = $this->playerFinder->updatePeopleInterest($postId, $interested);
    
        if ($playWithThem) {
            echo json_encode(['success' => true, 'message' => 'They will know you want to play with them']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to play with them']);
        }
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

}
