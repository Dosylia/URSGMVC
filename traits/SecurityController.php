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

    public function isConnectLeagueLf()
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
}

