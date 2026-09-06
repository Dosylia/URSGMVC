<?php

namespace controllers;

use models\UserLookingForGames;
use models\Games;
use models\User;
use models\FriendRequest;
use models\GoogleUser;
use enums\GameSlug;
use services\RoutingService;
use traits\SecurityController;
use traits\Translatable;
use traits\PageRenderer;

class UserLookingForController
{
    use SecurityController;
    use Translatable;
    use PageRenderer;

    private UserLookingForGames $userlookingforgames;
    private Games $games;
    private User $user;
    private RoutingService $routingService;
    private FriendRequest $friendrequest;
    private GoogleUser $googleUser;
    private $userId;
    private $lfGender;
    private $lfKindOfGamer;
    private $lfGame;
    private $lfFilteredServer;
    private $main1;
    private $main2;
    private $main3;
    private $rank;
    private $role;

    public function __construct()
    {
        $this -> userlookingforgames = new UserLookingForGames();
        $this -> games = new Games();
        $this -> user = new User();
        $this -> routingService = new RoutingService();
        $this -> friendrequest = new FriendRequest();
        $this -> googleUser = new GoogleUser();
    }

    public function getGoogleUserModel(): GoogleUser
    {
        return $this->googleUser;
    }

    public function pageLookingFor()
    {
        $this->pageLookingForGame(GameSlug::LEAGUE_OF_LEGENDS->value);
    }

    public function pageLookingForValorant()
    {
        $this->pageLookingForGame(GameSlug::VALORANT->value);
    }

    private function pageLookingForGame(string $gameSlug): void
    {
        $this->initializeLanguage();

        $destination = $this->routingService->routeUser(['step' => 'lookingFor'], gameSlug: $gameSlug);
        $user = $this->user->getUserByUsername($_SESSION['username']);

        if (!empty($destination)) {
            $this->dispatch($destination, ['user' => $user]);
            return;
        }

        $config = $this->getGameLookingForConfig($gameSlug);

        $this->renderPage(
            layout: 'views/layoutSignup.phtml',
            template: 'views/signup/looking_for_game',
            current_url: $config['currentUrl'],
            page_title: 'URSG - Looking for',
            picture: 'ursg-preview-small',
            title: 'What are you looking for?',
            data: array_merge(['user' => $user], $config),
        );
    }

    public function pageUpdateLookingFor()
    {
        $this->requireUserSessionOrRedirect($redirectUrl = '/');
        $this->initializeLanguage();
        $user = $this-> user -> getUserByUsername($_SESSION['username']);
        $gameSlug = $user['game_slug'] ?? GameSlug::LEAGUE_OF_LEGENDS->value;
        $gameId = $this->games->getIdBySlug($gameSlug);
        $lfGame = $gameId ? $this->userlookingforgames->getLookingForGameByUserIdAndGameId($user['user_id'], $gameId) : false;

        $config = $this->getGameLookingForConfig($gameSlug);

        $mainDefault1 = !empty($lfGame['lfg_main1']) ? $lfGame['lfg_main1'] : $config['defaultMain1'];
        $mainDefault2 = !empty($lfGame['lfg_main2']) ? $lfGame['lfg_main2'] : $config['defaultMain2'];
        $mainDefault3 = !empty($lfGame['lfg_main3']) ? $lfGame['lfg_main3'] : $config['defaultMain3'];

        $this->renderPage(
            layout: 'views/layoutSwiping.phtml',
            template: 'views/swiping/update_lookingFor',
            current_url: 'https://ur-sg.com/updateLookingForPage',
            page_title: 'URSG - Profile',
            picture: 'ursg-preview-small',
            data: array_merge([
                'user' => $user,
                'lfGame' => $lfGame ?: null,
                'main1' => $mainDefault1,
                'main2' => $mainDefault2,
                'main3' => $mainDefault3,
                'genders' => ["Male", "Female", "Non binary", "Male and Female", "All", "Trans"],
                'kindofgamers' => ["Chill" => "Chill / Normal games", "Competition" => "Competition / Ranked", "Competition and Chill" => "Competition/Ranked and chill"],
                'filteredServers' => [
                    "Europe West", "North America", "Europe Nordic & East", "Brazil",
                    "Latin America North", "Latin America South", "Oceania",
                    "Russia", "Turkey", "Japan", "Korea"
                ],
            ], $config),
        );
    }

