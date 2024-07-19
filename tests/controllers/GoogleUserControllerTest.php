<?php

namespace tests\controllers;

use PHPUnit\Framework\TestCase;
use controllers\GoogleUserController;
use models\GoogleUser;
use PHPMailer\PHPMailer\PHPMailer;

class GoogleUserControllerTest extends TestCase
{
    private $googleUserController;

    protected function setUp(): void
    {
        $this->googleUserController = new GoogleUserController();
    }

    
}
