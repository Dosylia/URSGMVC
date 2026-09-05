<?php

namespace database\migrations;

use database\Migration;

// Separate from the column-add migration on purpose: DDL is not fully transactional
// under InnoDB/MariaDB, so each logical step (add column+backfill, then add FK) stays
// its own migration and its own point of failure.
class AddFkGameIdToUserTable implements Migration
{
    public function up(\PDO $bdd): void
    {
        $stmt = $bdd->query("SELECT COUNT(*) FROM `user` WHERE user_gameId IS NULL");
        $unmatchedCount = (int) $stmt->fetchColumn();

        if ($unmatchedCount > 0) {
            throw new \RuntimeException(
                "{$unmatchedCount} user(s) still have user_gameId = NULL. " .
                "Run the add_game_id_to_user_table migration's warning output down to zero before adding the FK."
            );
        }

        $bdd->exec("
            ALTER TABLE `user`
            ADD CONSTRAINT fk_user_gameId FOREIGN KEY (user_gameId) REFERENCES games(game_id)
        ");
    }

    public function down(\PDO $bdd): void
    {
        $bdd->exec("ALTER TABLE `user` DROP FOREIGN KEY fk_user_gameId");
    }
}
