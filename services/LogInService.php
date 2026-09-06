<?php

// Resumes a session for an already-existing `googleuser` identity, shared by
// Riot/Discord/Google's "user already has an account" login paths.

namespace services;

use models\User;
use models\UserGames;
use models\Games;
use models\UserLookingForGames;
use enums\GameSlug;

class LogInService
{
    private MasterTokenService $masterTokenService;
    private User $user;
    private UserGames $userGames;
    private Games $games;
    private UserLookingForGames $userLookingForGames;

    public function __construct(
        MasterTokenService $masterTokenService,
        User $user,
        UserGames $userGames,
        Games $games,
        UserLookingForGames $userLookingForGames
    ) {
        $this->masterTokenService = $masterTokenService;
        $this->user = $user;
        $this->userGames = $userGames;
        $this->games = $games;
        $this->userLookingForGames = $userLookingForGames;
    }

    /**
     * Resumes a web session for an already-existing identity row from `googleuser`.
     * Mints/reuses the web master token, writes the auth_token cookie, populates
     * $_SESSION, and resolves which onboarding step the linked app-level user is at.
     */
    public function resumeWebSession(array $identityRow): LoginOutcome
    {
        $token = $this->masterTokenService->mintOrReuseToken($identityRow, false);
        $this->masterTokenService->writeAuthCookie($token);

        $_SESSION['google_userId'] = $identityRow['google_userId'];
        $_SESSION['full_name'] = $identityRow['google_fullName'];
        $_SESSION['google_id'] = $identityRow['google_id'];
        $_SESSION['email'] = $identityRow['google_email'];
        $_SESSION['google_firstName'] = $identityRow['google_firstName'];
        $_SESSION['masterTokenWebsite'] = $token;

        $userRow = $this->user->getUserByGoogleUserId($identityRow['google_userId']);

        if (!$userRow) {
            return new LoginOutcome([
                'userExists' => false,
                'identityRow' => $identityRow,
                'masterToken' => $token,
                'destination' => LoginDestination::NO_USER_ROW,
            ]);
        }

        $_SESSION['userId'] = $userRow['user_id'];
        $_SESSION['username'] = $userRow['user_username'];
        $_SESSION['gender'] = $userRow['user_gender'];
        $_SESSION['age'] = $userRow['user_age'];
        $_SESSION['kindOfGamer'] = $userRow['user_kindOfGamer'];
        $_SESSION['game'] = $userRow['user_game'];

        return $this->resolveOnboardingDestination($identityRow, $userRow, $token);
    }

    /**
     * Resumes a mobile profile fetch for an already-existing identity row.
     * Mints/reuses the mobile master token and returns curated camelCase
     * arrays (no $_SESSION/cookie writes - the mobile app carries its own
     * session via the returned token).
     */
    public function resumeMobileProfile(array $identityRow): LoginOutcome
    {
        $token = $this->masterTokenService->mintOrReuseToken($identityRow, true);

        $googleUserData = [
            'googleId' => $identityRow['google_id'],
            'fullName' => $identityRow['google_fullName'],
            'firstName' => $identityRow['google_firstName'],
            'lastName' => $identityRow['google_lastName'],
            'email' => $identityRow['google_email'],
            'googleUserId' => $identityRow['google_userId'],
            'token' => $token,
        ];

        $userRow = $this->user->getUserByGoogleUserId($identityRow['google_userId']);

        if (!$userRow) {
            return new LoginOutcome([
                'userExists' => false,
                'identityRow' => $googleUserData,
                'masterToken' => $token,
                'destination' => LoginDestination::NO_USER_ROW,
            ]);
        }

        $userData = [
            'userId' => $userRow['user_id'],
            'username' => $userRow['user_username'],
            'gender' => $userRow['user_gender'],
            'age' => $userRow['user_age'],
            'kindOfGamer' => $userRow['user_kindOfGamer'],
            'game' => $userRow['user_game'],
            'shortBio' => $userRow['user_shortBio'],
            'picture' => $userRow['user_picture'] ?? null,
            'bonusPicture' => $userRow['user_bonusPicture'] ?? null,
            'discord' => $userRow['user_discord'] ?? null,
            'twitch' => $userRow['user_twitch'] ?? null,
            'instagram' => $userRow['user_instagram'] ?? null,
            'twitter' => $userRow['user_twitter'] ?? null,
            'bluesky' => $userRow['user_bluesky'] ?? null,
            'currency' => $userRow['user_currency'] ?? null,
            'isGold' => $userRow['user_isGold'] ?? null,
            'isPartner' => $userRow['user_isPartner'] ?? null,
            'isCertified' => $userRow['user_isCertified'] ?? null,
            'hasChatFilter' => $userRow['user_hasChatFilter'] ?? null,
            'arcane' => $userRow['user_arcane'] ?? null,
            'arcaneIgnore' => $userRow['user_ignore'] ?? null,
        ];

        return $this->resolveMobileOnboardingDestination($googleUserData, $userData, $userRow, $token);
    }

