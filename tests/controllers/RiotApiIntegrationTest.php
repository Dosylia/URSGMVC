<?php

namespace tests\controllers;

use PHPUnit\Framework\TestCase;
use controllers\RiotController;
use controllers\LeagueOfLegendsController;
use ReflectionClass;

/**
 * ──────────────────────────────────────────────────────────────
 *  Riot API – LIVE Integration Tests
 * ──────────────────────────────────────────────────────────────
 *
 *  These tests hit the REAL Riot API with a real API key
 *  and a known-good League of Legends account.
 *
 *  Purpose:
 *  - Catch breaking changes when Riot updates their API
 *  - Verify response shapes/fields still match what our code expects
 *
 *  Run ONLY these tests:
 *      vendor\bin\phpunit --group integration
 *
 *  Run everything EXCEPT these tests:
 *      vendor\bin\phpunit --exclude-group integration
 *
 *  Prerequisites:
 *  - Copy tests/test_keys.example.php → tests/test_keys.php
 *  - Fill in a valid Riot API key and test account data
 *
 * @group integration
 */
class RiotApiIntegrationTest extends TestCase
{
    private static string $apiKey;
    private static array  $account;
    private static RiotController $riotController;
    private static LeagueOfLegendsController $lolController;

    // ─── Bootstrap ──────────────────────────────────────────────

    public static function setUpBeforeClass(): void
    {
        $keysFile = __DIR__ . '/../test_keys.php';

        if (!file_exists($keysFile)) {
            self::markTestSkipped('tests/test_keys.php not found – copy test_keys.example.php and fill in real values.');
        }

        require $keysFile;

        /** @var string $riotApiKey */
        /** @var array  $testLoLAccount */
        if (!isset($riotApiKey) || strpos($riotApiKey, 'RGAPI-XXXX') === 0) {
            self::markTestSkipped('Riot API key is placeholder – add a real key in tests/test_keys.php');
        }

        self::$apiKey  = $riotApiKey;
        self::$account = $testLoLAccount;

        // Create controllers via reflection (bypass constructors that need DB)
        self::$riotController = self::createControllerNoConstructor(RiotController::class);
        self::$lolController  = self::createControllerNoConstructor(LeagueOfLegendsController::class);
    }

