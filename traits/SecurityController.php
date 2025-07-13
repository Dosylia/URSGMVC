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
}

