<?php

declare(strict_types=1);

namespace models;

use config\DataBase;

class UserGames extends DataBase
{
    private \PDO $bdd;

    public function __construct()
    {
        $this->bdd = $this->getBdd();
    }

    public function createUserGame(
        int $userId,
        int $gameId,
        ?string $main1,
        ?string $main2,
        ?string $main3,
        string $rank,
        string $role,
        string $server,
        int $statusChampion
    ): string|false {
        $query = $this->bdd->prepare("
            INSERT INTO `user_games`(
                `user_id`,
                `game_id`,
                `ug_main1`,
                `ug_main2`,
                `ug_main3`,
                `ug_rank`,
                `ug_role`,
                `ug_server`,
                `ug_noMains`
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $createUserGame = $query->execute([$userId, $gameId, $main1, $main2, $main3, $rank, $role, $server, $statusChampion]);

        if ($createUserGame) {
            return $this->bdd->lastInsertId();
        } else {
            return false;
        }
    }

    public function updateUserGameData(
        int $userId,
        int $gameId,
        ?string $main1,
        ?string $main2,
        ?string $main3,
        ?string $rank,
        ?string $role,
        ?string $server,
        int $statusChampion
    ): bool {
        $sql = "UPDATE `user_games` SET ";
        $params = [];
        $updates = [];

        $updates[] = "`ug_main1` = ?";
        $params[] = $main1;

        $updates[] = "`ug_main2` = ?";
        $params[] = $main2;

        $updates[] = "`ug_main3` = ?";
        $params[] = $main3;

        if (!empty($rank)) {
            $updates[] = "`ug_rank` = ?";
            $params[] = $rank;
        }
        if (!empty($role)) {
            $updates[] = "`ug_role` = ?";
            $params[] = $role;
        }
        if (!empty($server)) {
            $updates[] = "`ug_server` = ?";
            $params[] = $server;
        }

        $updates[] = "`ug_noMains` = ?";
        $params[] = $statusChampion;

        $sql .= implode(", ", $updates) . " WHERE `user_id` = ? AND `game_id` = ?";
        $params[] = $userId;
        $params[] = $gameId;

        $query = $this->bdd->prepare($sql);

        return $query->execute($params);
    }

    // $verificationCode is nullable: only games with a manual account-verification step use it,
    // pass null for games that link accounts through OAuth instead.
    public function addAccount(int $gameId, string $server, string $account, ?string $verificationCode, int $userId): bool
    {
        $query = $this->bdd->prepare("
            UPDATE `user_games`
            SET
                `ug_server` = ?,
                `ug_account` = ?,
                `ug_verificationCode` = ?
            WHERE `user_id` = ? AND `game_id` = ?
        ");

        return $query->execute([$server, $account, $verificationCode, $userId, $gameId]);
    }

    public function updateSyncData(
        int $gameId,
        int $userId,
        ?string $sUsername,
        ?string $sUsernameId,
        ?string $sPuuid,
        ?int $sLevel,
        ?string $sRank,
        ?string $sProfileIcon,
        ?string $account = null
    ): bool {
        $query = $this->bdd->prepare("
            UPDATE `user_games`
            SET
                `ug_verified` = 1,
                `ug_sUsername` = ?,
                `ug_sUsernameId` = ?,
                `ug_sPuuid` = ?,
                `ug_sLevel` = ?,
                `ug_sRank` = ?,
                `ug_sProfileIcon` = ?,
                `ug_account` = COALESCE(?, ug_account)
            WHERE `user_id` = ? AND `game_id` = ?
        ");

        return $query->execute([$sUsername, $sUsernameId, $sPuuid, $sLevel, $sRank, $sProfileIcon, $account, $userId, $gameId]);
    }

    public function unbindAccount(int $userId, int $gameId): bool
    {
        $query = $this->bdd->prepare("
            UPDATE `user_games`
            SET
                `ug_verified` = 0,
                `ug_sUsername` = NULL,
                `ug_sUsernameId` = NULL,
                `ug_sPuuid` = NULL,
                `ug_sLevel` = NULL,
                `ug_sRank` = NULL,
                `ug_sProfileIcon` = NULL,
                `ug_account` = NULL
            WHERE `user_id` = ? AND `game_id` = ?
        ");

        return $query->execute([$userId, $gameId]);
    }

    public function getUserGameByAccount(int $gameId, string $account): array|false
    {
        $query = $this->bdd->prepare("
            SELECT * FROM `user_games` WHERE `game_id` = ? AND `ug_account` = ?
        ");

        $query->execute([$gameId, $account]);

        return $query->fetch();
    }

    public function getUserGameById(int $ugId): array|false
    {
        $query = $this->bdd->prepare("
            SELECT * FROM `user_games` WHERE `ug_id` = ?
        ");

        $query->execute([$ugId]);

        return $query->fetch();
    }

    public function getUserGameByUserIdAndGameId(int $userId, int $gameId): array|false
    {
        $query = $this->bdd->prepare("
            SELECT * FROM `user_games` WHERE `user_id` = ? AND `game_id` = ?
        ");

        $query->execute([$userId, $gameId]);

        return $query->fetch();
    }

    public function addPuuid(string $puuid, int $userId, int $gameId): bool
    {
        $query = $this->bdd->prepare("
            UPDATE `user_games` SET `ug_sPuuid` = ? WHERE `user_id` = ? AND `game_id` = ?
        ");

        return $query->execute([$puuid, $userId, $gameId]);
    }

    public function getUserByPuuid(string $puuid, int $gameId): array|false
    {
        $query = $this->bdd->prepare("
            SELECT *
            FROM `user_games` AS ug
            LEFT JOIN `user` AS u ON ug.user_id = u.user_id
            WHERE ug.`ug_sPuuid` = ? AND ug.`game_id` = ?
        ");

        $query->execute([$puuid, $gameId]);

        return $query->fetch();
    }

    public function getVerifiedUserGames(int $gameId): array
    {
        $query = $this->bdd->prepare("
            SELECT * FROM `user_games` WHERE `game_id` = ? AND `ug_verified` = 1 AND `ug_sPuuid` IS NOT NULL
        ");

        $query->execute([$gameId]);

        return $query->fetchAll();
    }
}