    public function pageUpdateLookingForGame()
    {
        if (
            $this->isConnectGoogle() &&
            $this->isConnectWebsite() &&
            ($this->isConnectLeague() || $this->isConnectValorant()) &&
            !$this->isConnectLf()
        )
        {
            $this->initializeLanguage();
            $user = $this-> user -> getUserByUsername($_SESSION['username']);
            $gameSlug = $user['game_slug'] ?? GameSlug::LEAGUE_OF_LEGENDS->value;
            $gameId = $this->games->getIdBySlug($gameSlug);
            $lfGame = $gameId ? $this->userlookingforgames->getLookingForGameByUserIdAndGameId($user['user_id'], $gameId) : false;

            $config = $this->getGameLookingForConfig($gameSlug);

            $mainDefault1 = !empty($lfGame['lfg_main1']) ? $lfGame['lfg_main1'] : $config['defaultMain1'];
            $mainDefault2 = !empty($lfGame['lfg_main2']) ? $lfGame['lfg_main2'] : $config['defaultMain2'];
            $mainDefault3 = !empty($lfGame['lfg_main3']) ? $lfGame['lfg_main3'] : $config['defaultMain3'];

            $this->renderPage(
                layout: 'views/layoutSignup.phtml',
                template: 'views/swiping/update_lookingForGame',
                current_url: 'https://ur-sg.com/updateLookingForGamePage',
                page_title: 'URSG - Profile',
                picture: 'ursg-preview-small',
                title: 'What are you looking for?',
                data: array_merge([
                    'user' => $user,
                    'lfGame' => $lfGame ?: null,
                    'main1' => $mainDefault1,
                    'main2' => $mainDefault2,
                    'main3' => $mainDefault3,
                    'genders' => ["Male", "Female", "Non binary", "Male and Female", "All", "Trans"],
                    'kindofgamers' => ["Chill" => "Chill / Normal games", "Competition" => "Competition / Ranked", "Competition and Chill" => "Competition/Ranked and chill"],
                ], $config),
            );
        }
        else
        {
            header("Location: /");
            return;
        }
    }

