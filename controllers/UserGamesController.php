<?php

declare(strict_types=1);

namespace controllers;

use models\UserGames;
use models\Games;
use models\User;
use models\FriendRequest;
use models\GoogleUser;
use models\UserLookingForGames;
use enums\GameSlug;
use services\RoutingService;
use traits\SecurityController;
use traits\Translatable;
use traits\InputValidation;
use traits\PageRenderer;

// Handles signup, profile management, and Riot account linking for a user's game profile,
// generic over $gameSlug/$gameId instead of one controller per game.
class UserGamesController
{
    use SecurityController;
    use Translatable;
    use InputValidation;
    use PageRenderer;

    private UserGames $userGames;
    private Games $games;
    private RoutingService $routingService;
    private FriendRequest $friendrequest;
    private User $user;
    private GoogleUser $googleUser;
    private UserLookingForGames $userlookingforgames;
    private mixed $userId;
    private mixed $gameId;
    private mixed $main1;
    private mixed $main2;
    private mixed $main3;
    private mixed $rank;
    private mixed $role;
    private mixed $server;
    private mixed $account;
    private mixed $statusChampion;

    public function __construct()
    {
        $this->userGames = new UserGames();
        $this->games = new Games();
        $this->routingService = new RoutingService();
        $this->user = new User();
        $this->friendrequest = new FriendRequest();
        $this->googleUser = new GoogleUser();
        $this->userlookingforgames = new UserLookingForGames();
    }

    public function getGoogleUserModel(): GoogleUser
    {
        return $this->googleUser;
    }

    // Thin per-game wrappers below: the router (index.php) calls action methods with zero
    // arguments, so each game still needs its own route entry - these just forward to the
    // real generic method with the right slug baked in.
    public function pageLeagueUser(): void
    {
        $this->pageGameUser(GameSlug::LEAGUE_OF_LEGENDS->value);
    }

    public function pageValorantUser(): void
    {
        $this->pageGameUser(GameSlug::VALORANT->value);
    }

    public function pageUpdateLeague(): void
    {
        $this->pageUpdateGame(GameSlug::LEAGUE_OF_LEGENDS->value);
    }

    public function pageUpdateValorant(): void
    {
        $this->pageUpdateGame(GameSlug::VALORANT->value);
    }

    public function pageUpdateLeagueAccount(): void
    {
        $this->pageUpdateGameAccount(GameSlug::LEAGUE_OF_LEGENDS->value);
    }

    // Serves the game signup form, or whichever page is actually reachable for this session
    // (basic info, or the looking-for page if the game profile already exists).
    public function pageGameUser(string $gameSlug): void
    {
        $this->initializeLanguage();

        $destination = $this->routingService->routeUser(['step' => 'gameSignup'], gameSlug: $gameSlug);

        if (!empty($destination)) {
            $user = isset($_SESSION['username']) ? $this->user->getUserByUsername($_SESSION['username']) : null;
            $googleUser = isset($_SESSION['email']) ? $this->googleUser->getGoogleUserByEmail($_SESSION['email']) : null;
            $this->dispatch($destination, ['user' => $user, 'googleUser' => $googleUser ?: null]);
            return;
        }

        $user = $this->user->getUserByUsername($_SESSION['username']);

        $config = $this->getGameSignupFormConfig($gameSlug);

        $this->renderPage(
            layout: 'views/layoutSignup.phtml',
            template: $config['template'],
            current_url: $config['url'],
            page_title: $config['pageTitle'],
            picture: 'ursg-preview-small',
            data: [
                'user' => $user,
                'gameSlug' => $gameSlug,
                'ranks' => $config['ranks'],
                'roles' => $config['roles'],
                'servers' => $config['servers'],
                'formAction' => $config['formAction'],
                'mainLabel' => $config['mainLabel'],
                'defaultMains' => $config['defaultMains'],
                'mainImagePath' => $config['mainImagePath'],
                'pickerJs' => $config['pickerJs'],
                'ranksJs' => $config['ranksJs'],
                'rolesJs' => $config['rolesJs'],
                'idSuffix' => $config['idSuffix'],
                'selectedRolesId' => $config['selectedRolesId'],
                'bottomImage' => $config['bottomImage'],
                'bottomImageAlt' => $config['bottomImageAlt'],
            ],
        );
    }

