<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * Base test case for all controller tests.
 *
 * Provides helpers to:
 * - Create controller instances with mocked model dependencies (no DB)
 * - Simulate $_POST, $_GET, $_SESSION, $_SERVER, $_FILES, $_COOKIE
 * - Capture `echo` output from controller methods
 * - Decode JSON responses
 * - Simulate Bearer token headers
 *
 * IMPORTANT: Controllers are instantiated via reflection to bypass the
 * constructor (which creates real model instances). Mock models are then
 * injected into private properties via reflection.
 */
abstract class BaseControllerTestCase extends TestCase
{
    /** @var array Backup of superglobals to restore after each test */
    private array $backupPost;
    private array $backupGet;
    private array $backupSession;
    private array $backupServer;
    private array $backupCookie;
    private array $backupFiles;

    protected function setUp(): void
    {
        parent::setUp();

        // Backup superglobals
        $this->backupPost = $_POST;
        $this->backupGet = $_GET;
        $this->backupSession = $_SESSION ?? [];
        $this->backupServer = $_SERVER;
        $this->backupCookie = $_COOKIE;
        $this->backupFiles = $_FILES;

        // Reset them
        $_POST = [];
        $_GET = [];
        $_SERVER = array_merge($_SERVER, [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $_COOKIE['lang'] = 'en';
        $_FILES = [];
        $_SESSION['lang'] = 'en';    
    }

    protected function tearDown(): void
    {
        // Restore superglobals
        $_POST = $this->backupPost;
        $_GET = $this->backupGet;
        $_SESSION = $this->backupSession;
        $_SERVER = $this->backupServer;
        $_COOKIE = $this->backupCookie;
        $_FILES = $this->backupFiles;

        parent::tearDown();
    }

    // ─── Controller Factory ──────────────────────────────────────

    /**
     * Create a controller instance WITHOUT calling its constructor,
     * then inject mock models into its private properties.
     *
     * @param string $controllerClass  Fully qualified class name
     * @param array  $mocks            ['propertyName' => $mockObject, ...]
     * @return object  The controller instance with mocks injected
     */
    protected function createControllerWithMocks(string $controllerClass, array $mocks): object
    {
        $ref = new ReflectionClass($controllerClass);
        $controller = $ref->newInstanceWithoutConstructor();

        foreach ($mocks as $property => $mock) {
            $this->injectProperty($controller, $property, $mock);
        }

        return $controller;
    }

    /**
     * Inject a value into a private/protected property via reflection.
     */
    protected function injectProperty(object $object, string $propertyName, $value): void
    {
        $ref = new ReflectionClass($object);

        // Walk up the class hierarchy to find the property
        $current = $ref;
        while ($current) {
            if ($current->hasProperty($propertyName)) {
                $prop = $current->getProperty($propertyName);
                $prop->setAccessible(true);
                $prop->setValue($object, $value);
                return;
            }
            $current = $current->getParentClass();
        }

        // If property doesn't exist on the class, try adding it dynamically
        $object->$propertyName = $value;
    }

    /**
     * Read a private/protected property value via reflection.
     */
    protected function readProperty(object $object, string $propertyName)
    {
        $ref = new ReflectionClass($object);
        $current = $ref;
        while ($current) {
            if ($current->hasProperty($propertyName)) {
                $prop = $current->getProperty($propertyName);
                $prop->setAccessible(true);
                return $prop->getValue($object);
            }
            $current = $current->getParentClass();
        }
        throw new \RuntimeException("Property {$propertyName} not found on " . get_class($object));
    }

    // ─── HTTP Simulation ─────────────────────────────────────────

    /**
     * Set up a simulated Bearer token in $_SERVER.
     */
    protected function setBearerToken(string $token): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
    }

    /**
     * Set the session to simulate a fully logged-in user.
     */
    protected function simulateLoggedInUser(
        int $userId = 1,
        string $googleId = 'g123456',
        int $lolId = 1,
        ?int $valorantId = null,
        int $lfId = 1
    ): void {
        $_SESSION['userId'] = $userId;
        $_SESSION['google_id'] = $googleId;
        $_SESSION['lol_id'] = $lolId;
        $_SESSION['lf_id'] = $lfId;
        if ($valorantId !== null) {
            $_SESSION['valorant_id'] = $valorantId;
        }
    }

    /**
     * Simulate a logged-in admin (userId 157).
     */
    protected function simulateAdmin(): void
    {
        $this->simulateLoggedInUser(157, 'g_admin', 1, null, 1);
    }

    /**
     * Simulate a logged-in moderator (userId 161).
     */
    protected function simulateModerator(): void
    {
        $this->simulateLoggedInUser(161, 'g_mod', 1, null, 1);
    }

    // ─── Output Capture ──────────────────────────────────────────

    /**
     * Call a controller method and capture its echo output.
     * Also catches `exit()` calls via a custom error handler approach.
     *
     * @param object $controller
     * @param string $method
     * @return string  The echoed output
     */
    protected function captureOutput(object $controller, string $method): string
    {
        ob_start();
        // Suppress warnings from setcookie()/header() which output into the buffer in CLI
        $previousLevel = error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
        try {
            $controller->$method();
        } catch (\Exception $e) {
            // Some methods may throw; capture output anyway
        } finally {
            error_reporting($previousLevel);
        }
        return ob_get_clean() ?: '';
    }

    /**
     * Call a controller method, capture its JSON output, and decode it.
     *
     * @param object $controller
     * @param string $method
     * @return array|null  Decoded JSON array, or null if not valid JSON
     */
    protected function captureJsonOutput(object $controller, string $method): ?array
    {
        $output = $this->captureOutput($controller, $method);
        $decoded = json_decode($output, true);
        return is_array($decoded) ? $decoded : null;
    }

    // ─── Fake Data Helpers ───────────────────────────────────────

    /**
     * Return a fake user array that matches the structure returned by User::getUserById().
     */
    protected function fakeUser(array $overrides = []): array
    {
        return array_merge([
            'user_id' => 1,
            'google_userId' => 1,
            'user_username' => 'TestUser',
            'user_gender' => 'Male',
            'user_age' => 25,
            'user_kindOfGamer' => 'Competitive',
            'user_shortBio' => 'Test bio',
            'user_picture' => 'default.png',
            'user_bonusPicture' => null,
            'user_discord' => 'TestUser#1234',
            'user_instagram' => null,
            'user_twitter' => null,
            'user_twitch' => null,
            'user_bluesky' => null,
            'user_game' => 'League of Legends',
            'user_token' => null,
            'user_currency' => 500,
            'user_isVip' => 0,
            'user_isPartner' => 0,
            'user_isCertified' => 0,
            'user_hasChatFilter' => 0,
            'user_lastRequestTime' => date('Y-m-d H:i:s'),
            'user_lastReward' => null,
            'user_streak' => 0,
            'user_isOnline' => 1,
            'user_lastSeen' => date('Y-m-d H:i:s'),
            'user_arcane' => null,
            'user_ignore' => 0,
            'user_isLooking' => 0,
            'user_requestIsLooking' => date('Y-m-d H:i:s'),
            'user_lastCompletedGame' => null,
            'user_totalCompletedGame' => 0,
            'user_friendsInvited' => 0,
            'user_notificationPermission' => 0,
            'user_notificationEndPoint' => null,
            'user_notificationP256dh' => null,
            'user_notificationAuth' => null,
            'user_personalityTestResult' => null,
            'user_isGold' => 0,
            'user_isAscend' => 0,
            'user_personalColor' => null,
            'user_banner' => null,
            'user_rating' => 0,
            'rating_count' => 0,
            'user_deletionToken' => null,
            'user_deletionTokenExpiry' => null,
            'user_numberChangedUsername' => 0,
            'user_usernameChangeMonth' => null,
            'arcane_snapshot' => null,
            'google_createdWithRSO' => 0,
            'lf_filteredServer' => null,
            // LoL fields often joined
            'lol_id' => 1,
            'lol_main1' => 'Lux',
            'lol_main2' => 'Janna',
            'lol_main3' => 'Sona',
            'lol_rank' => 'Gold',
            'lol_role' => 'Support',
            'lol_server' => 'Europe West',
            'lol_account' => null,
            'lol_verified' => 0,
            'lol_sPuuid' => null,
            'lol_noChamp' => 0,
            // Valorant fields
            'valorant_id' => null,
            'valorant_main1' => null,
            'valorant_rank' => null,
            'valorant_role' => null,
            'valorant_server' => null,
            // LF fields
            'lf_id' => 1,
            'lf_gender' => 'Any',
            'lf_kindofgamer' => 'Competitive',
            'lf_game' => 'League of Legends',
            'lf_lolmain1' => 'Jinx',
            'lf_lolmain2' => 'Seraphine',
            'lf_lolmain3' => 'Nami',
            'lf_lolrank' => 'Gold',
            'lf_lolrole' => 'ADC',
            // Google fields
            'google_id' => 'g123456',
            'google_email' => 'test@example.com',
            'google_fullName' => 'Test User',
            'google_confirmEmail' => 1,
        ], $overrides);
    }

    /**
     * Return a fake Google user array.
     */
    protected function fakeGoogleUser(array $overrides = []): array
    {
        return array_merge([
            'google_userId' => 1,
            'google_id' => 'g123456',
            'google_fullName' => 'Test User',
            'google_firstName' => 'Test',
            'google_lastName' => 'User',
            'google_email' => 'test@example.com',
            'google_confirmEmail' => 1,
            'google_masterToken' => 'test_master_token_123',
            'google_masterTokenWebsite' => 'test_master_token_website_123',
            'google_createdWithRSO' => 0,
            'google_createdWithDiscord' => 0,
            'google_unsubscribeMails' => 0,
        ], $overrides);
    }

    /**
     * Return a fake LoL profile array.
     */
    protected function fakeLoLProfile(array $overrides = []): array
    {
        return array_merge([
            'lol_id' => 1,
            'user_id' => 1,
            'lol_noChamp' => 0,
            'lol_main1' => 'Lux',
            'lol_main2' => 'Janna',
            'lol_main3' => 'Sona',
            'lol_rank' => 'Gold',
            'lol_role' => 'Support',
            'lol_server' => 'Europe West',
            'lol_account' => null,
            'lol_verificationCode' => null,
            'lol_verified' => 0,
            'lol_sPuuid' => null,
            'lol_sUsername' => null,
            'lol_sUsernameId' => null,
            'lol_sLevel' => null,
            'lol_sRank' => null,
            'lol_sProfileIcon' => null,
        ], $overrides);
    }

    /**
     * Return a fake Valorant profile array.
     */
    protected function fakeValorantProfile(array $overrides = []): array
    {
        return array_merge([
            'valorant_id' => 1,
            'user_id' => 1,
            'valorant_noChamp' => 0,
            'valorant_main1' => 'Sage',
            'valorant_main2' => null,
            'valorant_main3' => null,
            'valorant_rank' => 'Gold',
            'valorant_role' => 'Support',
            'valorant_server' => 'EU',
            'valorant_account' => null,
            'valorant_verified' => 0,
            'valorant_aUsername' => null,
            'valorant_aUsernameId' => null,
            'valorant_aPuuid' => null,
            'valorant_aLevel' => null,
            'valorant_aRank' => null,
            'valorant_aProfileIcon' => null,
        ], $overrides);
    }

    /**
     * Return a fake friend request array.
     */
    protected function fakeFriendRequest(array $overrides = []): array
    {
        return array_merge([
            'fr_id' => 1,
            'fr_senderId' => 1,
            'fr_receiverId' => 2,
            'fr_date' => '2025-08-10',
            'fr_status' => 'accepted',
            'fr_rejectedAt' => null,
            'fr_acceptedAt' => date('Y-m-d H:i:s'),
            'fr_notifReadPending' => 0,
            'fr_notifReadAccepted' => 0,
        ], $overrides);
    }

    /**
     * Return a fake chat message array.
     */
    protected function fakeChatMessage(array $overrides = []): array
    {
        return array_merge([
            'chat_id' => 1,
            'chat_senderId' => 1,
            'chat_receiverId' => 2,
            'chat_message' => 'Hello there!',
            'chat_status' => 'sent',
            'chat_date' => date('Y-m-d H:i:s'),
            'chat_replyTo' => null,
        ], $overrides);
    }

    /**
     * Return a fake item array.
     */
    protected function fakeItem(array $overrides = []): array
    {
        return array_merge([
            'items_id' => 1,
            'items_name' => 'VIP Badge',
            'items_price' => 100,
            'items_desc' => 'A shiny badge',
            'items_picture' => 'badge.png',
            'items_category' => 'Cosmetic',
            'items_discount' => 0.00,
            'items_isActive' => 1,
        ], $overrides);
    }

    /**
     * Return a fake player finder post array.
     */
    protected function fakePlayerFinderPost(array $overrides = []): array
    {
        return array_merge([
            'pf_id' => 1,
            'user_id' => 1,
            'pf_role' => 'Support',
            'pf_rank' => 'Gold',
            'pf_description' => 'Looking for friendly teammates',
            'pf_voiceChat' => 1,
            'pf_game' => 'League of Legends',
            'pf_createdAt' => date('Y-m-d H:i:s'),
            'pf_peopleInterest' => null,
        ], $overrides);
    }

    /**
     * Return a fake game character array (for the daily guessing game).
     */
    protected function fakeGameCharacter(array $overrides = []): array
    {
        return array_merge([
            'game_username' => 'Ahri',
            'game_main' => 'Mid',
            'hint_affiliation' => 'Ionia',
            'hint_gender' => 'Female',
            'hint_guess' => 'Nine-tailed fox',
            'game_picture' => 'ahri.png',
            'game_date' => date('Y-m-d'),
            'game_game' => 'League of Legends',
        ], $overrides);
    }

    /**
     * Return a fake matching score row.
     */
    protected function fakeMatchingScore(array $overrides = []): array
    {
        return array_merge([
            'match_id' => 1,
            'match_userMatching' => 1,
            'match_userMatched' => 2,
            'match_score' => 85,
        ], $overrides);
    }

    /**
     * Return a fake block row.
     */
    protected function fakeBlock(array $overrides = []): array
    {
        return array_merge([
            'block_id' => 1,
            'block_senderId' => 1,
            'block_receiverId' => 2,
            'block_date' => date('Y-m-d'),
        ], $overrides);
    }

    /**
     * Return a fake report row.
     */
    protected function fakeReport(array $overrides = []): array
    {
        return array_merge([
            'report_id' => 1,
            'reporter_id' => 1,
            'reported_id' => 2,
            'content_id' => null,
            'content_type' => 'profile',
            'reason' => 'Inappropriate behavior',
            'details' => 'Test report details',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ], $overrides);
    }

    /**
     * Return a fake LF (looking for) row.
     */
    protected function fakeLookingFor(array $overrides = []): array
    {
        return array_merge([
            'lf_id' => 1,
            'user_id' => 1,
            'lf_gender' => 'Any',
            'lf_kindofgamer' => 'Competitive',
            'lf_game' => 'League of Legends',
            'lf_filteredServer' => null,
            'lf_lolNoChamp' => 0,
            'lf_lolmain1' => 'Jinx',
            'lf_lolmain2' => 'Seraphine',
            'lf_lolmain3' => 'Nami',
            'lf_lolrank' => 'Gold',
            'lf_lolrole' => 'ADC',
            'lf_valNoChamp' => 0,
            'lf_valmain1' => null,
            'lf_valmain2' => null,
            'lf_valmain3' => null,
            'lf_valrank' => null,
            'lf_valrole' => null,
        ], $overrides);
    }
}
