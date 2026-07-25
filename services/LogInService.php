<?php

// Resumes a session for an already-existing `googleuser` identity, shared by
// Riot/Discord/Google's "user already has an account" login paths.

namespace services;

use models\User;
use models\LeagueOfLegends;
use models\Valorant;
use models\UserLookingFor;

class LogInService
{
    private MasterTokenService $masterTokenService;
    private User $user;
    private LeagueOfLegends $leagueOfLegends;
    private Valorant $valorant;
    private UserLookingFor $userLookingFor;

    public function __construct(
        MasterTokenService $masterTokenService,
        User $user,
        LeagueOfLegends $leagueOfLegends,
        Valorant $valorant,
        UserLookingFor $userLookingFor
    ) {
        $this->masterTokenService = $masterTokenService;
        $this->user = $user;
        $this->leagueOfLegends = $leagueOfLegends;
        $this->valorant = $valorant;
        $this->userLookingFor = $userLookingFor;
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
     * mobile surface: same League/Valorant + looking-for branching, but
     * builds camelCase profile arrays instead of raw DB rows and never
     * touches $_SESSION.
     */
    private function resolveMobileOnboardingDestination(array $googleUserData, array $userData, array $userRow, string $token): LoginOutcome
    {
        $base = [
            'userExists' => true,
            'identityRow' => $googleUserData,
            'userRow' => $userData,
            'masterToken' => $token,
        ];

        if ($userRow['user_game'] === 'League of Legends') {
            $game = 'League of Legends';
            $lolUser = $this->leagueOfLegends->getLeageUserByUserId($userRow['user_id']);

            if (!$lolUser) {
                return new LoginOutcome($base + ['game' => $game, 'destination' => LoginDestination::NEEDS_GAME_ACCOUNT]);
            }

            $gameProfile = [
                'lolId' => $lolUser['lol_id'],
                'main1' => $lolUser['lol_main1'],
                'main2' => $lolUser['lol_main2'],
                'main3' => $lolUser['lol_main3'],
                'rank' => $lolUser['lol_rank'],
                'role' => $lolUser['lol_role'],
                'server' => $lolUser['lol_server'],
                'account' => $lolUser['lol_account'],
                'sUsername' => $lolUser['lol_sUsername'],
                'sLevel' => $lolUser['lol_sLevel'],
                'sRank' => $lolUser['lol_sRank'],
                'sProfileIcon' => $lolUser['lol_sProfileIcon'],
                'skipSelectionLol' => $lolUser['lol_noChamp'],
            ];

            $lfUser = $this->userLookingFor->getLookingForUserByUserId($userRow['user_id']);

            if (!$lfUser) {
                return new LoginOutcome($base + ['game' => $game, 'gameProfile' => $gameProfile, 'destination' => LoginDestination::NEEDS_LOOKING_FOR]);
            }

            $lookingForData = [
                'lfId' => $lfUser['lf_id'],
                'lfGender' => $lfUser['lf_gender'],
                'lfKingOfGamer' => $lfUser['lf_kindofgamer'],
                'lfGame' => $lfUser['lf_game'],
                'main1Lf' => $lfUser['lf_lolmain1'],
                'main2Lf' => $lfUser['lf_lolmain2'],
                'main3Lf' => $lfUser['lf_lolmain3'],
                'rankLf' => $lfUser['lf_lolrank'],
                'roleLf' => $lfUser['lf_lolrole'],
                'skipSelectionLf' => $lfUser['lf_lolNoChamp'],
                'filteredServerLf' => $lfUser['lf_filteredServer'],
            ];

            return new LoginOutcome($base + ['game' => $game, 'gameProfile' => $gameProfile, 'lookingForRow' => $lookingForData, 'destination' => LoginDestination::ONBOARDED]);
        }

        $game = 'Valorant';
        $valorantUser = $this->valorant->getValorantUserByUserId($userRow['user_id']);

        if (!$valorantUser) {
            return new LoginOutcome($base + ['game' => $game, 'destination' => LoginDestination::NEEDS_GAME_ACCOUNT]);
        }

        $gameProfile = [
            'valorantId' => $valorantUser['valorant_id'],
            'main1' => $valorantUser['valorant_main1'],
            'main2' => $valorantUser['valorant_main2'],
            'main3' => $valorantUser['valorant_main3'],
            'rank' => $valorantUser['valorant_rank'],
            'role' => $valorantUser['valorant_role'],
            'server' => $valorantUser['valorant_server'],
            'skipSelectionVal' => $valorantUser['valorant_noChamp'],
        ];

        $lfUser = $this->userLookingFor->getLookingForUserByUserId($userRow['user_id']);

        if (!$lfUser) {
            return new LoginOutcome($base + ['game' => $game, 'gameProfile' => $gameProfile, 'destination' => LoginDestination::NEEDS_LOOKING_FOR]);
        }

        $lookingForData = [
            'lfId' => $lfUser['lf_id'],
            'lfGender' => $lfUser['lf_gender'],
            'lfKingOfGamer' => $lfUser['lf_kindofgamer'],
            'lfGame' => $lfUser['lf_game'],
            'valmain1Lf' => $lfUser['lf_valmain1'],
            'valmain2Lf' => $lfUser['lf_valmain2'],
            'valmain3Lf' => $lfUser['lf_valmain3'],
            'valrankLf' => $lfUser['lf_valrank'],
            'valroleLf' => $lfUser['lf_valrole'],
            'skipSelectionLf' => $lfUser['lf_valNoChamp'],
            'filteredServerLf' => $lfUser['lf_filteredServer'],
        ];

        return new LoginOutcome($base + ['game' => $game, 'gameProfile' => $gameProfile, 'lookingForRow' => $lookingForData, 'destination' => LoginDestination::ONBOARDED]);
    }

    /**
     * Branches on the linked user's game (League vs Valorant), writes the
     * matching game-account/looking-for session keys, and decides which
     * onboarding step is next: needs a game account, needs a looking-for
     * profile, or fully onboarded.
     */
    private function resolveOnboardingDestination(array $identityRow, array $userRow, string $token): LoginOutcome
    {
        $base = [
            'userExists' => true,
            'identityRow' => $identityRow,
            'userRow' => $userRow,
            'masterToken' => $token,
        ];

        if ($userRow['user_game'] === 'League of Legends') {
            $game = 'League of Legends';
            $gameProfile = $this->leagueOfLegends->getLeageUserByUserId($userRow['user_id']);
        } else {
            $game = 'Valorant';
            $gameProfile = $this->valorant->getValorantUserByUserId($userRow['user_id']);
        }

        if (!$gameProfile) {
            return new LoginOutcome($base + [
                'game' => $game,
                'destination' => LoginDestination::NEEDS_GAME_ACCOUNT,
            ]);
        }

        if ($game === 'League of Legends') {
            $_SESSION['lol_id'] = $gameProfile['lol_id'];
        } else {
            $_SESSION['valorant_id'] = $gameProfile['valorant_id'];
        }

        $lookingForRow = $this->userLookingFor->getLookingForUserByUserId($userRow['user_id']);

        if (!$lookingForRow) {
            return new LoginOutcome($base + [
                'game' => $game,
                'gameProfile' => $gameProfile,
                'destination' => LoginDestination::NEEDS_LOOKING_FOR,
            ]);
        }

        $_SESSION['lf_id'] = $lookingForRow['lf_id'];

        return new LoginOutcome($base + [
            'game' => $game,
            'gameProfile' => $gameProfile,
            'lookingForRow' => $lookingForRow,
            'destination' => LoginDestination::ONBOARDED,
        ]);
    }
}