    public function pageUpdateGame(string $gameSlug): void
    {
        if (
            !$this->isConnectGoogle() ||
            !$this->isConnectWebsite() ||
            !$this->routingService->hasGameAccount($gameSlug) ||
            !$this->isConnectLf()
        ) {
            header("Location: /");
            return;
        }

        $this->initializeLanguage();

        $user = $this->user->getUserByUsername($_SESSION['username']);
        $gameId = $this->games->getIdBySlug($gameSlug);
        $userGame = $this->userGames->getUserGameByUserIdAndGameId($user['user_id'], $gameId);

        $config = $this->getGameUpdateConfig($gameSlug);

        $main1 = !empty($userGame['ug_main1']) ? $userGame['ug_main1'] : $config['defaultMains'][0];
        $main2 = !empty($userGame['ug_main2']) ? $userGame['ug_main2'] : $config['defaultMains'][1];
        $main3 = !empty($userGame['ug_main3']) ? $userGame['ug_main3'] : $config['defaultMains'][2];

        $this->renderPage(
            layout: 'views/layoutSwiping.phtml',
            template: $config['template'],
            current_url: $config['url'],
            page_title: $config['pageTitle'],
            picture: 'ursg-preview-small',
            data: [
                'user' => $user,
                'userGame' => $userGame,
                'gameSlug' => $gameSlug,
                'main1' => $main1,
                'main2' => $main2,
                'main3' => $main3,
                'ranks' => $config['ranks'],
                'roles' => $config['roles'],
                'servers' => $config['servers'],
                'formAction' => $config['formAction'],
                'mainLabel' => $config['mainLabel'],
                'mainImagePath' => $config['mainImagePath'],
                'rankImagePath' => $config['rankImagePath'],
                'roleImagePath' => $config['roleImagePath'],
                'pickerJs' => $config['pickerJs'],
                'ranksJs' => $config['ranksJs'],
                'rolesJs' => $config['rolesJs'],
                'idSuffix' => $config['idSuffix'],
                'selectedRolesId' => $config['selectedRolesId'],
                'bottomImage' => $config['bottomImage'],
                'bottomImageAlt' => $config['bottomImageAlt'],
            ],
        );
    }

    // Everything about how a game's picker/rank/role/server UI looks - shared by the signup
    // form and the update form, which otherwise only differ in template/URL/title. Adding a
    // game means adding one entry here, not a new template file.
    private function getGameUiConfig(string $gameSlug): array
    {
        $servers = ["Europe West", "North America", "Europe Nordic" => "Europe Nordic & East", "Brazil", "Latin America North", "Latin America South", "Oceania", "Russia", "Turkey", "Japan", "Korea"];

        return match ($gameSlug) {
            GameSlug::LEAGUE_OF_LEGENDS->value => [
                'defaultMains' => ['KaiSa', 'Ezreal', 'Jhin'],
                'ranks' => ["Unranked", "Iron", "Bronze", "Silver", "Gold", "Platinum", "Emerald", "Diamond", "Master", "Grand Master", "Challenger"],
                'roles' => ["Support", "AD Carry", "Mid laner", "Jungler", "Top laner", "Fill"],
                'servers' => $servers,
                'mainLabel' => 'champion',
                'mainImagePath' => fn(string $name) => "public/images/champions/{$name}.png",
                'rankImagePath' => fn(string $rank) => "public/images/ranks/{$rank}.png",
                'roleImagePath' => fn(string $role) => 'public/images/roles/' . strtolower(str_replace(' ', '', $role)) . '.png',
                'pickerJs' => 'public/js/champions-picker.js',
                'ranksJs' => 'public/js/ranks.js',
                'rolesJs' => 'public/js/roles.js',
                'idSuffix' => 'lol',
                'selectedRolesId' => 'selected-roles',
                'bottomImage' => 'public/images/Ahri.png',
                'bottomImageAlt' => 'Ahri from League of legends',
            ],
            GameSlug::VALORANT->value => [
                'defaultMains' => ['Viper', 'Omen', 'Sova'],
                'ranks' => ["Unranked", "Iron", "Bronze", "Silver", "Gold", "Platinum", "Diamond", "Ascendant", "Immortal", "Radiant"],
                'roles' => ["Controller", "Duelist", "Initiator", "Sentinel", "Fill"],
                'servers' => $servers,
                'mainLabel' => 'agent',
                'mainImagePath' => fn(string $name) => "public/images/valorant_champions/{$name}_icon.webp",
                'rankImagePath' => fn(string $rank) => "public/images/valorant_ranks/{$rank}.png",
                'roleImagePath' => fn(string $role) => "public/images/valorant_roles/{$role}.webp",
                'pickerJs' => 'public/js/champions-picker-valorant.js',
                'ranksJs' => 'public/js/ranks-valorant.js',
                'rolesJs' => 'public/js/roles-valorant.js',
                'idSuffix' => 'valorant',
                'selectedRolesId' => 'selected-roles-valorant',
                'bottomImage' => 'public/images/jett.png',
                'bottomImageAlt' => 'Jett from Valorant',
            ],
            default => throw new \InvalidArgumentException("Unknown game slug: {$gameSlug}"),
        };
    }

