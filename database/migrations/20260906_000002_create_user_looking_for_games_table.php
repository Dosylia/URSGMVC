<?php

namespace database\migrations;

use database\Migration;

// Fully replaces userlookingfor (not just its lf_lol*/lf_val* columns): the generic fields
// (gender/kindofgamer/filteredServer) move here too, one row per user per game, same pattern
// as user_games replacing leagueoflegends/valorant. No FK yet on purpose (see
// add_fk_constraints_to_user_looking_for_games). UNIQUE(user_id, game_id) mirrors user_games
// so a user can eventually have looking-for preferences for more than one game at once.
class CreateUserLookingForGamesTable implements Migration
{
    public function up(\PDO $bdd): void
    {
        $bdd->exec("
            CREATE TABLE IF NOT EXISTS user_looking_for_games (
                lfg_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                game_id INT NOT NULL,
                lfg_gender VARCHAR(20) NOT NULL,
                lfg_kindofgamer VARCHAR(50) NOT NULL,
                lfg_filteredServer LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(lfg_filteredServer)),
                lfg_rank VARCHAR(20) DEFAULT NULL,
                lfg_role VARCHAR(20) DEFAULT NULL,
                lfg_noMains SMALLINT NOT NULL DEFAULT 0,
                lfg_main1 VARCHAR(20) DEFAULT NULL,
                lfg_main2 VARCHAR(20) DEFAULT NULL,
                lfg_main3 VARCHAR(20) DEFAULT NULL,
                lfg_createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                lfg_updatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_user_looking_for_games_user_game (user_id, game_id),
                KEY user_looking_for_games_game_id (game_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function down(\PDO $bdd): void
    {
        $bdd->exec("DROP TABLE IF EXISTS user_looking_for_games");
    }
}
