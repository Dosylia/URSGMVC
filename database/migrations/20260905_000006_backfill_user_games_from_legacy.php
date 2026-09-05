<?php

namespace database\migrations;

use database\Migration;

// Copies leagueoflegends and valorant rows into user_games. Each INSERT filters
// out rows whose user_id no longer exists in `user` (INNER JOIN instead of blindly
// copying everything) and the NOT EXISTS check makes both statements safe to re-run.
// Orphans are reported, not silently dropped, so they can be investigated before
// the add_fk_constraints_to_user_games migration runs.
class BackfillUserGamesFromLegacy implements Migration
{
    public function up(\PDO $bdd): void
    {
        $bdd->exec("
            INSERT INTO user_games (
                user_id, game_id, ug_rank, ug_role, ug_server, ug_account, ug_noMains,
                ug_main1, ug_main2, ug_main3, ug_verified, ug_verificationCode,
                ug_sUsername, ug_sUsernameId, ug_sPuuid, ug_sLevel, ug_sRank, ug_sProfileIcon
            )
            SELECT
                l.user_id,
                (SELECT game_id FROM games WHERE game_slug = 'league-of-legends' LIMIT 1),
                l.lol_rank, l.lol_role, l.lol_server, l.lol_account, l.lol_noChamp,
                l.lol_main1, l.lol_main2, l.lol_main3, l.lol_verified, l.lol_verificationCode,
                l.lol_sUsername, l.lol_sUsernameId, l.lol_sPuuid, l.lol_sLevel, l.lol_sRank, l.lol_sProfileIcon
            FROM leagueoflegends l
            INNER JOIN `user` u ON u.user_id = l.user_id
            WHERE NOT EXISTS (
                SELECT 1 FROM user_games ug
                WHERE ug.user_id = l.user_id
                AND ug.game_id = (SELECT game_id FROM games WHERE game_slug = 'league-of-legends' LIMIT 1)
            )
        ");

        $bdd->exec("
            INSERT INTO user_games (
                user_id, game_id, ug_rank, ug_role, ug_server, ug_account, ug_noMains,
                ug_main1, ug_main2, ug_main3, ug_verified, ug_verificationCode,
                ug_sUsername, ug_sUsernameId, ug_sPuuid, ug_sLevel, ug_sRank, ug_sProfileIcon
            )
            SELECT
                v.user_id,
                (SELECT game_id FROM games WHERE game_slug = 'valorant' LIMIT 1),
                v.valorant_rank, v.valorant_role, v.valorant_server, v.valorant_account, v.valorant_noChamp,
                v.valorant_main1, v.valorant_main2, v.valorant_main3, v.valorant_verified, NULL,
                v.valorant_aUsername, v.valorant_aUsernameId, v.valorant_aPuuid, v.valorant_aLevel, v.valorant_aRank, v.valorant_aProfileIcon
            FROM valorant v
            INNER JOIN `user` u ON u.user_id = v.user_id
            WHERE NOT EXISTS (
                SELECT 1 FROM user_games ug
                WHERE ug.user_id = v.user_id
                AND ug.game_id = (SELECT game_id FROM games WHERE game_slug = 'valorant' LIMIT 1)
            )
        ");

        $this->reportOrphans($bdd, 'leagueoflegends', 'lol_id');
        $this->reportOrphans($bdd, 'valorant', 'valorant_id');
    }

    public function down(\PDO $bdd): void
    {
        $bdd->exec("DELETE FROM user_games");
    }

    private function reportOrphans(\PDO $bdd, string $table, string $idColumn): void
    {
        $stmt = $bdd->query("
            SELECT t.{$idColumn} AS id, t.user_id
            FROM {$table} t
            LEFT JOIN `user` u ON u.user_id = t.user_id
            WHERE u.user_id IS NULL
        ");
        $orphans = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (!empty($orphans)) {
            echo "Warning: " . count($orphans) . " row(s) in {$table} reference a user_id that no longer exists in `user`:\n";
            foreach ($orphans as $row) {
                echo "  {$idColumn}={$row['id']} user_id={$row['user_id']}\n";
            }
            echo "These rows were not copied into user_games.\n";
        }
    }
}