    private function getGameSignupFormConfig(string $gameSlug): array
    {
        return $this->getGameUiConfig($gameSlug) + match ($gameSlug) {
            GameSlug::LEAGUE_OF_LEGENDS->value => [
                'template' => 'views/signup/game_user',
                'url' => 'https://ur-sg.com/leagueuser',
                'pageTitle' => 'URSG - Sign up',
                'formAction' => '/createleagueuser',
            ],
            GameSlug::VALORANT->value => [
                'template' => 'views/signup/game_user',
                'url' => 'https://ur-sg.com/valorantuser',
                'pageTitle' => 'URSG - Sign up',
                'formAction' => '/createvalorantuser',
            ],
            default => throw new \InvalidArgumentException("Unknown game slug: {$gameSlug}"),
        };
    }

    private function getGameUpdateConfig(string $gameSlug): array
    {
        return $this->getGameUiConfig($gameSlug) + match ($gameSlug) {
            GameSlug::LEAGUE_OF_LEGENDS->value => [
                'template' => 'views/swiping/update_game',
                'url' => 'https://ur-sg.com/updateLeaguePage',
                'pageTitle' => 'URSG - Profile',
            ],
            GameSlug::VALORANT->value => [
                'template' => 'views/swiping/update_game',
                'url' => 'https://ur-sg.com/updateValorantPage',
                'pageTitle' => 'URSG - Update Valorant',
            ],
            default => throw new \InvalidArgumentException("Unknown game slug: {$gameSlug}"),
        } + ['formAction' => '/updateGame'];
    }

    public function pageUpdateGameAccount(string $gameSlug): void
    {
        if (
            !$this->isConnectGoogle() ||
            !$this->isConnectWebsite() ||
            !$this->routingService->hasGameAccount($gameSlug) ||
            !$this->isConnectLf()
        ) {
            header("Location: /");
            return;
        }

        $config = $this->getGameAccountConfig($gameSlug);

        if ($config === null) {
            header("Location: /");
            return;
        }

        $this->initializeLanguage();

        $user = $this->user->getUserByUsername($_SESSION['username']);
        $allUsers = $this->user->getAllUsers();
        $friendRequest = $this->friendrequest->getFriendRequest($_SESSION['userId']);
        $gameId = $this->games->getIdBySlug($gameSlug);
        $userGame = $this->userGames->getUserGameByUserIdAndGameId($user['user_id'], $gameId);

        $this->renderPage(
            layout: 'views/layoutSwiping.phtml',
            template: $config['template'],
            current_url: $config['url'],
            page_title: $config['pageTitle'],
            picture: 'ursg-preview-small',
            data: [
                'user' => $user,
                'allUsers' => $allUsers,
                'friendRequest' => $friendRequest,
                'userGame' => $userGame,
                'servers' => $config['servers'],
            ],
        );
    }

    // Only League of Legends has an account-binding template and a working Riot verification
    // flow today (see the TODOs on bindAccount/verifyGameAccount above). No entry here means
    // no page to render, so the caller redirects instead of failing on a missing template.
    private function getGameAccountConfig(string $gameSlug): ?array
    {
        return match ($gameSlug) {
            GameSlug::LEAGUE_OF_LEGENDS->value => [
                'template' => 'views/swiping/update_account',
                'url' => 'https://ur-sg.com/updateLeagueAccount',
                'pageTitle' => 'URSG - Bind league account',
                'servers' => ["Europe West", "North America", "Europe Nordic" => "Europe Nordic & East", "Brazil", "Latin America North", "Latin America South", "Oceania", "Russia", "Turkey", "Japan", "Korea"],
            ],
            default => null,
        };
    }

    public function createGameUser(): void
    {
        if (!isset($_POST['submit']) || !isset($_POST['userId'])) {
            throw new \InvalidArgumentException("Invalid request: missing 'submit' or 'userId' in POST data.");
        }

        if ($returnTo = $this->validateAndSetFormData($_POST)) {
            header("location: $returnTo");
            return;
        }

        $existingProfile = $this->userGames->getUserGameByUserIdAndGameId($this->getUserId(), $this->getGameId());

        if ($existingProfile) {
            header("location: /updateGame?gameSlug=" . $_POST['gameSlug'] . "&message=Profile already exists");
            return;
        }

        $createdId = $this->userGames->createUserGame(
            $this->getUserId(),
            $this->getGameId(),
            $this->getMain1(),
            $this->getMain2(),
            $this->getMain3(),
            $this->getRank(),
            $this->getRole(),
            $this->getServer(),
            (int) $this->getStatusChampion()
        );

        if (!$createdId) {
            header("location: /?gameSlug=" . $_POST['gameSlug'] . "&message=Failed to create profile");
            return;
        }

        $gameSlug = $_POST['gameSlug'];

        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params(7 * 24 * 60 * 60);
            session_start();
        }

        $this->setGameSessionId($gameSlug, (int) $createdId);

        // A looking-for row already exists (rare: user_games got wiped/recreated but their
        // looking-for answers survived) - send them to finish that instead of starting over.
        if ($this->userlookingforgames->getLookingForGameByUserIdAndGameId($this->getUserId(), $this->getGameId())) {
            header("location:/updateLookingForGamePage");
            return;
        }