    /**
     * Curated-array counterpart of resolveOnboardingDestination() for the
     * mobile surface: same game/looking-for branching, but builds camelCase
     * profile arrays instead of raw DB rows and never touches $_SESSION.
     */
    private function resolveMobileOnboardingDestination(array $googleUserData, array $userData, array $userRow, string $token): LoginOutcome
    {
        $base = [
            'userExists' => true,
            'identityRow' => $googleUserData,
            'userRow' => $userData,
            'masterToken' => $token,
        ];

        $gameSlug = $userRow['game_slug'] ?? null;
        $gameId = $gameSlug ? $this->games->getIdBySlug($gameSlug) : null;
        $userGame = $gameId ? $this->userGames->getUserGameByUserIdAndGameId($userRow['user_id'], $gameId) : null;

        if (!$userGame) {
            return new LoginOutcome($base + ['game' => $gameSlug, 'destination' => LoginDestination::NEEDS_GAME_ACCOUNT]);
        }

        $gameProfile = [
            'userGamesId' => $userGame['ug_id'],
            'main1' => $userGame['ug_main1'],
            'main2' => $userGame['ug_main2'],
            'main3' => $userGame['ug_main3'],
            'rank' => $userGame['ug_rank'],
            'role' => $userGame['ug_role'],
            'server' => $userGame['ug_server'],
            'account' => $userGame['ug_account'],
            'sUsername' => $userGame['ug_sUsername'],
            'sLevel' => $userGame['ug_sLevel'],
            'sRank' => $userGame['ug_sRank'],
            'sProfileIcon' => $userGame['ug_sProfileIcon'],
            'skipSelection' => $userGame['ug_noMains'],
        ];

        $lfGame = $this->userLookingForGames->getLookingForGameByUserIdAndGameId($userRow['user_id'], $gameId);

        if (!$lfGame) {
            return new LoginOutcome($base + ['game' => $gameSlug, 'gameProfile' => $gameProfile, 'destination' => LoginDestination::NEEDS_LOOKING_FOR]);
        }

        $lookingForData = [
            'lfId' => $lfGame['lfg_id'],
            'lfGender' => $lfGame['lfg_gender'],
            'lfKingOfGamer' => $lfGame['lfg_kindofgamer'],
            'lfGame' => $gameSlug,
            'filteredServerLf' => $lfGame['lfg_filteredServer'],
        ];

        return new LoginOutcome($base + ['game' => $gameSlug, 'gameProfile' => $gameProfile, 'lookingForRow' => $lookingForData, 'destination' => LoginDestination::ONBOARDED]);
    }

    /**
     * Resolves which game the linked user is on, writes the matching
     * session key, and decides which onboarding step is next: needs a
     * game account, needs a looking-for profile, or fully onboarded.
     */
    private function resolveOnboardingDestination(array $identityRow, array $userRow, string $token): LoginOutcome
    {
        $base = [
            'userExists' => true,
            'identityRow' => $identityRow,
            'userRow' => $userRow,
            'masterToken' => $token,
        ];

        $gameSlug = $userRow['game_slug'] ?? null;
        $gameId = $gameSlug ? $this->games->getIdBySlug($gameSlug) : null;
        $gameProfile = $gameId ? $this->userGames->getUserGameByUserIdAndGameId($userRow['user_id'], $gameId) : null;

        if (!$gameProfile) {
            return new LoginOutcome($base + [
                'game' => $gameSlug,
                'destination' => LoginDestination::NEEDS_GAME_ACCOUNT,
            ]);
        }

        // TODO: session key still keyed by game (lol_id/valorant_id), see
        // UserGamesController::setGameSessionId - same transition-period shim.
        match ($gameSlug) {
            GameSlug::LEAGUE_OF_LEGENDS->value => $_SESSION['lol_id'] = $gameProfile['ug_id'],
            GameSlug::VALORANT->value => $_SESSION['valorant_id'] = $gameProfile['ug_id'],
            default => null,
        };

        $lookingForRow = $this->userLookingForGames->getLookingForGameByUserIdAndGameId($userRow['user_id'], $gameId);

        if (!$lookingForRow) {
            return new LoginOutcome($base + [
                'game' => $gameSlug,
                'gameProfile' => $gameProfile,
                'destination' => LoginDestination::NEEDS_LOOKING_FOR,
            ]);
        }

        $_SESSION['lf_id'] = $lookingForRow['lfg_id'];

        return new LoginOutcome($base + [
            'game' => $gameSlug,
            'gameProfile' => $gameProfile,
            'lookingForRow' => $lookingForRow,
            'destination' => LoginDestination::ONBOARDED,
        ]);
    }
}
