<?php

namespace database\migrations;

use database\Migration;

// models\MatchingScore and controllers\MatchingScoreController were already deleted (commit
// "The great purge V2"); the matchingscore table itself was left behind. Nothing in the
// codebase references it any more, so it's safe to drop directly - no dependency to move
// first, unlike the other legacy-table drops in this series.
class DropMatchingscoreTable implements Migration
{
    public function up(\PDO $bdd): void
    {
        $bdd->exec("DROP TABLE IF EXISTS matchingscore");
    }

    public function down(\PDO $bdd): void
    {
        $bdd->exec("
            CREATE TABLE IF NOT EXISTS matchingscore (
                match_id INT AUTO_INCREMENT PRIMARY KEY,
                match_userMatching INT NOT NULL,
                match_userMatched INT NOT NULL,
                match_score INT NOT NULL,
                UNIQUE KEY unique_match (match_userMatching, match_userMatched),
                KEY match_userMatching (match_userMatching)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
        ");
    }
}
