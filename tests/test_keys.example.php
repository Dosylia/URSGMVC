<?php

/**
 * ──────────────────────────────────────────────────────────────
 *  EXAMPLE – Copy this to tests/test_keys.php and fill in real values.
 *  tests/test_keys.php is git-ignored.
 * ──────────────────────────────────────────────────────────────
 */

// Riot developer API key – regenerate at https://developer.riotgames.com
$riotApiKey = 'RGAPI-XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';

// ── Known-good League of Legends account ───────────────────
$testLoLAccount = [
    'puuid'       => 'YOUR_PUUID_HERE',
    'gameName'    => 'YourGameName',
    'tagLine'     => 'YourTag',
    'server'      => 'Europe West',
    'serverCode'  => 'euw1',
    'regionRoute' => 'europe',
    'expectedRank' => 'GOLD',
    'summonerLevel' => 100,
    'profileIconId' => 1234,
];

// Riot OAuth credentials
$riotClientId     = 'YOUR_CLIENT_ID';
$riotClientSecret = 'YOUR_CLIENT_SECRET';