    public function createLookingFor()
    {
        if (!isset($_POST['submit'])) {
            return;
        }

        $gameSlug = ($_POST['game'] ?? '') === 'Valorant' ? GameSlug::VALORANT->value : GameSlug::LEAGUE_OF_LEGENDS->value;
        $idSuffix = $gameSlug === GameSlug::VALORANT->value ? 'valorant' : 'lol';
        $gameId = $this->games->getIdBySlug($gameSlug);

        $userId = $this->validateInput($_POST["userId"]);
        $this->setUserId($userId);

        if (!$this->validateTokenWebsite($_SESSION['masterTokenWebsite'], $userId)) {
            header("location:/userProfile?message=Token not valid");
            return;
        }

        $lfGender = $this->validateInput($_POST["gender"]);
        $this->setLfGender($lfGender);
        $lfKindOfGamer = $this->validateInput($_POST["kindofgamer"]);
        $this->setLfKindOfGamer($lfKindOfGamer);
        $main1 = $this->validateInput($_POST["main1"]);
        $this->setMain1($main1);
        $main2 = $this->validateInput($_POST["main2"]);
        $this->setMain2($main2);
        $main3 = $this->validateInput($_POST["main3"]);
        $this->setMain3($main3);
        $rank = $this->validateInput($_POST["rank_{$idSuffix}"]);
        $this->setRank($rank);
        $role = $this->validateInput($_POST["role_{$idSuffix}"]);
        $this->setRole($role);
        $statusChampion = 0;

        if (isset($_POST["skipSelection"])) {
            $statusChampion = $this->validateInput($_POST["skipSelection"]);
        }

        if ($statusChampion == "1") {
            if (empty($rank) || empty($role))
            {
                header("location:/signup?message=Inputs cannot be empty");
                return;
            }
        } else {
            if (($main1 === $main2 || $main1 === $main3 || $main2 === $main3)) {
                header("location:/signup?message=Each pick must be unique");
                return;
            }
            if (empty($main1) || empty($main2) || empty($main3) || empty($rank) || empty($role))
            {
                header("location:/signup?message=Inputs cannot be empty");
                return;
            }
        }

        $existingLookingForGame = $this->userlookingforgames->getLookingForGameByUserIdAndGameId($this->getUserId(), $gameId);

        if ($existingLookingForGame) {
            $updateLookingFor = $this->userlookingforgames->updateLookingForGame(
                $this->getUserId(),
                $gameId,
                $this->getLfGender(),
                $this->getLfKindOfGamer(),
                $this->getRank(),
                $this->getRole(),
                (int) $statusChampion,
                $this->getMain1(),
                $this->getMain2(),
                $this->getMain3());

            if ($updateLookingFor)
            {
                if (!isset($_SESSION['lf_id']))
                {
                    $_SESSION['lf_id'] = $existingLookingForGame['lfg_id'];
                }
                header("location:/userProfile?message=Updated successfully");
                return;
            }
            else
            {
                header("location:/userProfile?message=Could not update");
                return;
            }
        }

        $createLookingForId = $this->userlookingforgames->createLookingForGame(
            $this->getUserId(),
            $gameId,
            $this->getLfGender(),
            $this->getLfKindOfGamer(),
            $this->getRank(),
            $this->getRole(),
            (int) $statusChampion,
            $this->getMain1(),
            $this->getMain2(),
            $this->getMain3());

        if ($createLookingForId)
        {
            if (session_status() == PHP_SESSION_NONE)
            {
                $lifetime = 7 * 24 * 60 * 60;
                session_set_cookie_params($lifetime);
                session_start();
            }

            $_SESSION['lf_id'] = $createLookingForId;

            header("location:/swiping?createdUser=true");
            return;
        } else {
            header("location:/signup?message=Could not create looking for user");
            return;
        }
    }

    // TODO: mobile app is not currently maintained and will be rebuilt - kept working against
    // the unified model so the route doesn't fatal, but not a priority to polish further.
    public function createLookingForUserPhone()
    {
        if (!isset($_POST['lookingforData'])) {
            echo json_encode(['message' => $this->_('messages.error')]);
            return;
        }

        $data = json_decode($_POST['lookingforData']);

        $gameSlug = ($data->game ?? '') === 'Valorant' ? GameSlug::VALORANT->value : GameSlug::LEAGUE_OF_LEGENDS->value;
        $gameId = $this->games->getIdBySlug($gameSlug);

        $userId = $this->validateInput($data->userId);
        $this->setUserId($userId);

        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }

        if (!$this->validateToken($token, $userId)) {
            echo json_encode(['success' => false, 'message' => $this->_('messages.invalid_token')]);
            return;
        }

        $lfGender = $this->validateInput($data->gender);
        $this->setLfGender($lfGender);
        $lfKindOfGamer = $this->validateInput($data->kindOfGamer);
        $this->setLfKindOfGamer($lfKindOfGamer);
        $main1 = $this->validateInput($data->main1);
        $this->setMain1($main1);
        $main2 = $this->validateInput($data->main2);
        $this->setMain2($main2);
        $main3 = $this->validateInput($data->main3);
        $this->setMain3($main3);
        $rank = $this->validateInput($data->rank);
        $this->setRank($rank);
        $role = $this->validateInput($data->role);
        $this->setRole($role);
        $statusChampion = $this->validateInput($data->skipSelection);

