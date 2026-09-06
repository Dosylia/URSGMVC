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
        // add_game_id_to_user_table leaves user_gameId NULL for any user_game value that
        // doesn't match games.game_name (typos, garbage test data, blank values - real rows
        // seen in prod). Rather than block onboarding of new game slugs on cleaning up old
        // junk data, default those stragglers to League of Legends and move on.
        $stmt = $bdd->query("SELECT game_id FROM games WHERE game_slug = 'league-of-legends' LIMIT 1");
        $defaultGameId = $stmt->fetchColumn();

        if ($defaultGameId === false) {
            throw new \RuntimeException("No 'league-of-legends' row in games - seed_games must run before this migration.");
        }

        $stmt = $bdd->query("SELECT user_id, user_game FROM `user` WHERE user_gameId IS NULL");
        $unmatched = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (!empty($unmatched)) {
            echo "Defaulting " . count($unmatched) . " user(s) with an unmatched user_game value to League of Legends:\n";
            foreach ($unmatched as $row) {
                echo "  user_id={$row['user_id']} user_game=\"{$row['user_game']}\"\n";
            }

            $update = $bdd->prepare("UPDATE `user` SET user_gameId = ? WHERE user_gameId IS NULL");
            $update->execute([$defaultGameId]);
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
