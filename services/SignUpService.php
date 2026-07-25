<?php

// Creates a brand-new `googleuser` identity and starts its session, shared by
// Riot/Discord/Google's "no account yet" signup paths.

namespace services;

use models\GoogleUser;

class SignUpService
{
    private GoogleUser $googleUser;
    private MasterTokenService $masterTokenService;

    public function __construct(GoogleUser $googleUser, MasterTokenService $masterTokenService)
    {
        $this->googleUser = $googleUser;
        $this->masterTokenService = $masterTokenService;
    }

    /**
     * Creates a brand-new identity row in `googleuser` and starts its web
     * session: cycles the session (fresh cookie lifetime), mints a master
     * token, writes the auth_token cookie, and populates $_SESSION. Returns
     * null if the identity row itself failed to insert.
     */
    public function createWebIdentity(
        string $providerId,
        string $fullName,
        string $firstName,
        string $lastName,
        string $email,
        int $rsoFlag
    ): ?LoginOutcome {
        $googleUserId = $this->googleUser->createGoogleUser($providerId, $fullName, $firstName, $lastName, $rsoFlag, $email);

        if (!$googleUserId) {
            return null;
        }

        $identityRow = [
            'google_userId' => $googleUserId,
            'google_id' => $providerId,
            'google_fullName' => $fullName,
            'google_firstName' => $firstName,
            'google_lastName' => $lastName,
            'google_email' => $email,
        ];

        $lifetime = 7 * 24 * 60 * 60;
        session_destroy();
        session_set_cookie_params($lifetime);
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $createToken = $this->googleUser->storeMasterTokenWebsite($googleUserId, $token);

        $masterToken = '';
        if ($createToken) {
            $masterToken = $token;
            $_SESSION['masterTokenWebsite'] = $token;
            $this->masterTokenService->writeAuthCookie($token);
        }

        $_SESSION['google_userId'] = $googleUserId;
        $_SESSION['google_id'] = $providerId;
        $_SESSION['email'] = $email;
        $_SESSION['full_name'] = $fullName;
        $_SESSION['google_firstName'] = $firstName;

        return new LoginOutcome([
            'newUser' => true,
            'userExists' => false,
            'identityRow' => $identityRow,
            'masterToken' => $masterToken,
        ]);
    }

    /**
     * Creates a brand-new identity row and mints its mobile master token.
     * Unlike createWebIdentity(), there's no $_SESSION/cookie/session-cycling
     * involved - the mobile app carries the returned token itself. Returns
     * null if the identity row itself failed to insert.
     */
    public function createMobileIdentity(
        string $providerId,
        string $fullName,
        string $firstName,
        string $lastName,
        string $email,
        int $rsoFlag
    ): ?LoginOutcome {
        $googleUserId = $this->googleUser->createGoogleUser($providerId, $fullName, $firstName, $lastName, $rsoFlag, $email);

        if (!$googleUserId) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $this->googleUser->storeMasterToken($googleUserId, $token);

        $identityRow = [
            'googleId' => $providerId,
            'fullName' => $fullName,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
            'googleUserId' => $googleUserId,
            'token' => $token,
        ];

        return new LoginOutcome([
            'newUser' => true,
            'userExists' => false,
            'identityRow' => $identityRow,
            'masterToken' => $token,
        ]);
    }
}