        if ($statusChampion == 1) {
            if (empty($rank) || empty($role))
            {
                echo json_encode(['message' => $this->_('messages.fill_all_fields')]);
                return;
            }
        } else {
            if (empty($main1) || empty($main2) || empty($main3) || empty($rank) || empty($role))
            {
                echo json_encode(['message' => $this->_('messages.fill_all_fields')]);
                return;
            }
        }

        if ($this->userlookingforgames->getLookingForGameByUserIdAndGameId($this->getUserId(), $gameId)) {
            echo json_encode(['message' => $this->_('messages.user_already_exist')]);
            return;
        }

        $createLookingForId = $this->userlookingforgames->createLookingForGame(
            $this->getUserId(),
            $gameId,
            $this->getLfGender(),
            $this->getLfKindOfGamer(),
            $this->getRank(),
            $this->getRole(),
            (int) $statusChampion,
            $this->getMain1(),
            $this->getMain2(),
            $this->getMain3());

        if ($createLookingForId)
        {
            $lfGameRow = $this->userlookingforgames->getLookingForGameByUserIdAndGameId($this->getUserId(), $gameId);

            $lookingforUserData = array(
                'lfId' => $lfGameRow['lfg_id'],
                'lfGender' => $lfGameRow['lfg_gender'],
                'lfKingOfGamer' => $lfGameRow['lfg_kindofgamer'],
                'lfGame' => $data->game,
                'main1Lf' => $lfGameRow['lfg_main1'],
                'main2Lf' => $lfGameRow['lfg_main2'],
                'main3Lf' => $lfGameRow['lfg_main3'],
                'rankLf' => $lfGameRow['lfg_rank'],
                'roleLf' => $lfGameRow['lfg_role']
            );

            echo json_encode(['sessionId' => session_id(), 'user' => $lookingforUserData, 'message' => $this->_('messages.success')]);
            return;
        }

