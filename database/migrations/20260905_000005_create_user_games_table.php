<?php

namespace database\migrations;

use database\Migration;

// Unified per-user-per-game profile table, replacing leagueoflegends/valorant.
// No FK yet on purpose (see add_fk_constraints_to_user_games), so orphans can be
// cleaned up before the constraint is added. UNIQUE(user_id, game_id) is what allows
// a user to eventually have more than one game at once, unlike the old single-table-per-game setup.
class CreateUserGamesTable implements Migration
{
    public function up(\PDO $bdd): void
    {
        $bdd->exec("
            CREATE TABLE IF NOT EXISTS user_games (
                ug_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                game_id INT NOT NULL,
                ug_rank VARCHAR(20) NOT NULL,
                ug_role VARCHAR(20) NOT NULL,
                ug_server VARCHAR(20) NOT NULL,
                ug_account VARCHAR(20) DEFAULT NULL,
                ug_noMains SMALLINT NOT NULL DEFAULT 0,
                ug_main1 VARCHAR(20) DEFAULT NULL,
                ug_main2 VARCHAR(20) DEFAULT NULL,
                ug_main3 VARCHAR(20) DEFAULT NULL,
                ug_verified TINYINT(1) NOT NULL DEFAULT 0,
                ug_verificationCode VARCHAR(15) DEFAULT NULL,
                ug_sUsername VARCHAR(40) DEFAULT NULL,
                ug_sUsernameId VARCHAR(200) DEFAULT NULL,
                ug_sPuuid VARCHAR(100) DEFAULT NULL,
                ug_sLevel INT DEFAULT NULL,
                ug_sRank VARCHAR(30) DEFAULT NULL,
                ug_sProfileIcon VARCHAR(30) DEFAULT NULL,
                ug_createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ug_updatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_user_games_user_game (user_id, game_id),
                KEY user_games_game_id (game_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function down(\PDO $bdd): void
    {
        $bdd->exec("DROP TABLE IF EXISTS user_games");
    }
}
