<?php

namespace database\migrations;

use database\Migration;

// Adds user.user_gameId alongside the existing user.user_game string column.
// Both columns live side by side during the transition (see the migration plan's
// dual-write strategy), old column stays untouched so down() never loses data.
class AddGameIdToUserTable implements Migration
{
    public function up(\PDO $bdd): void
    {
        $this->addColumnIfMissing($bdd);

        $bdd->exec("
            UPDATE `user` u
            INNER JOIN `games` g ON g.game_name = u.user_game
            SET u.user_gameId = g.game_id
            WHERE u.user_gameId IS NULL
        ");

        $stmt = $bdd->query("
            SELECT user_id, user_game FROM `user` WHERE user_gameId IS NULL
        ");
        $unmatched = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (!empty($unmatched)) {
            echo "Warning: " . count($unmatched) . " user(s) have a user_game value that does not match any row in games.game_name:\n";
            foreach ($unmatched as $row) {
                echo "  user_id={$row['user_id']} user_game=\"{$row['user_game']}\"\n";
            }
            echo "These rows will keep user_gameId = NULL. Fix them (typo, blank value, etc.) before running the add_fk migration.\n";
        }
    }

    public function down(\PDO $bdd): void
    {
        $bdd->exec("ALTER TABLE `user` DROP COLUMN IF EXISTS user_gameId");
    }

    private function addColumnIfMissing(\PDO $bdd): void
    {
        $stmt = $bdd->query("SHOW COLUMNS FROM `user` LIKE 'user_gameId'");

        if ($stmt->fetch() !== false) {
            return;
        }

        $bdd->exec("ALTER TABLE `user` ADD COLUMN user_gameId INT NULL AFTER user_game");
    }
}
