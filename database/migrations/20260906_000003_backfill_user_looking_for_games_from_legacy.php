<?php

namespace database\migrations;

use database\Migration;

// Copies every userlookingfor row into user_looking_for_games, one row per game a user
// actually has data for. A row is copied for a game when either lf_game matches that game's
// name (the normal case) or the game's rank/role columns are populated (covers a user who
// switched game without updating lf_game). The generic fields (lf_gender/lf_kindofgamer/
// lf_filteredServer) are copied onto every row produced for that user, since
// user_looking_for_games fully replaces userlookingfor rather than just its lf_lol*/lf_val*
// columns. NOT EXISTS keeps both statements safe to re-run.
class BackfillUserLookingForGamesFromLegacy implements Migration
{
    public function up(\PDO $bdd): void
    {
        $bdd->exec("
            INSERT INTO user_looking_for_games (
                user_id, game_id, lfg_gender, lfg_kindofgamer, lfg_filteredServer,
                lfg_rank, lfg_role, lfg_noMains, lfg_main1, lfg_main2, lfg_main3
            )
            SELECT
                lf.user_id,
                (SELECT game_id FROM games WHERE game_slug = 'league-of-legends' LIMIT 1),
                lf.lf_gender, lf.lf_kindofgamer, lf.lf_filteredServer,
                lf.lf_lolrank, lf.lf_lolrole, lf.lf_lolNoChamp, lf.lf_lolmain1, lf.lf_lolmain2, lf.lf_lolmain3
            FROM userlookingfor lf
            WHERE (lf.lf_game = 'League of Legends' OR lf.lf_lolrank IS NOT NULL OR lf.lf_lolrole IS NOT NULL)
            AND NOT EXISTS (
                SELECT 1 FROM user_looking_for_games lfg
                WHERE lfg.user_id = lf.user_id
                AND lfg.game_id = (SELECT game_id FROM games WHERE game_slug = 'league-of-legends' LIMIT 1)
            )
        ");

        $bdd->exec("
            INSERT INTO user_looking_for_games (
                user_id, game_id, lfg_gender, lfg_kindofgamer, lfg_filteredServer,
                lfg_rank, lfg_role, lfg_noMains, lfg_main1, lfg_main2, lfg_main3
            )
            SELECT
                lf.user_id,
                (SELECT game_id FROM games WHERE game_slug = 'valorant' LIMIT 1),
                lf.lf_gender, lf.lf_kindofgamer, lf.lf_filteredServer,
                lf.lf_valrank, lf.lf_valrole, lf.lf_valNoChamp, lf.lf_valmain1, lf.lf_valmain2, lf.lf_valmain3
            FROM userlookingfor lf
            WHERE (lf.lf_game = 'Valorant' OR lf.lf_valrank IS NOT NULL OR lf.lf_valrole IS NOT NULL)
            AND NOT EXISTS (
                SELECT 1 FROM user_looking_for_games lfg
                WHERE lfg.user_id = lf.user_id
                AND lfg.game_id = (SELECT game_id FROM games WHERE game_slug = 'valorant' LIMIT 1)
            )
        ");
    }

    public function down(\PDO $bdd): void
    {
        $bdd->exec("DELETE FROM user_looking_for_games");
    }
}