        $user = $this->user->getUserById($this->getUserId());

        if ($user['google_createdWithRSO'] === 1) {
            $this->bindRiotAccountAfterRsoSignup($gameSlug);
            return;
        }

        header("location:" . $this->routingService->lookingForDestination($gameSlug)['current_url']);
    }

    private function setGameSessionId(string $gameSlug, int $userGamesId): void
    {
        match ($gameSlug) {
            GameSlug::LEAGUE_OF_LEGENDS->value => $_SESSION['lol_id'] = $userGamesId,
            GameSlug::VALORANT->value => $_SESSION['valorant_id'] = $userGamesId,
            default => null,
        };
    }

    // A user who signed up via Riot Sign-On (not Google) has already proven Riot account
    // ownership, so this enriches their profile with real Riot data right after creation
    // instead of asking them to bind it separately.
    //
    // Only League has a public rank/level API today (lol/summoner-v4, lol/league-v4) - the
    // account-verification endpoint itself (riot/account/v1) is game-agnostic and already
    // used for the initial RSO sign-in, but there is no public Valorant equivalent of
    // summoner-v4/league-v4 to fetch rank/level from (see the Riot API research: Valorant
    // stats access requires Riot's manual partner approval). Valorant profiles created this
    // way are session-bound and correctly game_id-tagged, just without rank/level enrichment.
    private function bindRiotAccountAfterRsoSignup(string $gameSlug): void
    {
        $lookingForUrl = $this->routingService->lookingForDestination($gameSlug)['current_url'];

        if ($gameSlug !== GameSlug::LEAGUE_OF_LEGENDS->value) {
            header("location:{$lookingForUrl}");
            return;
        }

        require_once 'keys.php';

        $selectedRegionValue = $this->resolveLolRegion($this->getServer());
        $puuid = $_SESSION['google_id'];

        $summonerProfile = $this->getSummonerProfile($puuid, $selectedRegionValue, $apiKey);
        $summonerRankedStats = $this->getSummonerRankedStats($puuid, $selectedRegionValue, $apiKey);

        if (!isset($summonerRankedStats)) {
            header("location:{$lookingForUrl}");
            return;
        }

        $rankAndTier = $this->determineRankAndTier($summonerRankedStats);
        $fullAccountName = $_SESSION['full_name'] . '#' . $_SESSION['tagLine'];

        $bound = $this->userGames->updateSyncData(
            $this->getGameId(),
            $this->getUserId(),
            $_SESSION['full_name'],
            'Removed',
            $puuid,
            $summonerProfile['summonerLevel'] ?? null,
            $rankAndTier,
            $summonerProfile['profileIconId'] ?? null,
            $fullAccountName,
        );

        $message = $bound ? 'Binded ' . ucfirst($gameSlug) . ' account' : 'Couldnt bind account';
        header("location:{$lookingForUrl}?message=" . urlencode($message));
    }

    // Maps a display server name to the shard used by lol/-namespaced Riot endpoints
    // (summoner-v4, league-v4). League-only: the generic riot/account/v1 endpoints (used by
    // getSummonerByNameAndTag/getTagLine) route by continent instead and don't need this.
    // TODO: this and the Riot API methods below duplicate RiotController - both should consume
    // a shared services\RiotApiClient instead (agreed, extraction is a follow-up step).
    private function getLolRegionMap(): array
    {
        return [
            "Europe West" => "euw1",
            "North America" => "na1",
            "Europe Nordic" => "eun1",
            "Europe Nordic & East" => "eun1",
            "Europe Nordic &amp;" => "eun1",
            "Brazil" => "br1",
            "Latin America North" => "la1",
            "Latin America South" => "la2",
            "Oceania" => "oc1",
            "Russia" => "ru1",
            "Turkey" => "tr1",
            "Japan" => "jp1",
            "Korea" => "kr",
        ];
    }

    private function resolveLolRegion(?string $server): ?string
    {
        return $this->getLolRegionMap()[$server] ?? null;
    }

    // Validates the POST payload, populates the getters/setters below from it, and returns
    // a redirect path if anything is wrong (null means the request is good to proceed).
    // For $isMobile, errors are echoed as JSON directly (matching the *Phone() endpoints'
    // contract) and the return value is just a non-null signal for the caller to stop - not
    // a redirect path. For web, the return value is the redirect path to send the browser to.
    private function validateAndSetFormData(array $post, bool $isMobile = false): ?string
    {
        $userId = $this->validateInput($post['userId'] ?? '');
        $this->setUserId($userId);

        if ($isMobile) {
            $token = $this->getBearerTokenOrJsonError();
            if (!$token) {
                return 'handled';
            }
            if (!$this->validateToken($token, $userId)) {
                echo json_encode(['success' => false, 'message' => 'Invalid token']);
                return 'handled';
            }
        } elseif (!$this->validateTokenWebsite($_SESSION['masterTokenWebsite'], $userId)) {
            return "/userProfile?message=Token not valid";
        }

        $gameId = $this->games->getIdBySlug($post['gameSlug'] ?? '');

        if ($gameId === null) {
            if ($isMobile) {
                echo json_encode(['message' => 'Unknown game']);
                return 'handled';
            }
            return "/?message=Unknown game";
        }

        $this->setGameId($gameId);

        $fields = [
            'main1' => 'setMain1',
            'main2' => 'setMain2',
            'main3' => 'setMain3',
            'rank' => 'setRank',
            'role' => 'setRole',
            'server' => 'setServer',
        ];

        foreach ($fields as $postKey => $setter) {
            $this->$setter($this->validateInput($post[$postKey] ?? ''));
        }

        $statusChampion = isset($post['skipSelection']) ? $this->validateInput($post['skipSelection']) : '0';
        $this->setStatusChampion($statusChampion);

        $requiredInputs = $statusChampion === '1'
            ? [$this->getRank(), $this->getRole(), $this->getServer()]
            : [$this->getMain1(), $this->getMain2(), $this->getMain3(), $this->getRank(), $this->getRole(), $this->getServer()];

        if ($this->areAnyEmpty(...$requiredInputs)) {
            if ($isMobile) {
                echo json_encode(['message' => 'Fill all fields']);
                return 'handled';
            }
            return "/signup?message=Inputs%20cannot%20be%20empty";
        }

        return null;
    }

    public function createGameUserPhone(): void
    {
        if (!isset($_POST['gameData'])) {
            echo json_encode(['message' => 'Error']);
            return;
        }

        $data = json_decode($_POST['gameData'], true) ?? [];

        if ($this->validateAndSetFormData($data, isMobile: true)) {
            // Error response already echoed by validateAndSetFormData.
            return;
        }

        $existingProfile = $this->userGames->getUserGameByUserIdAndGameId($this->getUserId(), $this->getGameId());

        if ($existingProfile) {
            echo json_encode(['message' => 'User already exist']);
            return;
        }

        $createdId = $this->userGames->createUserGame(
            $this->getUserId(),
            $this->getGameId(),
            $this->getMain1(),
            $this->getMain2(),
            $this->getMain3(),
            $this->getRank(),
            $this->getRole(),
            $this->getServer(),
            (int) $this->getStatusChampion()
        );

        if (!$createdId) {
            echo json_encode(['message' => 'Error']);
            return;
        }

        $userGame = $this->userGames->getUserGameById((int) $createdId);

        echo json_encode([
            'sessionId' => session_id(),
            'user' => [
                'userGamesId' => $userGame['ug_id'],
                'main1' => $userGame['ug_main1'],
                'main2' => $userGame['ug_main2'],
                'main3' => $userGame['ug_main3'],
                'rank' => $userGame['ug_rank'],
                'role' => $userGame['ug_role'],
                'server' => $userGame['ug_server'],
            ],
            'message' => 'Success',
        ]);
    }

    public function updateGame(): void
    {
        if (!isset($_POST['submit'])) {
            return;
        }

        if ($returnTo = $this->validateAndSetFormData($_POST)) {
            header("location: $returnTo");
            return;
        }

        if ($this->getStatusChampion() === '0') {
            if ($this->getMain1() === $this->getMain2() || $this->getMain1() === $this->getMain3() || $this->getMain2() === $this->getMain3()) {
                header("location:/userProfile?message=Each champion must be unique");
                return;
            }
        }

        $updated = $this->userGames->updateUserGameData(
            $this->getUserId(),
            $this->getGameId(),
            $this->getMain1(),
            $this->getMain2(),
            $this->getMain3(),
            $this->getRank(),
            $this->getRole(),
            $this->getServer(),
            (int) $this->getStatusChampion()
        );

        if ($updated) {
            header("location:/userProfile?message=Updated successfully");
            return;
        }

        header("location:/userProfile?message=Could not update");
    }

    // Only League has a public API endpoint for this today (account-v1 by-riot-id is
    // game-agnostic, but the verification-code flow below is currently only wired for League).
    public function bindAccount(): void
    {
        if (!isset($_POST['userData'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid data received']);
            return;
        }

        $data = json_decode($_POST['userData']);

        $this->setUserId($this->validateInput($data->userId));

        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }

        if (!$this->validateToken($token, $this->getUserId())) {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            return;
        }

        $this->setAccount(str_replace(' ', '', $this->validateInput($data->account)));
        $this->setServer($data->server);

        $parts = explode('#', $this->getAccount());
        $username = $parts[0];
        $tagLine = $parts[1] ?? '';

        require_once 'keys.php';

        $summoner = $this->getSummonerByNameAndTag($username, $tagLine, $apiKey);

        if (!$summoner) {
            echo json_encode(['success' => false, 'message' => "Couldn't find a LoL account"]);
            return;
        }

        $puudId = $summoner['puuid'];
        $summonerName = $summoner['gameName'];
        $verificationCode = bin2hex(random_bytes(5));

        $gameId = $this->games->getIdBySlug(GameSlug::LEAGUE_OF_LEGENDS->value);
        $bound = $this->userGames->addAccount($gameId, $this->getServer(), $this->getAccount(), $verificationCode, $this->getUserId());

        if (!$bound) {
            echo json_encode(['success' => false, 'message' => 'Failed to save account']);
            return;
        }

        echo json_encode([
            'status' => 'Success',
            'message' => 'Verification code generated',
            'verification_code' => $verificationCode,
            'puuId' => $puudId,
            'summonerName' => $summonerName,
            'tagLine' => $tagLine,
        ]);
    }

    // Checks the summoner's live profile icon against the value the user was asked to set
    // during bindAccount, as proof of account ownership. League-only (see bindAccount).
    public function verifyGameAccount(): void
    {
        if (!isset($_POST['param'])) {
            echo json_encode(['status' => 'failure', 'message' => 'Invalid request method']);
            return;
        }

        $this->setUserId($this->validateInput($_SESSION['userId']));
        $puudId = $_SESSION['$puudId'];
        $server = $_SESSION['server'];
        $username = $_SESSION['summoner_name'];

        require_once 'keys.php';

        $summonerProfile = $this->getSummonerProfile($puudId, $server, $apiKey);

        if (!$summonerProfile || $summonerProfile['profileIconId'] !== 7) {
            echo json_encode(['status' => 'failure', 'message' => 'Verification failed. Picture does not match.']);
            return;
        }

        $summonerRankedStats = $this->getSummonerRankedStats($puudId, $server, $apiKey);
        $rankAndTier = $summonerRankedStats ? $this->determineRankAndTier($summonerRankedStats) : 'Unranked';

        $gameId = $this->games->getIdBySlug(GameSlug::LEAGUE_OF_LEGENDS->value);

        $this->userGames->updateSyncData(
            $gameId,
            $this->getUserId(),
            $username,
            'Removed',
            $puudId,
            $summonerProfile['summonerLevel'] ?? null,
            $rankAndTier,
            $summonerProfile['profileIconId'],
        );

        echo json_encode(['status' => 'success', 'message' => 'Account verified successfully!']);
    }

    public function verifyGameAccountPhone(): void
    {
        if (!isset($_POST['userData'])) {
            echo json_encode(['status' => 'failure', 'message' => 'Invalid request method']);
            return;
        }

        $data = json_decode($_POST['userData']);
        $userId = $this->validateInput($data->userId);

        $token = $this->getBearerTokenOrJsonError();
        if (!$token) {
            return;
        }

        if (!$this->validateToken($token, $userId)) {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            return;
        }

        $this->setUserId($userId);
        $puudId = $data->puuId;
        $tagLine = $data->tagLine;
        $username = $data->summonerName;
        $selectedRegionValue = $this->resolveLolRegion($data->server);

        require_once 'keys.php';

        $summonerProfile = $this->getSummonerProfile($puudId, $selectedRegionValue, $apiKey);

        if (!$summonerProfile || $summonerProfile['profileIconId'] !== 7) {
            echo json_encode(['status' => 'failure', 'message' => 'Verification failed. Picture does not match.']);
            return;
        }

        $summonerRankedStats = $this->getSummonerRankedStats($puudId, $selectedRegionValue, $apiKey);
        $rankAndTier = $summonerRankedStats ? $this->determineRankAndTier($summonerRankedStats) : 'Unranked';

        $gameId = $this->games->getIdBySlug(GameSlug::LEAGUE_OF_LEGENDS->value);

        $this->userGames->updateSyncData(
            $gameId,
            $this->getUserId(),
            $username,
            'Removed',
            $puudId,
            $summonerProfile['summonerLevel'] ?? null,
            $rankAndTier,
            $summonerProfile['profileIconId'],
            $username . $tagLine,
        );

        echo json_encode([
            'status' => 'success',
            'message' => 'Account verified successfully!',
            'summonerName' => $username,
            'summonerId' => 'Removed',
            'puuId' => $puudId,
            'summonerLevel' => $summonerProfile['summonerLevel'] ?? null,
            'rankAndTier' => $rankAndTier,
            'profileIconId' => $summonerProfile['profileIconId'],
        ]);
    }

    // Cron-style batch refresh for every verified League profile. League-only: no public
    // rank/level API exists for Valorant today (see the Riot API research).
    public function refreshRiotData(): void
    {
        require_once 'keys.php';

        $token = $_GET['token'] ?? null;

        if (!isset($token) || $token !== $tokenRefresh) {
            header("Location: /?message=Unauthorized");
            return;
        }

        $gameId = $this->games->getIdBySlug(GameSlug::LEAGUE_OF_LEGENDS->value);
        $verifiedProfiles = array_slice($this->userGames->getVerifiedUserGames($gameId), 0, 100);

        foreach ($verifiedProfiles as $profile) {
            $selectedRegionValue = $this->resolveLolRegion($profile['ug_server']);

            $summonerProfile = $this->getSummonerProfile($profile['ug_sPuuid'], $selectedRegionValue, $apiKey);

            if (!$summonerProfile) {
                continue;
            }

            $summonerRankedStats = $this->getSummonerRankedStats($profile['ug_sPuuid'], $selectedRegionValue, $apiKey);
            $rankAndTier = $summonerRankedStats ? $this->determineRankAndTier($summonerRankedStats) : 'Unranked';

            $tagLineData = $this->getTagLine($profile['ug_sPuuid'], $profile['ug_server'], $apiKey);

            $this->userGames->updateSyncData(
                $gameId,
                $profile['user_id'],
                $profile['ug_sUsername'],
                'Removed',
                $profile['ug_sPuuid'],
                $summonerProfile['summonerLevel'] ?? null,
                $rankAndTier,
                $summonerProfile['profileIconId'] ?? null,
                $profile['ug_sUsername'] . '#' . ($tagLineData['tagLine'] ?? ''),
            );
        }
    }

    public function unbindGameAccount(): void
    {
        if (!isset($_POST['userId'])) {
            return;
        }

        $userId = $this->validateInput($_POST['userId']);

        if (!$this->validateTokenWebsite($_SESSION['masterTokenWebsite'], $userId)) {
            header("location:/userProfile?message=Token not valid");
            return;
        }

        $this->setUserId($userId);

        $gameId = $this->games->getIdBySlug($_POST['gameSlug'] ?? GameSlug::LEAGUE_OF_LEGENDS->value);
        $unbound = $this->userGames->unbindAccount($this->getUserId(), $gameId);

        if ($unbound) {
            header("location:/userProfile?message=Unbinded successfully");
            return;
        }

        header("location:/userProfile?message=Could not unbind");
    }

    // Looks up a Riot account by Riot ID (name + tag) via the Riot API.
    public function getSummonerByNameAndTag(string $summonerName, string $tagLine, string $apiKey): ?array
    {
        $region = "americas";
        $url = "https://{$region}.api.riotgames.com/riot/account/v1/accounts/by-riot-id/" . rawurlencode($summonerName) . "/{$tagLine}?api_key={$apiKey}";
        $context = stream_context_create([
            'http' => [
                'ignore_errors' => true
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        // Check for HTTP errors
        if (isset($http_response_header)) {
            preg_match('{HTTP/\S*\s(\d{3})}', $http_response_header[0], $match);
            $statusCode = $match[1] ?? 0;

            if ($statusCode != '200') {
                error_log("Riot API error");
                return null;
            }
        }

        return json_decode($response, true);
    }

    // Fetches a summoner's profile (level, icon, etc.) from the Riot API by PUUID.
    public function getSummonerProfile(string $puudId, string $server, string $apiKey): ?array
    {
        $url = "https://" . strtolower($server) . ".api.riotgames.com/lol/summoner/v4/summoners/by-puuid/{$puudId}?api_key={$apiKey}";
        $context = stream_context_create([
            'http' => [
                'ignore_errors' => true
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        // Check for HTTP errors
        if (isset($http_response_header)) {
            preg_match('{HTTP/\S*\s(\d{3})}', $http_response_header[0], $match);
            $statusCode = $match[1] ?? 0;

            if ($statusCode != '200') {
                error_log("Riot API error");
                return null;
            }
        }

        return json_decode($response, true);
    }

    // Fetches a summoner's ranked queue entries from the Riot API by PUUID.
    public function getSummonerRankedStats(string $puudId, string $server, string $apiKey): ?array
    {
        $url = "https://" . strtolower($server) . ".api.riotgames.com/lol/league/v4/entries/by-puuid/{$puudId}?api_key={$apiKey}";
        $context = stream_context_create([
            'http' => [
                'ignore_errors' => true
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        // Check for HTTP errors
        if (isset($http_response_header)) {
            preg_match('{HTTP/\S*\s(\d{3})}', $http_response_header[0], $match);
            $statusCode = $match[1] ?? 0;

            if ($statusCode != '200') {
                error_log("Riot API error");
                return null;
            }
        }

        return json_decode($response, true);
    }

    // Resolves a PUUID to its current Riot ID (game name + tag line) via the Riot API.
    public function getTagLine(string $puuid, string $server, string $apiKey): ?array
    {
        $regionMap = [
            "Europe West" => "europe",
            "North America" => "americas",
            "Europe Nordic" => "europe",
            "Brazil" => "americas",
            "Latin America North" => "americas",
            "Latin America South" => "americas",
            "Oceania" => "americas",
            "Russia" => "europe",
            "Turkey" => "europe",
            "Japan" => "asia",
            "Korea" => "asia",
        ];

        // Get the correct region value
        $selectedRegionValue = $regionMap[$server] ?? null;

        if (!$selectedRegionValue) {
            throw new \Exception("Invalid server: $server");
        }

        $url = "https://{$selectedRegionValue}.api.riotgames.com/riot/account/v1/accounts/by-puuid/{$puuid}?api_key={$apiKey}";

        $response = file_get_contents($url);

        if ($response === false) {
            throw new \Exception("Failed to fetch data from Riot API for PUUID: $puuid");
        }

        return json_decode($response, true);
    }

    // Picks the higher of a player's solo queue and flex queue ranks, defaulting to Unranked.
    public function determineRankAndTier(array $summonerRankedStats): string
    {
        $rankAndTier = 'Unranked';
        $soloQueueRank = null;
        $flexQueueRank = null;

        // Define tier and division order for comparison
        $tiers = [
            'IRON' => 1,
            'BRONZE' => 2,
            'SILVER' => 3,
            'GOLD' => 4,
            'PLATINUM' => 5,
            'EMERALD' => 6,
            'DIAMOND' => 7,
            'MASTER' => 8,
            'GRANDMASTER' => 9,
            'CHALLENGER' => 10
        ];

        $divisions = [
            'IV' => 1,
            'III' => 2,
            'II' => 3,
            'I' => 4
        ];

        // Loop through the ranked stats array to find the desired queue types
        foreach ($summonerRankedStats as $rankedStats) {
            if ($rankedStats['queueType'] === 'RANKED_SOLO_5x5') {
                $soloQueueRank = [
                    'tier' => $rankedStats['tier'],
                    'rank' => $rankedStats['rank']
                ];
            } elseif ($rankedStats['queueType'] === 'RANKED_FLEX_SR') {
                $flexQueueRank = [
                    'tier' => $rankedStats['tier'],
                    'rank' => $rankedStats['rank']
                ];
            }
        }

        // Compare solo queue and flex queue ranks to determine the higher one
        if ($soloQueueRank && $flexQueueRank) {
            if ($tiers[$soloQueueRank['tier']] > $tiers[$flexQueueRank['tier']]) {
                $rankAndTier = $soloQueueRank['tier'] . ' ' . $soloQueueRank['rank'];
            } elseif ($tiers[$soloQueueRank['tier']] < $tiers[$flexQueueRank['tier']]) {
                $rankAndTier = $flexQueueRank['tier'] . ' ' . $flexQueueRank['rank'];
            } else {
                // If tiers are the same, compare divisions
                if ($divisions[$soloQueueRank['rank']] > $divisions[$flexQueueRank['rank']]) {
                    $rankAndTier = $soloQueueRank['tier'] . ' ' . $soloQueueRank['rank'];
                } else {
                    $rankAndTier = $flexQueueRank['tier'] . ' ' . $flexQueueRank['rank'];
                }
            }
        } elseif ($soloQueueRank) {
            $rankAndTier = $soloQueueRank['tier'] . ' ' . $soloQueueRank['rank'];
        } elseif ($flexQueueRank) {
            $rankAndTier = $flexQueueRank['tier'] . ' ' . $flexQueueRank['rank'];
        }

        return $rankAndTier;
    }

    // Getters/setters hold the mobile/JSON request data used by the *Phone() methods above.
    // Typed as mixed: values come in from $_POST/$_SESSION/json_decode()'d objects, whose
    // actual type (int vs string) varies by call site for the same field.
    public function getUserId(): mixed
    {
        return $this->userId;
    }

    public function setUserId(mixed $userId): void
    {
        $this->userId = $userId;
    }

    public function getGameId(): mixed
    {
        return $this->gameId;
    }

    public function setGameId(mixed $gameId): void
    {
        $this->gameId = $gameId;
    }

    public function getMain1(): mixed
    {
        return $this->main1;
    }

    public function setMain1(mixed $main1): void
    {
        $this->main1 = $main1;
    }

    public function getMain2(): mixed
    {
        return $this->main2;
    }

    public function setMain2(mixed $main2): void
    {
        $this->main2 = $main2;
    }

    public function getMain3(): mixed
    {
        return $this->main3;
    }

    public function setMain3(mixed $main3): void
    {
        $this->main3 = $main3;
    }

    public function getRank(): mixed
    {
        return $this->rank;
    }

    public function setRank(mixed $rank): void
    {
        $this->rank = $rank;
    }

    public function getRole(): mixed
    {
        return $this->role;
    }

    public function setRole(mixed $role): void
    {
        $this->role = $role;
    }

    public function getServer(): mixed
    {
        return $this->server;
    }

    public function setServer(mixed $server): void
    {
        $this->server = $server;
    }

    public function getStatusChampion(): mixed
    {
        return $this->statusChampion;
    }

    public function setStatusChampion(mixed $statusChampion): void
    {
        $this->statusChampion = $statusChampion;
    }

    public function getAccount(): mixed
    {
        return $this->account;
    }

    public function setAccount(mixed $account): void
    {
        $this->account = $account;
    }
}
