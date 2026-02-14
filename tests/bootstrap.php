<?php

/**
 * PHPUnit Bootstrap
 * 
 * Sets up the environment for running tests:
 * - Loads Composer autoloader
 * - Sets up $_ENV defaults so controllers don't crash
 * - Starts a session (suppressing headers-already-sent)
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Provide minimal $_ENV so controllers/traits don't blow up
$_ENV['environment'] = 'test';
$_ENV['db_server'] = 'localhost';
$_ENV['db_name'] = 'ursgpoo_test';
$_ENV['db_user'] = 'root';
$_ENV['db_password'] = '';
$_ENV['STRIPE_SECRET_KEY'] = 'sk_test_fake';
$_ENV['STRIPE_WEBHOOK_SECRET'] = 'whsec_fake';
$_ENV['STRIPE_PRICE_ASCEND'] = 'price_fake';

// Suppress session_start warnings in CLI
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
