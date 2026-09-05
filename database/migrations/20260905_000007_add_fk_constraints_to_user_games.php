<?php

namespace database\migrations;

use database\Migration;

// Separate from create_user_games_table on purpose, same reasoning as
// add_fk_game_id_to_user_table: DDL isn't fully transactional under InnoDB/MariaDB,
// so the table gets a chance to be backfilled and cleaned up before being constrained.
class AddFkConstraintsToUserGames implements Migration
{
    public function up(\PDO $bdd): void
    {
        $stmt = $bdd->query("
            SELECT COUNT(*) FROM user_games ug
            WHERE ug.user_id NOT IN (SELECT user_id FROM `user`)
            OR ug.game_id NOT IN (SELECT game_id FROM games)
        ");
        $orphanCount = (int) $stmt->fetchColumn();

        if ($orphanCount > 0) {
            throw new \RuntimeException(
                "{$orphanCount} row(s) in user_games reference a missing user or game. " .
                "Fix or remove them before adding the foreign keys."
            );
        }

        $bdd->exec("
            ALTER TABLE user_games
            ADD CONSTRAINT fk_user_games_user FOREIGN KEY (user_id) REFERENCES `user` (user_id) ON DELETE CASCADE,
            ADD CONSTRAINT fk_user_games_game FOREIGN KEY (game_id) REFERENCES games (game_id)
        ");
    }

    public function down(\PDO $bdd): void
    {
        $bdd->exec("
            ALTER TABLE user_games
            DROP FOREIGN KEY fk_user_games_user,
            DROP FOREIGN KEY fk_user_games_game
        ");
    }
}
