<?php

namespace database\migrations;

use database\Migration;

// leagueoflegends/valorant are fully superseded by user_games. down() only restores the
// table structure, not the data - take a dump of both tables before running this in an
// environment whose data actually matters.
//
// services\LogInService (used by RiotController, DiscordController, GoogleUserController for
// login) still queries these two tables directly and will break once they're gone - that
// dependency needs to move to UserGames before this migration is safe to run anywhere real
// users log in.
class DropLegacyGameTables implements Migration
{
    public function up(\PDO $bdd): void
    {
        $bdd->exec("DROP TABLE IF EXISTS leagueoflegends");
        $bdd->exec("DROP TABLE IF EXISTS valorant");
    }

    public function down(\PDO $bdd): void
    {
        $bdd->exec("
            CREATE TABLE IF NOT EXISTS leagueoflegends (
                lol_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                lol_noChamp SMALLINT NOT NULL DEFAULT 0,
                lol_main1 VARCHAR(20) DEFAULT NULL,
                lol_main2 VARCHAR(20) DEFAULT NULL,
                lol_main3 VARCHAR(20) DEFAULT NULL,
                lol_rank VARCHAR(20) NOT NULL,
                lol_role VARCHAR(20) NOT NULL,
                lol_server VARCHAR(20) NOT NULL,
                lol_account VARCHAR(20) DEFAULT NULL,
                lol_verificationCode VARCHAR(15) DEFAULT NULL,
                lol_verified TINYINT(1) NOT NULL DEFAULT 0,
                lol_sUsername VARCHAR(40) DEFAULT NULL,
                lol_sUsernameId VARCHAR(200) DEFAULT NULL,
                lol_sPuuid VARCHAR(100) DEFAULT NULL,
                lol_sLevel INT DEFAULT NULL,
                lol_sRank VARCHAR(30) DEFAULT NULL,
                lol_sProfileIcon VARCHAR(30) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
        ");

        $bdd->exec("
            CREATE TABLE IF NOT EXISTS valorant (
                valorant_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                valorant_noChamp SMALLINT NOT NULL DEFAULT 0,
                valorant_main1 VARCHAR(20) DEFAULT NULL,
                valorant_main2 VARCHAR(20) DEFAULT NULL,
                valorant_main3 VARCHAR(20) DEFAULT NULL,
                valorant_rank VARCHAR(20) NOT NULL,
                valorant_role VARCHAR(20) NOT NULL,
                valorant_server VARCHAR(20) NOT NULL,
                valorant_account VARCHAR(20) DEFAULT NULL,
                valorant_verified TINYINT(1) NOT NULL DEFAULT 0,
                valorant_aUsername VARCHAR(40) DEFAULT NULL,
                valorant_aUsernameId VARCHAR(200) DEFAULT NULL,
                valorant_aPuuid VARCHAR(100) DEFAULT NULL,
                valorant_aLevel INT DEFAULT NULL,
                valorant_aRank VARCHAR(30) DEFAULT NULL,
                valorant_aProfileIcon VARCHAR(30) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
        ");
    }
}
