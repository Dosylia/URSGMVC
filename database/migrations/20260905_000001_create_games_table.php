<?php

namespace database\migrations;

use database\Migration;

// Reference table for supported games. Replaces the free-text user.user_game /
// lf_game / pf_game columns so the app can join on a real ID instead of matching strings.
class CreateGamesTable implements Migration
{
    public function up(\PDO $bdd): void
    {
        $bdd->exec("
            CREATE TABLE IF NOT EXISTS games (
                game_id INT AUTO_INCREMENT PRIMARY KEY,
                game_slug VARCHAR(30) NOT NULL,
                game_name VARCHAR(50) NOT NULL,
                game_icon VARCHAR(200) DEFAULT NULL,
                game_active TINYINT(1) NOT NULL DEFAULT 1,
                game_sortOrder SMALLINT NOT NULL DEFAULT 0,
                game_hasRiotIntegration TINYINT(1) NOT NULL DEFAULT 0,
                game_createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_games_slug (game_slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function down(\PDO $bdd): void
    {
        $bdd->exec("DROP TABLE IF EXISTS games");
    }
}
