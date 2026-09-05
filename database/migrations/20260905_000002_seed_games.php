<?php

namespace database\migrations;

use database\Migration;

// game_name is kept byte-identical to the legacy user.user_game / lf_game / pf_game
// strings on purpose, so the upcoming backfill migration can join on it directly.
class SeedGames implements Migration
{
    public function up(\PDO $bdd): void
    {
        $stmt = $bdd->prepare("
            INSERT INTO games (game_slug, game_name, game_sortOrder, game_hasRiotIntegration)
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE game_name = VALUES(game_name)
        ");

        $stmt->execute(['league-of-legends', 'League of Legends', 1]);
        $stmt->execute(['valorant', 'Valorant', 2]);
    }

    public function down(\PDO $bdd): void
    {
        $stmt = $bdd->prepare("DELETE FROM games WHERE game_slug IN (?, ?)");
        $stmt->execute(['league-of-legends', 'valorant']);
    }
}
