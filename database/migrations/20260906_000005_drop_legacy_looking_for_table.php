<?php

namespace database\migrations;

use database\Migration;

// userlookingfor is fully superseded by user_looking_for_games (including its generic
// gender/kindofgamer/filteredServer fields, not just the lf_lol*/lf_val* columns). Take a
// dump of userlookingfor before running this in an environment whose data actually matters -
// down() only restores the table structure, not the data.
//
// models\UserLookingFor and controllers\UserLookingForController must be fully moved onto
// UserLookingForGames before this migration is safe to run anywhere real users log in.
class DropLegacyLookingForTable implements Migration
{
    public function up(\PDO $bdd): void
    {
        $bdd->exec("DROP TABLE IF EXISTS userlookingfor");
    }

    public function down(\PDO $bdd): void
    {
        $bdd->exec("
            CREATE TABLE IF NOT EXISTS userlookingfor (
                lf_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                lf_gender VARCHAR(20) NOT NULL,
                lf_kindofgamer VARCHAR(50) NOT NULL,
                lf_game VARCHAR(20) NOT NULL,
                lf_filteredServer LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(lf_filteredServer))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
        ");
    }
}
