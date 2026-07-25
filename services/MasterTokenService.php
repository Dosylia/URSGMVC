<?php

// Mints/reuses master tokens and writes the auth_token cookie.
// Shared by LogInService (resuming a session) and SignUpService (creating one).

namespace services;

use models\GoogleUser;

class MasterTokenService
{
    private GoogleUser $googleUser;

    public function __construct(GoogleUser $googleUser)
    {
        $this->googleUser = $googleUser;
    }

    /**
     * Returns the identity's existing master token for the given surface
     * (mobile: google_masterToken, web: google_masterTokenWebsite), or mints
     * and persists a new one if none is stored yet.
     */
    public function mintOrReuseToken(array $identityRow, bool $mobile): string
    {
        $column = $mobile ? 'google_masterToken' : 'google_masterTokenWebsite';

        if (!empty($identityRow[$column])) {
            return $identityRow[$column];
        }

        $token = bin2hex(random_bytes(32));

        if ($mobile) {
            $this->googleUser->storeMasterToken($identityRow['google_userId'], $token);
        } else {
            $this->googleUser->storeMasterTokenWebsite($identityRow['google_userId'], $token);
        }

        return $token;
    }

    /**
     * Writes the 60-day auth_token cookie (secure, httponly, SameSite=Strict)
     * used to restore a session on later visits.
     */
    public function writeAuthCookie(string $token): void
    {
        setcookie("auth_token", $token, [
            'expires' => time() + 60 * 60 * 24 * 60,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }
}
