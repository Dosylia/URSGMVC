<?php

declare(strict_types=1);

namespace controllers;

use models\UserGames;
use models\User;
use models\FriendRequest;
use models\GoogleUser;
use services\RoutingService;
use traits\SecurityController;
use traits\Translatable;
use traits\InputValidation;
use traits\PageRenderer;

// Handles signup, profile management, and Riot account linking for a user's game profile,
// generic over $gameSlug/$gameId instead of one controller per game.
// TODO: figure out where game_id gets resolved from a slug/route (a small Games model
// wrapping the `games` table, or inline queries here - not decided yet).
class UserGamesController
{
    use SecurityController;
    use Translatable;
    use InputValidation;
    use PageRenderer;

    private UserGames $userGames;
    private RoutingService $routingService;
    private FriendRequest $friendrequest;
    private User $user;
    private GoogleUser $googleUser;
    private mixed $userId;
    private mixed $gameId;
    private mixed $main1;
    private mixed $main2;
    private mixed $main3;
    private mixed $rank;
    private mixed $role;
    private mixed $server;
    private mixed $account;

    public function __construct()
    {
        $this->userGames = new UserGames();
        $this->routingService = new RoutingService();
        $this->user = new User();
        $this->friendrequest = new FriendRequest();
        $this->googleUser = new GoogleUser();
    }

    public function getGoogleUserModel(): GoogleUser
    {
        return $this->googleUser;
    }

    // Serves the game signup form, or whichever page is actually reachable for this session
    // (basic info, or the looking-for page if the game profile already exists).
    public function pageGameUser(string $gameSlug): void
    {
        $this->initializeLanguage();

        $destination = $this->routingService->routeUser(['step' => 'gameSignup'], gameSlug: $gameSlug);

        if (empty($destination)) {
            $destination = $this->routingService->gameSignupDestination($gameSlug);
        }

        $user = $this->user->getUserByUsername($_SESSION['username']);

        $this->dispatch($destination, ['user' => $user]);
    }

    public function pageUpdateGame(string $gameSlug): void
    {
        // TODO: fetch the user's user_games row for this game and render the update form.
    }

    public function pageUpdateGameAccount(string $gameSlug): void
    {
        // TODO: render the account-binding page (verification code UI only makes sense
        // when games.game_hasRiotIntegration and the game actually uses ug_verificationCode).
    }

    public function createGameUser(): void
    {
        // TODO: read game from request, resolve to game_id, call
        // $this->userGames->createUserGame(...).
    }

    public function createGameUserPhone(): void
    {
        // TODO: mobile/JSON variant of createGameUser, using the getters/setters below.
    }

    public function updateGame(): void
    {
        // TODO: call $this->userGames->updateUserGameData(...).
    }

    public function sendAccountToPhp(): void
    {
        // TODO: needs $this->userGames->addAccount(...) and the generic getters/setters
        // below instead of a specific game's model/getters.
    }

    public function bindAccount(): void
    {
        // TODO: Riot OAuth account binding, using $this->userGames->addAccount(...).
    }

    public function verifyGameAccount(): void
    {
        // TODO: verification-code flow, only meaningful when games.game_hasRiotIntegration
        // and the game actually uses ug_verificationCode (see models/UserGames.php).
    }

    public function verifyGameAccountPhone(): void
    {
        // TODO: mobile variant of verifyGameAccount.
    }

    public function refreshRiotData(): void
    {
        // TODO: pull fresh Riot data and call $this->userGames->updateSyncData(...).
    }

    public function unbindGameAccount(): void
    {
        // TODO: call $this->userGames->unbindAccount(...).
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

    public function getAccount(): mixed
    {
        return $this->account;
    }

    public function setAccount(mixed $account): void
    {
        $this->account = $account;
    }
}
