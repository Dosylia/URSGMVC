<?php

declare(strict_types=1);

namespace models;

use config\DataBase;

class UserLookingForGames extends DataBase
{
    private \PDO $bdd;

    public function __construct()
    {
        $this->bdd = $this->getBdd();
    }

    public function createLookingForGame(
        int $userId,
        int $gameId,
        string $gender,
        string $kindOfGamer,
        ?string $rank,
        ?string $role,
        int $noMains,
        ?string $main1,
        ?string $main2,
        ?string $main3
    ): string|false {
        $query = $this->bdd->prepare("
            INSERT INTO `user_looking_for_games`(
                `user_id`,
                `game_id`,
                `lfg_gender`,
                `lfg_kindofgamer`,
                `lfg_rank`,
                `lfg_role`,
                `lfg_noMains`,
                `lfg_main1`,
                `lfg_main2`,
                `lfg_main3`
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $created = $query->execute([$userId, $gameId, $gender, $kindOfGamer, $rank, $role, $noMains, $main1, $main2, $main3]);

        if ($created) {
            return $this->bdd->lastInsertId();
        } else {
            return false;
        }
    }

    public function updateLookingForGame(
        int $userId,
        int $gameId,
        string $gender,
        string $kindOfGamer,
        ?string $rank,
        ?string $role,
        int $noMains,
        ?string $main1,
        ?string $main2,
        ?string $main3,
        ?string $filteredServer = null
    ): bool {
        $sql = "
            UPDATE `user_looking_for_games`
            SET
                `lfg_gender` = ?,
                `lfg_kindofgamer` = ?,
                `lfg_rank` = ?,
                `lfg_role` = ?,
                `lfg_noMains` = ?,
                `lfg_main1` = ?,
                `lfg_main2` = ?,
                `lfg_main3` = ?
        ";
        $params = [$gender, $kindOfGamer, $rank, $role, $noMains, $main1, $main2, $main3];

        if (!empty($filteredServer)) {
            $sql .= ", `lfg_filteredServer` = ?";
            $params[] = $filteredServer;
        }

        $sql .= " WHERE `user_id` = ? AND `game_id` = ?";
        $params[] = $userId;
        $params[] = $gameId;

        $query = $this->bdd->prepare($sql);

        return $query->execute($params);
    }

    public function getLookingForGameByUserIdAndGameId(int $userId, int $gameId): array|false
    {
        $query = $this->bdd->prepare("
            SELECT * FROM `user_looking_for_games` WHERE `user_id` = ? AND `game_id` = ?
        ");

        $query->execute([$userId, $gameId]);

        return $query->fetch();
    }
}
