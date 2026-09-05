<?php

namespace controllers;

use models\UserGames;
use models\User;
use models\FriendRequest;
use models\GoogleUser;
use traits\SecurityController;
use traits\Translatable;

// Skeleton for the controller meant to replace LeagueOfLegendsController.php and
// ValorantController.php. Methods are parameterized by $gameId/$gameSlug instead of
// being duplicated per game. Riot-specific methods (bindAccount, verify*, refreshRiotData,
// summoner lookups) only have a real implementation on the League of Legends side today -
// they're included here because that's where they belong once Valorant gets its own Riot
// integration, but until then they may just delegate to League-only logic internally.
// TODO: figure out where game_id gets resolved from a slug/route (a small Games model
// wrapping the `games` table, or inline queries here - not decided yet).
class UserGamesController
{
    use SecurityController;
    use Translatable;

    private UserGames $userGames;
    private FriendRequest $friendrequest;
    private User $user;
    private GoogleUser $googleUser;
    private $userId;
    private $gameId;
    private $main1;
    private $main2;
    private $main3;
    private $rank;
    private $role;
    private $server;
    private $account;

    public function __construct()
    {
        $this->userGames = new UserGames();
        $this->user = new User();
        $this->friendrequest = new FriendRequest();
        $this->googleUser = new GoogleUser();
    }

    public function getGoogleUserModel(): GoogleUser
    {
        return $this->googleUser;
    }

    // Replaces LeagueOfLegendsController::pageLeagueUser / ValorantController::pageValorantUser.
    public function pageGameUser($gameSlug)
    {
        // TODO: same signup-step-1 page, rendered with the right template/champion or
        // agent list for $gameSlug instead of two near-identical methods.
    }

    // Replaces pageUpdateLeague / pageUpdateValorant.
    public function pageUpdateGame($gameSlug)
    {
        // TODO: fetch the user's user_games row for this game and render the update form.
    }

    // Replaces pageUpdateLeagueAccount / pageUpdateValorantAccount.
    public function pageUpdateGameAccount($gameSlug)
    {
        // TODO: render the account-binding page (verification code UI only makes sense
        // when games.game_hasRiotIntegration and the game actually uses ug_verificationCode).
    }

    // Replaces createLeagueUser / createValorantUser.
    public function createGameUser()
    {
        // TODO: read game from request, resolve to game_id, call
        // $this->userGames->createUserGame(...) instead of the two separate model calls.
    }

    // Replaces createLeagueUserPhone / createValorantUserPhone.
    public function createGameUserPhone()
    {
        // TODO: mobile/JSON variant of createGameUser, using the getters/setters below
        // the same way the old controllers did.
    }

    // Replaces UpdateLeague / UpdateValorant.
    public function updateGame()
    {
        // TODO: call $this->userGames->updateUserGameData(...).
    }

    // Replaces LeagueOfLegendsController::sendAccountToPhp. No Valorant equivalent existed -
    // check whether this is League-specific plumbing or genuinely generic before porting it.
    public function sendAccountToPhp()
    {
        // TODO: see note above.
    }

    // Replaces LeagueOfLegendsController::bindAccount. League-only today.
    public function bindAccount()
    {
        // TODO: Riot OAuth account binding, generalized to use $this->userGames->addAccount(...).
    }

    // Replaces LeagueOfLegendsController::verifyLeagueAccount. League-only today.
    public function verifyGameAccount()
    {
        // TODO: verification-code flow, only meaningful while games.game_hasRiotIntegration
        // and the game actually uses ug_verificationCode (Valorant doesn't, see UserGames.php).
    }

    // Replaces LeagueOfLegendsController::verifyLeagueAccountPhone.
    public function verifyGameAccountPhone()
    {
        // TODO: mobile variant of verifyGameAccount.
    }

    // Replaces LeagueOfLegendsController::refreshRiotData. League-only today.
    public function refreshRiotData()
    {
        // TODO: pull fresh Riot data and call $this->userGames->updateSyncData(...).
    }

    // Replaces LeagueOfLegendsController::unbindLoLAccount. League-only today, see
    // UserGames::unbindAccount for the same asymmetry note.
    public function unbindGameAccount()
    {
        // TODO: call $this->userGames->unbindAccount(...).
    }

    // Replaces LeagueOfLegendsController::getSummonerByNameAndTag. Riot API call, League-only.
    public function getSummonerByNameAndTag($summonerName, $tagLine, $apiKey)
    {
        // TODO: port as-is, this is Riot-API plumbing not game-model plumbing.
    }

    // Replaces LeagueOfLegendsController::getSummonerProfile.
    public function getSummonerProfile($puudId, $server, $apiKey)
    {
        // TODO: port as-is.
    }

    // Replaces LeagueOfLegendsController::getSummonerRankedStats.
    public function getSummonerRankedStats($puudId, $server, $apiKey)
    {
        // TODO: port as-is.
    }

    // Replaces LeagueOfLegendsController::getTagLine.
    public function getTagLine($puuid, $server, $apiKey)
    {
        // TODO: port as-is.
    }

    // Replaces LeagueOfLegendsController::determineRankAndTier.
    public function determineRankAndTier($summonerRankedStats)
    {
        // TODO: port as-is.
    }

    // Replaces both controllers' emptyInputSignup.
    public function emptyInputSignup($account)
    {
        // TODO: port as-is, was identical (or near-identical) in both controllers - diff
        // them before merging to make sure nothing game-specific was hiding in either copy.
    }

    // Replaces both controllers' validateInput.
    public function validateInput($input)
    {
        // TODO: same as emptyInputSignup, diff both copies first.
    }

    // Getters/setters below replace the per-game-named ones (getLoLMain1/getValorantMain1,
    // etc.) used by the *Phone() methods above. Same pattern, just not duplicated per game.
    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function getGameId()
    {
        return $this->gameId;
    }

    public function setGameId($gameId)
    {
        $this->gameId = $gameId;
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

    public function getServer()
    {
        return $this->server;
    }

    public function setServer($server)
    {
        $this->server = $server;
    }

    public function getAccount()
    {
        return $this->account;
    }

    public function setAccount($account)
    {
        $this->account = $account;
    }
}
