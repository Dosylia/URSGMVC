<?php

namespace traits;

trait SecurityController
{
    public function isConnectGoogle()
    {
        if(isset($_SESSION['google_id']))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    public function isConnectWebsite()
    {
        if(isset($_SESSION['userId']))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function isConnectLeague()
    {
        if(isset($_SESSION['lol_id']))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function isConnectValorant()
    {
        if(isset($_SESSION['valorant_id']))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function isConnectLf()
    {
        if(isset($_SESSION['lf_id']))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function isAdmin()
    {
        if($_SESSION['userId'] == 157 || $_SESSION['userId'] == 158)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function isModerator()
    {
        if($_SESSION['userId'] == 157 || $_SESSION['userId'] == 158 || $_SESSION['userId'] == 161  || $_SESSION['userId'] == 198)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function isMarketing()
    {
        if($_SESSION['userId'] == 4009)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function getGoogleUserModel()
    {
        throw new \Exception('getGoogleUserModel() not implemented');
    }

    public function validateToken($token, $userId): bool
    {
        $googleUser = $this->getGoogleUserModel();
        $storedTokenData = $googleUser->getMasterTokenByUserId($userId);

        if ($storedTokenData && isset($storedTokenData['google_masterToken'])) {
            $storedToken = $storedTokenData['google_masterToken'];
            return hash_equals($storedToken, $token);
        }
        return false;
    }

    public function validateTokenGoogleUserId($token, $googleUserId): bool
    {
        $googleUser = $this->getGoogleUserModel();
        $storedTokenData = $googleUser->getMasterTokenPhoneByGoogleUserId($googleUserId);

        if ($storedTokenData && isset($storedTokenData['google_masterToken'])) {
            $storedToken = $storedTokenData['google_masterToken'];
            return hash_equals($storedToken, $token);
        }
        return false;
    }

    public function validateTokenWebsite($token, $userId): bool
    {
        $googleUser = $this->getGoogleUserModel();
        $storedTokenData = $googleUser->getMasterTokenWebsiteByUserId($userId);

        if ($storedTokenData && isset($storedTokenData['google_masterTokenWebsite'])) {
            $storedToken = $storedTokenData['google_masterTokenWebsite'];
            return hash_equals($storedToken, $token);
        }
    
        return false;
    }
}

