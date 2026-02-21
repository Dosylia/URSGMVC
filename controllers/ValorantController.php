<?php

namespace controllers;

use models\Valorant;
use models\User;
use models\FriendRequest;
use models\GoogleUser;
use traits\SecurityController;
use traits\Translatable;

class ValorantController
{
    use SecurityController;
    use Translatable;

    private Valorant $valorant;
    private FriendRequest $friendrequest;
    private User $user;
    private GoogleUser $googleUser;
    private $userId;
    private $valorantMain1;
    private $valorantMain2;
    private $valorantMain3;
    private $valorantRank;
    private $valorantRole;
    private $valorantServer;
    private $valorantAccount;

    
    public function __construct()
    {
        $this -> valorant = new Valorant();
        $this -> user = new User();
        $this -> friendrequest = new FriendRequest();
        $this -> googleUser = new GoogleUser();
    }

    public function getGoogleUserModel(): GoogleUser
    {
        return $this->googleUser;
    }

    public function pageValorantUser()
    {
        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectValorant()) {
            // Code block 1: User is connected via Google, Website and has League data, need looking for
            if (isset($_GET['user_id'])) {
                if ($_GET['user_id'] != $_SESSION['userId']) {
                    header("Location: /?message=This is not your account");
                    return;
                }
            }
            $this->initializeLanguage();
            $valorantUser = $this->valorant->getValorantUserByUsername($_SESSION['valorant_account']);
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
            $current_url = "https://ur-sg.com/lookingforuservalorant";
            $template = "views/signup/lookingforlol";
            $title = "What are you looking for?";
            $page_title = "URSG - Looking for";
            $picture = "ursg-preview-small";
            require "views/layoutSignup.phtml";
        } elseif ($this->isConnectGoogle() && $this->isConnectWebsite() && !$this->isConnectValorant()){
            // Code block 2: User is connected via Google, Website but not connected to Valorant LATER ADD VALORANT CHECK
            if (isset($_GET['user_id'])) {
                if ($_GET['user_id'] != $_SESSION['userId']) {
                    header("Location: /?message=This is not your account");
                    return;
                }
            }
            $this->initializeLanguage();
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
            $current_url = "https://ur-sg.com/valoranteuser";
            $template = "views/signup/valorantuser";
            $title = "More about you";
            $page_title = "URSG - Sign up";
            $picture = "ursg-preview-small";
            require "views/layoutSignup.phtml";
        } elseif ($this->isConnectGoogle() && !$this->isConnectWebsite()) {
            // Code block 3: User is connected via Google but doesn't have a username
            $this->initializeLanguage();
            $current_url = "https://ur-sg.com/basicinfo";
            $template = "views/signup/basicinfo";
            $title = "Sign up";
            $page_title = "URSG - Sign";
            $picture = "ursg-preview-small";
            require "views/layoutSignup.phtml";
        } else {
            // Code block 4: Redirect to / if none of the above conditions are met
            header("Location: /");
            return;
        }
    }

    public function pageUpdateValorant()
    {
        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectValorant() && $this->isConnectLf())
        {

          // Get important datas
          $this->initializeLanguage();
          $user = $this-> user -> getUserByUsername($_SESSION['username']);
          $valorantUser = $this->valorant->getValorantUserByValorantId($_SESSION['valorant_id']);

          $defaultChampions = [
            'valorant_main1' => 'Viper',
            'valorant_main2' => 'Omen',
            'valorant_main3' => 'Sova'
        ];

        // Check if the values are empty, and use the fallback if needed
        $valorantMain1 = !empty($valorantUser['valorant_main1']) ? $valorantUser['valorant_main1'] : $defaultChampions['valorant_main1'];
        $valorantMain2 = !empty($valorantUser['valorant_main2']) ? $valorantUser['valorant_main2'] : $defaultChampions['valorant_main2'];
        $valorantMain3 = !empty($valorantUser['valorant_main3']) ? $valorantUser['valorant_main3'] : $defaultChampions['valorant_main3'];

            
            $valorant_ranks = ["Unranked", "Iron", "Bronze", "Silver", "Gold", "Platinum", "Diamond", "Ascendant", "Immortal", "Radiant"];
            $valorant_roles = ["Controller", "Duelist", "Initiator", "Sentinel", "Fill"];
            $valorant_servers = ["Europe West", "North America", "Europe Nordic" => "Europe Nordic & East", "Brazil", "Latin America North", "Latin America South", "Oceania", "Russia",  "Turkey", "Japan", "Korea"];

            $current_url = "https://ur-sg.com/updateValorantPage";
            $template = "views/swiping/update_valorant";
            $page_title = "URSG - Update Valorant";
            $picture = "ursg-preview-small";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            header("Location: /");
            return;
        }
    }

    public function pageUpdateValorantAccount()
    {
        if ($this->isConnectGoogle() && $this->isConnectWebsite() && $this->isConnectValorant() && $this->isConnectLf())
        {

            // Get important datas
            $this->initializeLanguage();
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
            $valorantUser = $this->valorant->getValorantUserByValorantId($_SESSION['valorant_id']);

            $defaultChampions = [
                'valorant_main1' => 'Viper',
                'valorant_main2' => 'Omen',
                'valorant_main3' => 'Sova'
            ];
    
            // Check if the values are empty, and use the fallback if needed
            $valorantMain1 = !empty($valorantUser['valorant_main1']) ? $valorantUser['valorant_main1'] : $defaultChampions['valorant_main1'];
            $valorantMain2 = !empty($valorantUser['valorant_main2']) ? $valorantUser['valorant_main2'] : $defaultChampions['valorant_main2'];
            $valorantMain3 = !empty($valorantUser['valorant_main3']) ? $valorantUser['valorant_main3'] : $defaultChampions['valorant_main3'];
            
                
            $valorant_servers = ["Europe West", "North America", "Europe Nordic" => "Europe Nordic & East", "Brazil", "Latin America North", "Latin America South", "Oceania", "Russia",  "Turkey", "Japan", "Korea"];

            $current_url = "https://ur-sg.com/updateValorantAccount";
            $template = "views/swiping/update_valorantAccount";
            $page_title = "URSG - Update Valorant account";
            $picture = "ursg-preview-small";
            require "views/layoutSwiping.phtml";
        } 
        else
        {
            header("Location: /");
            return;
        }
    }

    public function createValorantUser()
    {
        if (isset($_POST['submit'])) 
        {

            $userId = $this->validateInput($_POST["userId"]);
            $this->setUserId($userId);

            if (!$this->validateTokenWebsite($_SESSION['masterTokenWebsite'], $userId)) {
                header("location:/userProfile?message=Token not valid");
                return;
            }


            $valorantMain1 = $this->validateInput($_POST["main1"]);
            $this->setValorantMain1($valorantMain1);
            $valorantMain2 = $this->validateInput($_POST["main2"]);
            $this->setValorantMain2($valorantMain2);
            $valorantMain3 = $this->validateInput($_POST["main3"]);
            $this->setValorantMain3($valorantMain3);
            $valorantRank = $this->validateInput($_POST["rank_valorant"]);
            $this->setValorantRank($valorantRank);
            $valorantRole = $this->validateInput($_POST["role_valorant"]);
            $this->setValorantRole($valorantRole);
            $valorantServer = $this->validateInput($_POST["server"]);
            $this->setValorantServer($valorantServer);
            $statusChampion = $this->validateInput($_POST["skipSelection"]);

            $user = $this->user->getUserById($_SESSION['userId']);


            if ($statusChampion == "1") {
                if ($this->emptyInputSignup($valorantRank) || $this->emptyInputSignup($valorantRole) || $this->emptyInputSignup($valorantServer))
                {
                    header("location:/signup?message=Inputs cannot be empty");
                    return;
                }
    
            } else {
                if (($valorantMain1 === $valorantMain2 || $valorantMain1 === $valorantMain2 || $valorantMain2 === $valorantMain3)) {
                    header("location:/signup?message=Each agents must be unique");
                    return;
                }

                if ($this->emptyInputSignup($valorantMain1) || $this->emptyInputSignup($valorantMain2) || $this->emptyInputSignup($valorantMain3) || $this->emptyInputSignup($valorantRank) || $this->emptyInputSignup($valorantRole) || $this->emptyInputSignup($valorantServer))
                {
                    header("location:/signup?message=Inputs cannot be empty");
                    return;
                }
    
            }

            $testValorantAccount = $this->user->getUserById($this->getUserId());

            if ($testValorantAccount && $testValorantAccount['valorant_id'] !== null) {
                header("location:/signup?message=Valorant user already exists");
                return;
            }

            $createValorantUser = $this->valorant->createValorantUser(
                $this->getUserId(), 
                $this->getValorantMain1(), 
                $this->getValorantMain2(), 
                $this->getValorantMain3(), 
                $this->getValorantRank(), 
                $this->getValorantRole(), 
                $this->getValorantServer(),
                $statusChampion);

            if ($createValorantUser)
            {

                $valorantUser = $this->valorant->getValorantAccountByValorantId($createValorantUser);

                if (session_status() == PHP_SESSION_NONE) 
                {
                    $lifetime = 7 * 24 * 60 * 60;
                    session_set_cookie_params($lifetime);
                    session_start();
                }
                
                    $_SESSION['valorant_id'] = $valorantUser['valorant_id'];

                    if($testValorantAccount['lf_id'] !== NULL)
                    {
                        header("location:/updateLookingForGamePage");
                        return;
                    }

                header("location:/lookingforuservalorant");
                return;
            } else {
                header("location:/signup?message=Could not create Valorant user");
                return;
            }

        }

    }

    public function createValorantUserPhone()
    {
        if (isset($_POST['valorantData'])) 
        {
            $data = json_decode($_POST['valorantData']);
            $userId = $this->validateInput($data->userId);

            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return;
            }

            // Validate Token for User
            if (!$this->validateToken($token, $userId)) {
                echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_token')]);
                return;
            }


            $this->setUserId($userId);
            $valorantMain1 = $this->validateInput($data->main1);
            $this->setValorantMain1($valorantMain1);
            $valorantMain2 = $this->validateInput($data->main2);
            $this->setValorantMain2($valorantMain2);
            $valorantMain3 = $this->validateInput($data->main3);
            $this->setValorantMain3($valorantMain3);
            $valorantRank = $this->validateInput($data->rank);
            $this->setValorantRank($valorantRank);
            $valorantRole = $this->validateInput($data->role);
            $this->setValorantRole($valorantRole);
            $valorantServer = $this->validateInput($data->server);
            $this->setValorantServer($valorantServer);
            $statusChampion = $this->validateInput($data->skipSelection);

            if ($statusChampion == 1) {
                if ($this->emptyInputSignup($valorantRank) || $this->emptyInputSignup($valorantRole) || $this->emptyInputSignup($valorantServer))
                {
                    echo json_encode(['message' => $this->_('messages.fill_all_fields')]);
                    return;  
                }
            } else {
                if ($this->emptyInputSignup($valorantMain1) || $this->emptyInputSignup($valorantMain2) || $this->emptyInputSignup($valorantMain3) || $this->emptyInputSignup($valorantRank) || $this->emptyInputSignup($valorantRole) || $this->emptyInputSignup($valorantServer))
                {
                    echo json_encode(['message' => $this->_('messages.fill_all_fields')]);
                    return;  
                }
            }

            $testValorantAccount = $this->user->getUserById($this->getUserId());

            if ($testValorantAccount && $testValorantAccount['valorant_id'] !== null) {
                echo json_encode(['message' => $this->_('messages.user_already_exist')]);
                return;  
            }

            $createValorantUser = $this->valorant->createValorantUser(
                $this->getUserId(), 
                $this->getValorantMain1(), 
                $this->getValorantMain2(), 
                $this->getValorantMain3(), 
                $this->getValorantRank(), 
                $this->getValorantRole(), 
                $this->getValorantServer(),
                $statusChampion);

            if ($createValorantUser)
            {

                $valorantUser = $this->valorant->getValorantAccountByValorantId($createValorantUser);

                echo json_encode([
                    'sessionId' => session_id(),
                    'user' => [
                        'valorantId' => $valorantUser['valorant_id'],
                        'main1' => $valorantUser['valorant_main1'],
                        'main2' => $valorantUser['valorant_main2'],
                        'main3' => $valorantUser['valorant_main3'],
                        'rank' => $valorantUser['valorant_rank'],
                        'role' => $valorantUser['valorant_role'],
                        'server' => $valorantUser['valorant_server']
                    ],
                    'message' => $this->_('messages.success')
                ]);
                return;
            }

        }
        echo json_encode(['message' => $this->_('messages.error')]);
        return;  
    }


    public function UpdateValorant()
    {
        if (isset($_POST['submit'])) 
        {

            $userId = $this->validateInput($_POST["userId"]);

            if (!$this->validateTokenWebsite($_SESSION['masterTokenWebsite'], $userId)) {
                header("location:/userProfile?message=Token not valid");
                return;
            }

            $this->setUserId($userId);
            $valorantMain1 = $this->validateInput($_POST["main1"]);
            $this->setValorantMain1($valorantMain1);
            $valorantMain2 = $this->validateInput($_POST["main2"]);
            $this->setValorantMain2($valorantMain2);
            $valorantMain3 = $this->validateInput($_POST["main3"]);
            $this->setValorantMain3($valorantMain3);
            $valorantRank = $this->validateInput($_POST["rank_valorant"]);
            $this->setValorantRank($valorantRank);
            $valorantRole = $this->validateInput($_POST["role_valorant"]);
            $this->setValorantRole($valorantRole);
            $valorantServer = $this->validateInput($_POST["server"]);
            $this->setValorantServer($valorantServer);
            $statusChampion = $this->validateInput($_POST["skipSelection"]);

            // $user = $this->user->getUserById($_SESSION['userId']);

            // if ($user['user_id'] != $this->getUserId())
            // {
            //     header("location:/userProfile?message=Not allowed");
            //     return;
            // }
            if ($statusChampion == "0") {
                if ($valorantMain1 === $valorantMain2 || $valorantMain1 === $valorantMain2 || $valorantMain2 === $valorantMain3) {
                    header("location:/userProfile?message=Each agents must be unique");
                    return;
                }
            }

            $updateValorant = $this->valorant->updateValorantData(
                $this->getUserId(), 
                $this->getValorantMain1(), 
                $this->getValorantMain2(), 
                $this->getValorantMain3(), 
                $this->getValorantRank(), 
                $this->getValorantRole(), 
                $this->getValorantServer(),
                $statusChampion);

            if ($updateValorant)
            {
                header("location:/userProfile?message=Updated successfully");
                return;  
            }
            else
            {
                header("location:/userProfile?message=Could not update");
                return;
            }

        }

    }

    public function emptyInputSignup($account) 
    {
        $result;
        if (empty($account))
        {
            $result = true;
        } else {
            $result = false;
        }
        return $result;
    }

    public function validateInput($input) 
    {
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function getValorantMain1()
    {
        return $this->valorantMain1;
    }

    public function setValorantMain1($valorantMain1)
    {
        $this->valorantMain1 = $valorantMain1;
    }

    public function getValorantMain2()
    {
        return $this->valorantMain2;
    }

    public function setValorantMain2($valorantMain2)
    {
        $this->valorantMain2 = $valorantMain2;
    }

    public function getValorantMain3()
    {
        return $this->valorantMain3;
    }

    public function setValorantMain3($valorantMain3)
    {
        $this->valorantMain3 = $valorantMain3;
    }

    public function getValorantRank()
    {
        return $this->valorantRank;
    }

    public function setValorantRank($valorantRank)
    {
        $this->valorantRank = $valorantRank;
    }


    public function getValorantRole()
    {
        return $this->valorantRole;
    }

    public function setValorantRole($valorantRole)
    {
        $this->valorantRole = $valorantRole;
    }

    public function getValorantServer()
    {
        return $this->valorantServer;
    }

    public function setValorantServer($valorantServer)
    {
        $this->valorantServer = $valorantServer;
    }

    public function getValorantAccount()
    {
        return $this->valorantAccount;
    }

    public function setValorantAccount($valorantAccount)
    {
        $this->valorantAccount = $valorantAccount;
    }

}