        echo json_encode(['message' => $this->_('messages.error')]);
        return;
    }

    public function updateLookingFor()
    {
        if (!isset($_POST['submit'])) {
            return;
        }

        $gameSlug = ($_POST['game'] ?? '') === 'Valorant' ? GameSlug::VALORANT->value : GameSlug::LEAGUE_OF_LEGENDS->value;
        $idSuffix = $gameSlug === GameSlug::VALORANT->value ? 'valorant' : 'lol';
        $gameId = $this->games->getIdBySlug($gameSlug);

        $userId = $this->validateInput($_POST["userId"]);

        if (!$this->validateTokenWebsite($_SESSION['masterTokenWebsite'], $userId)) {
            header("location:/userProfile?message=Token not valid");
            return;
        }

        $this->setUserId($userId);
        $lfGender = $this->validateInput($_POST["gender"]);
        $this->setLfGender($lfGender);
        $lfKindOfGamer = $this->validateInput($_POST["kindofgamer"]);
        $this->setLfKindOfGamer($lfKindOfGamer);
        $main1 = $this->validateInput($_POST["main1"]);
        $this->setMain1($main1);
        $main2 = $this->validateInput($_POST["main2"]);
        $this->setMain2($main2);
        $main3 = $this->validateInput($_POST["main3"]);
        $this->setMain3($main3);
        $rank = $this->validateInput($_POST["rank_{$idSuffix}"]);
        $this->setRank($rank);
        $role = $this->validateInput($_POST["role_{$idSuffix}"]);
        $this->setRole($role);
        $filteredServer = $this->validateInputJSON($_POST["filteredServers"]);

        $validRegions = [
            "Europe West", "North America", "Europe Nordic & East", "Brazil",
            "Latin America North", "Latin America South", "Oceania",
            "Russia", "Turkey", "Japan", "Korea"
        ];

        if (!empty($filteredServer)) {
            foreach ($filteredServer as $server) {
                if (!in_array($server, $validRegions)) {
                    header("Location: /userProfile?message=Filtered region not valid");
                    return;
                }
            }
        }

        $filteredServerJson = json_encode($filteredServer);
        $this->setLfFilteredServer($filteredServerJson);

        $user = $this->user->getUserById($_SESSION['userId']);

        if ($user['user_id'] != $this->getUserId())
        {
            header("location:/userProfile?message=Could not update");
            return;
        }

        $statusChampion = 0;
        if (isset($_POST["skipSelection"])) {
            $statusChampion = $this->validateInput($_POST["skipSelection"]);
        }

        $existingLookingForGame = $this->userlookingforgames->getLookingForGameByUserIdAndGameId($this->getUserId(), $gameId);

        if ($statusChampion == "1") {
            if (empty($rank) || empty($role))
            {
                if ($existingLookingForGame && $existingLookingForGame['lfg_role']) {
                    header("location:/signup?message=Inputs cannot be empty");
                    return;
                } else {
                    header("location:/updateLookingForGamePage?message=Inputs cannot be empty");
                    return;
                }
            }
        } else {
            if (empty($main1) || empty($main2) || empty($main3) || empty($rank) || empty($role))
            {
                if ($existingLookingForGame && $existingLookingForGame['lfg_role']) {
                    header("location:/signup?message=Inputs cannot be empty");
                    return;
                } else {
                    header("location:/updateLookingForGamePage?message=Inputs cannot be empty");
                    return;
                }
            }
        }

        if ($existingLookingForGame) {
            $updateLookingFor = $this->userlookingforgames->updateLookingForGame(
                $this->getUserId(),
                $gameId,
                $this->getLfGender(),
                $this->getLfKindOfGamer(),
                $this->getRank(),
                $this->getRole(),
                (int) $statusChampion,
                $this->getMain1(),
                $this->getMain2(),
                $this->getMain3(),
                $this->getLfFilteredServer());
        } else {
            $updateLookingFor = $this->userlookingforgames->createLookingForGame(
                $this->getUserId(),
                $gameId,
                $this->getLfGender(),
                $this->getLfKindOfGamer(),
                $this->getRank(),
                $this->getRole(),
                (int) $statusChampion,
                $this->getMain1(),
                $this->getMain2(),
                $this->getMain3());
        }

        if ($updateLookingFor)
        {
            if (!isset($_SESSION['lf_id']))
            {
                $lfGame = $this->userlookingforgames->getLookingForGameByUserIdAndGameId($this->getUserId(), $gameId);
                $_SESSION['lf_id'] = $lfGame['lfg_id'];
            }
            header("location:/userProfile?message=Updated successfully");
            return;
        }
        else
        {
            header("location:/userProfile?message=Could not update");
            return;
        }
    }

    // Config shared by the signup-flow ("looking for" onboarding step) and the profile-update
    // pages: everything that legitimately differs between games (asset paths, form field id
    // suffix, rank/role option lists, gender options) lives here instead of duplicated templates.
    private function getGameLookingForConfig(string $gameSlug): array
    {
        return match ($gameSlug) {
            GameSlug::LEAGUE_OF_LEGENDS->value => [
                'idSuffix' => 'lol',
                'currentUrl' => 'https://ur-sg.com/lookingforuserlol',
                'gameDisplayName' => 'League of Legends',
                'genderOptions' => ['Male', 'Female', 'Trans', 'Non binary', 'Male and Female', 'All'],
                'ranks' => ["Unranked", "Iron", "Bronze", "Silver", "Gold", "Platinum", "Emerald", "Diamond", "Master", "Grand Master", "Challenger", "Any"],
                'roles' => ["Support", "AD Carry", "Mid laner", "Jungler", "Top laner", "Fill", "Any"],
                'defaultMain1' => 'KaiSa',
                'defaultMain2' => 'Ezreal',
                'defaultMain3' => 'Jhin',
                'mainImagePath' => fn(string $name) => "public/images/champions/{$name}.png",
                'rankImagePath' => fn(string $rank) => "public/images/ranks/{$rank}.png",
                'roleImagePath' => fn(string $role) => "public/images/roles/" . strtolower(str_replace(' ', '', $role)) . ".png",
                'pickerJs' => 'public/js/champions-picker.js',
                'ranksJs' => 'public/js/ranks.js',
                'rolesJs' => 'public/js/roles.js',
                'gameInfoJs' => 'public/js/leagueoflegendsinfo.js',
                'bottomImage' => 'public/images/ahri.png',
                'bottomImageAlt' => 'Ahri from League of legends',
            ],
            GameSlug::VALORANT->value => [
                'idSuffix' => 'valorant',
                'currentUrl' => 'https://ur-sg.com/lookingforuservalorant',
                'gameDisplayName' => 'Valorant',
                'genderOptions' => ['Male', 'Female', 'Non Binary', 'Male and Female', 'All'],
                'ranks' => ["Unranked", "Iron", "Bronze", "Silver", "Gold", "Platinum", "Diamond", "Ascendant", "Immortal", "Radiant", "Any"],
                'roles' => ["Controller", "Duelist", "Initiator", "Sentinel", "Fill", "Any"],
                'defaultMain1' => 'Viper',
                'defaultMain2' => 'Omen',
                'defaultMain3' => 'Sova',
                'mainImagePath' => fn(string $name) => "public/images/valorant_champions/{$name}_icon.webp",
                'rankImagePath' => fn(string $rank) => "public/images/valorant_ranks/{$rank}.png",
                'roleImagePath' => fn(string $role) => "public/images/valorant_roles/{$role}.webp",
                'pickerJs' => 'public/js/champions-picker-valorant.js',
                'ranksJs' => 'public/js/ranks-valorant.js',
                'rolesJs' => 'public/js/roles-valorant.js',
                'gameInfoJs' => 'public/js/valorantinfo.js',
                'bottomImage' => 'public/images/jett.png',
                'bottomImageAlt' => 'Jett from Valorant',
            ],
            default => throw new \InvalidArgumentException("Unknown game slug: {$gameSlug}"),
        };
    }

    public function validateInput($input)
    {
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }

    public function validateInputJSON($input)
    {
        $input = trim($input);

        // Try decoding if it looks like JSON
        if (is_string($input) && (strpos($input, '[') === 0 || strpos($input, '{') === 0)) {
            $decodedInput = json_decode($input, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decodedInput;
            }
        }

        // If it's a raw string, attempt to decode HTML entities and retry JSON
        $decodedEntities = html_entity_decode($input, ENT_QUOTES, 'UTF-8');
        $tryJsonAgain = json_decode($decodedEntities, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $tryJsonAgain;
        }

        return [];
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function getLfGender()
    {
        return $this->lfGender;
    }

    public function setLfGender($lfGender)
    {
        $this->lfGender = $lfGender;
    }

    public function getLfKindOfGamer()
    {
        return $this->lfKindOfGamer;
    }

    public function setLfKindOfGamer($lfKindOfGamer)
    {
        $this->lfKindOfGamer = $lfKindOfGamer;
    }

    public function getLfFilteredServer()
    {
        return $this->lfFilteredServer;
    }

    public function setLfFilteredServer($lfFilteredServer)
    {
        $this->lfFilteredServer = $lfFilteredServer;
    }

    public function getMain1()
    {
        return $this->main1;
    }

    public function setMain1($main1)
    {
        $this->main1 = $main1;
    }

    public function getMain2()
    {
        return $this->main2;
    }

    public function setMain2($main2)
    {
        $this->main2 = $main2;
    }

    public function getMain3()
    {
        return $this->main3;
    }

    public function setMain3($main3)
    {
        $this->main3 = $main3;
    }

    public function getRank()
    {
        return $this->rank;
    }

    public function setRank($rank)
    {
        $this->rank = $rank;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setRole($role)
    {
        $this->role = $role;
    }
}
