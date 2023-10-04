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
        if(isset($_SESSION['user']))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}