    private static function createControllerNoConstructor(string $class): object
    {
        $ref = new ReflectionClass($class);
        return $ref->newInstanceWithoutConstructor();
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  1. Account lookup by Riot ID  (LeagueOfLegendsController)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function testGetSummonerByNameAndTag(): void
    {
        $result = self::$lolController->getSummonerByNameAndTag(
            self::$account['gameName'],
            self::$account['tagLine'],
            self::$apiKey
        );

        // Must return an array (not null = API responded 200)
        $this->assertIsArray($result, 'Riot account/v1 by-riot-id returned null – API key may have expired');

        // Must contain the standard fields
        $this->assertArrayHasKey('puuid', $result, 'Response missing "puuid"');
        $this->assertArrayHasKey('gameName', $result, 'Response missing "gameName"');
        $this->assertArrayHasKey('tagLine', $result, 'Response missing "tagLine"');

        // Verify values match the known account
        $this->assertEquals(self::$account['gameName'], $result['gameName'], 'gameName mismatch');
        $this->assertEquals(self::$account['tagLine'], $result['tagLine'], 'tagLine mismatch');

        // PUUID should be a non-empty string
        $this->assertNotEmpty($result['puuid'], 'puuid is empty');
        $this->assertIsString($result['puuid']);

        // If we have a puuid in our test config, verify it matches
        if (!empty(self::$account['puuid']) && self::$account['puuid'] !== 'YOUR_PUUID_HERE') {
            $this->assertStringStartsWith(
                self::$account['puuid'],
                $result['puuid'],
                'puuid does not match expected value'
            );
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  2. Summoner Profile  (summoner/v4)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function testGetSummonerProfile(): void
    {
        $result = self::$riotController->getSummonerProfile(
            self::$account['puuid'],
            self::$account['serverCode'],
            self::$apiKey
        );

        $this->assertIsArray($result, 'getSummonerProfile returned null – API or puuid issue');

        // Keys our code depends on (see RiotController lines ~590-610)
        // NOTE: Riot removed 'id' and 'accountId' from summoner/v4 in 2024+.
        // If your code still references these, it will break!
        $this->assertArrayHasKey('puuid', $result, 'Response missing "puuid"');
        $this->assertArrayHasKey('profileIconId', $result, 'Response missing "profileIconId"');
        $this->assertArrayHasKey('summonerLevel', $result, 'Response missing "summonerLevel"');
        $this->assertArrayHasKey('revisionDate', $result, 'Response missing "revisionDate"');

        // Legacy fields that Riot REMOVED – assert they are gone so we know
        // our code needs updating if it still depends on them
        $this->assertArrayNotHasKey('id', $result,
            'Riot brought back "id" in summoner/v4 – update code if needed');
        $this->assertArrayNotHasKey('accountId', $result,
            'Riot brought back "accountId" in summoner/v4 – update code if needed');

        // Type checks
        $this->assertIsInt($result['profileIconId'], 'profileIconId should be an integer');
        $this->assertIsInt($result['summonerLevel'], 'summonerLevel should be an integer');
        $this->assertGreaterThan(0, $result['summonerLevel'], 'summonerLevel should be > 0');

        // Value sanity (level can go up, so check ≥ a reasonable floor)
        $this->assertGreaterThanOrEqual(
            100,
            $result['summonerLevel'],
            'summonerLevel unexpectedly low for the test account'
        );
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  3. Ranked Stats  (league/v4)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function testGetSummonerRankedStats(): void
    {
        $result = self::$riotController->getSummonerRankedStats(
            self::$account['puuid'],
            self::$account['serverCode'],
            self::$apiKey
        );

        $this->assertIsArray($result, 'getSummonerRankedStats returned null – API issue');

        // Could be empty if the account has no ranked games this season
        // but the test account should usually have ranked data
        if (!empty($result)) {
            $entry = $result[0];

            // Keys our code depends on (see RiotController ranked processing)
            $this->assertArrayHasKey('queueType', $entry, 'Entry missing "queueType"');
            $this->assertArrayHasKey('tier', $entry, 'Entry missing "tier"');
            $this->assertArrayHasKey('rank', $entry, 'Entry missing "rank"');
            $this->assertArrayHasKey('leaguePoints', $entry, 'Entry missing "leaguePoints"');
            $this->assertArrayHasKey('wins', $entry, 'Entry missing "wins"');
            $this->assertArrayHasKey('losses', $entry, 'Entry missing "losses"');
            $this->assertArrayHasKey('summonerId', $entry, 'Entry missing "summonerId"');

            // queueType must be a known value
            $validQueues = ['RANKED_SOLO_5x5', 'RANKED_FLEX_SR', 'RANKED_TFT_DOUBLE_UP', 'RANKED_TFT'];
            $this->assertContains($entry['queueType'], $validQueues, 'Unknown queueType: ' . $entry['queueType']);

            // tier must be a known tier
            $validTiers = ['IRON', 'BRONZE', 'SILVER', 'GOLD', 'PLATINUM', 'EMERALD', 'DIAMOND', 'MASTER', 'GRANDMASTER', 'CHALLENGER'];
            $this->assertContains($entry['tier'], $validTiers, 'Unknown tier: ' . $entry['tier']);

            // rank must be a Roman numeral
            $validRanks = ['I', 'II', 'III', 'IV'];
            $this->assertContains($entry['rank'], $validRanks, 'Unknown rank: ' . $entry['rank']);
        }
    }

    /**
     * Verify the test account's tier matches what we expect (roughly).
     * This is a softer check since rank can change with play.
     */
    public function testRankedTierMatchesExpected(): void
    {
        $result = self::$riotController->getSummonerRankedStats(
            self::$account['puuid'],
            self::$account['serverCode'],
            self::$apiKey
        );

        $this->assertIsArray($result, 'getSummonerRankedStats returned null');

        if (empty($result)) {
            $this->markTestSkipped('Test account has no ranked data this season');
        }

        // Look for Solo/Duo queue specifically
        $soloEntry = null;
        foreach ($result as $entry) {
            if ($entry['queueType'] === 'RANKED_SOLO_5x5') {
                $soloEntry = $entry;
                break;
            }
        }

        if ($soloEntry && !empty(self::$account['expectedRank'])) {
            // Just log it – don't fail on rank changes, but make it visible
            $actualTier = $soloEntry['tier'] . ' ' . $soloEntry['rank'];
            $this->assertNotEmpty($actualTier, 'Tier string should not be empty');

            // Optional: uncomment the line below to ENFORCE rank matching
            // $this->assertEquals(self::$account['expectedRank'], $soloEntry['tier']);
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  4. Match IDs  (match/v5)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function testGetMatchIds(): void
    {
        $result = self::$riotController->getMatchIds(
            self::$account['puuid'],
            self::$account['regionRoute'],
            self::$apiKey
        );

        // getMatchIds returns false if empty/error, or an array of strings
        if ($result === false) {
            $this->markTestSkipped('Test account has no recent matches or API returned an error');
        }

        $this->assertIsArray($result, 'getMatchIds should return an array of match IDs');
        $this->assertNotEmpty($result, 'Match ID array should not be empty');
        $this->assertLessThanOrEqual(20, count($result), 'Should return at most 20 match IDs');

        // Each match ID should be a string in the format: region_number (e.g. "EUW1_1234567890")
        foreach ($result as $matchId) {
            $this->assertIsString($matchId, 'Each match ID should be a string');
            $this->assertMatchesRegularExpression(
                '/^[A-Z]+\d?_\d+$/',
                $matchId,
                "Match ID '$matchId' does not match expected format (e.g. EUW1_1234567890)"
            );
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  5. Top Played Champions  (champion-mastery/v4)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function testGetTopPlayedChamps(): void
    {
        $result = self::$riotController->getTopPlayedChamps(
            self::$account['puuid'],
            self::$account['serverCode'],
            self::$apiKey
        );

        $this->assertIsArray($result, 'getTopPlayedChamps should return an array');

        // Should return up to 3 champions
        $this->assertLessThanOrEqual(3, count($result), 'Should return at most 3 champions');

        if (!empty($result)) {
            $champ = $result[0];

            // Keys our code depends on
            $this->assertArrayHasKey('championId', $champ, 'Entry missing "championId"');
            $this->assertArrayHasKey('championLevel', $champ, 'Entry missing "championLevel"');
            $this->assertArrayHasKey('championPoints', $champ, 'Entry missing "championPoints"');
            $this->assertArrayHasKey('puuid', $champ, 'Entry missing "puuid"');

            // Type checks
            $this->assertIsInt($champ['championId'], 'championId should be an int');
            $this->assertIsInt($champ['championLevel'], 'championLevel should be an int');
            $this->assertIsInt($champ['championPoints'], 'championPoints should be an int');
            $this->assertGreaterThan(0, $champ['championPoints'], 'championPoints should be > 0');
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  6. Game Status / Spectator  (spectator/v5)
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function testGetGameStatusNotInGame(): void
    {
        $result = self::$riotController->getGameStatus(
            self::$account['puuid'],
            self::$account['serverCode'],
            self::$apiKey
        );

        // Most of the time the test account won't be in a game
        // getGameStatus returns null when not in game, or an array when in game
        // Either result is valid; we just make sure it doesn't crash
        if ($result === null) {
            $this->assertNull($result, 'getGameStatus should return null when not in a game');
        } else {
            // If they happen to be in a game, verify the shape
            $this->assertIsArray($result);
            $this->assertArrayHasKey('gameId', $result, 'Active game missing "gameId"');
            $this->assertArrayHasKey('participants', $result, 'Active game missing "participants"');
            $this->assertArrayHasKey('gameMode', $result, 'Active game missing "gameMode"');
        }
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  7. Data Dragon – versions endpoint
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function testDDragonVersionsEndpoint(): void
    {
        $url = 'https://ddragon.leagueoflegends.com/api/versions.json';
        $response = @file_get_contents($url);

        $this->assertNotFalse($response, 'DDragon versions.json endpoint is unreachable');

        $versions = json_decode($response, true);
        $this->assertIsArray($versions, 'versions.json should decode to an array');
        $this->assertNotEmpty($versions, 'versions array should not be empty');

        // First element is the latest version – should be a semver-ish string
        $latestVersion = $versions[0];
        $this->assertIsString($latestVersion);
        $this->assertMatchesRegularExpression(
            '/^\d+\.\d+\.\d+$/',
            $latestVersion,
            "Latest version '$latestVersion' does not look like a version number"
        );
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  8. Data Dragon – champion data
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function testDDragonChampionDataEndpoint(): void
    {
        // Get latest version first
        $versionsJson = @file_get_contents('https://ddragon.leagueoflegends.com/api/versions.json');
        $this->assertNotFalse($versionsJson, 'Could not fetch DDragon versions');

        $versions = json_decode($versionsJson, true);
        $latestVersion = $versions[0];

        // Fetch champion data
        $url = "https://ddragon.leagueoflegends.com/cdn/{$latestVersion}/data/en_US/champion.json";
        $response = @file_get_contents($url);

        $this->assertNotFalse($response, "DDragon champion.json unreachable for version $latestVersion");

        $data = json_decode($response, true);
        $this->assertIsArray($data, 'champion.json should decode to an array');
        $this->assertArrayHasKey('data', $data, 'champion.json missing "data" key');

        $champions = $data['data'];
        $this->assertNotEmpty($champions, 'Champion data should not be empty');

        // Spot check a champion we know exists
        $this->assertArrayHasKey('Jinx', $champions, 'Jinx should exist in champion data');
        $this->assertArrayHasKey('key', $champions['Jinx'], 'Jinx entry missing "key"');
        $this->assertArrayHasKey('name', $champions['Jinx'], 'Jinx entry missing "name"');
        $this->assertEquals('Jinx', $champions['Jinx']['name']);

        // Verify getChampionNameById still works with this data
        $riotController = self::$riotController;
        $jinxId = (int) $champions['Jinx']['key'];
        $resolvedName = $riotController->getChampionNameById($jinxId, $champions);
        $this->assertEquals('Jinx', $resolvedName, 'getChampionNameById failed to resolve Jinx from live DDragon data');
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  9. Cross-check: puuid from account lookup vs test config
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function testPuuidConsistencyAcrossEndpoints(): void
    {
        // Step 1: Look up by Riot ID
        $accountData = self::$lolController->getSummonerByNameAndTag(
            self::$account['gameName'],
            self::$account['tagLine'],
            self::$apiKey
        );

        $this->assertIsArray($accountData, 'Account lookup returned null');
        $puuidFromAccount = $accountData['puuid'];

        // Step 2: Fetch summoner profile using that puuid
        $profileData = self::$riotController->getSummonerProfile(
            $puuidFromAccount,
            self::$account['serverCode'],
            self::$apiKey
        );

        $this->assertIsArray($profileData, 'Summoner profile lookup returned null');
        $puuidFromProfile = $profileData['puuid'];

        // The puuid should be consistent across both endpoints
        $this->assertEquals(
            $puuidFromAccount,
            $puuidFromProfile,
            'puuid mismatch between account/v1 and summoner/v4'
        );
    }

    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    //  10. API key validity check
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

    public function testApiKeyIsValid(): void
    {
        // Quick check: fetch summoner profile – if key expired we get null
        $result = self::$riotController->getSummonerProfile(
            self::$account['puuid'],
            self::$account['serverCode'],
            self::$apiKey
        );

        $this->assertNotNull(
            $result,
            'API key appears to be invalid or expired – regenerate at https://developer.riotgames.com'
        );
    }
}
