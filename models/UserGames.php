<?php
namespace models;

use config\DataBase;

// Skeleton for the model meant to replace LeagueOfLegends.php and Valorant.php once the
// dual-write phase starts (see TODOs at the top of both). Every method takes a $gameId
// (or resolves one internally) instead of being written twice, once per game.
// Signatures below mirror the old per-game methods one for one, grouped by what they replace,
// so nothing gets lost in the merge. Fill in the bodies against user_games/games, not the
// legacy tables.
class UserGames extends DataBase
{
    private \PDO $bdd;

    public function __construct()
    {
        $this->bdd = $this->getBdd();
    }

    // Replaces LeagueOfLegends::createLoLUser / Valorant::createValorantUser.
    public function createUserGame($userId, $gameId, $main1, $main2, $main3, $rank, $role, $server, $statusChampion)
    {
        // TODO: INSERT INTO user_games (user_id, game_id, ug_main1/2/3, ug_rank, ug_role,
        // ug_server, ug_noMains). $statusChampion maps to ug_noMains like the old lol_noChamp/
        // valorant_noChamp did, keep the same meaning.
    }

    // Replaces LeagueOfLegends::updateLeagueData / Valorant::updateValorantData.
    public function updateUserGameData($userId, $gameId, $main1, $main2, $main3, $rank, $role, $server, $statusChampion)
    {
        // TODO: UPDATE user_games SET ug_main1/2/3, ug_rank, ug_role, ug_server, ug_noMains
        // WHERE user_id = ? AND game_id = ?.
    }

    // Replaces LeagueOfLegends::addLoLAccount. Valorant has no equivalent today (its account
    // binding goes through Riot OAuth, not a manual verification code) - $verificationCode
    // should stay nullable here and just not be used on the Valorant path, same asymmetry
    // as ug_verificationCode itself.
    public function addAccount($gameId, $server, $account, $verificationCode, $userId)
    {
        // TODO: UPDATE user_games SET ug_server, ug_account, ug_verificationCode
        // WHERE user_id = ? AND game_id = ?.
    }

    // Replaces LeagueOfLegends::updateSummonerData / Valorant::updateValorantRiot.
    public function updateSyncData($gameId, $userId, $sUsername, $sUsernameId, $sPuuid, $sLevel, $sRank, $sProfileIcon)
    {
        // TODO: UPDATE user_games SET ug_sUsername, ug_sUsernameId, ug_sPuuid, ug_sLevel,
        // ug_sRank, ug_sProfileIcon WHERE user_id = ? AND game_id = ?.
    }

    // Replaces LeagueOfLegends::unbindLoLAccount. Valorant has no equivalent method today
    // (flag this to product/UX before wiring it up for Valorant - might be intentional).
    public function unbindAccount($userId, $gameId)
    {
        // TODO: clear ug_account/ug_verified/ug_verificationCode/sync fields for this
        // user_id + game_id row, same fields unbindLoLAccount used to reset on leagueoflegends.
    }

    // Replaces LeagueOfLegends::getLeageUserByUsername / Valorant::getValorantUserByUsername.
    public function getUserGameByAccount($gameId, $account)
    {
        // TODO: SELECT * FROM user_games WHERE game_id = ? AND ug_account = ?.
    }

    // Replaces LeagueOfLegends::getLeageAccountByLeagueId / Valorant::getValorantAccountByValorantId.
    public function getUserGameById($userGamesId)
    {
        // TODO: SELECT * FROM user_games WHERE user_games_id = ?.
    }

    // Replaces LeagueOfLegends::getLeageUserByLolId. No direct Valorant equivalent existed
    // (getValorantAccountByValorantId already covers the same lookup by row id there) -
    // double check at implementation time whether this is actually a duplicate of
    // getUserGameById above once both games go through the same table.
    public function getUserGameByLegacyRowId($userGamesId)
    {
        // TODO: see note above, likely redundant with getUserGameById.
    }

    // Replaces LeagueOfLegends::getLeageUserByUserId / Valorant::getValorantUserByUserId.
    public function getUserGameByUserIdAndGameId($userId, $gameId)
    {
        // TODO: SELECT * FROM user_games WHERE user_id = ? AND game_id = ?.
    }

    // Replaces LeagueOfLegends::addPuuid / Valorant::addPuuid (identical signature in both
    // today, only needs $gameId added since a user can now have a puuid per game).
    public function addPuuid($puuid, $userId, $gameId)
    {
        // TODO: UPDATE user_games SET ug_sPuuid = ? WHERE user_id = ? AND game_id = ?.
    }

    // Replaces LeagueOfLegends::getUserByPuuid. Valorant never had this method - check
    // whether it's actually needed there before porting it over unchanged.
    public function getUserByPuuid($puuid, $gameId)
    {
        // TODO: SELECT * FROM user_games WHERE ug_sPuuid = ? AND game_id = ?.
    }
}
