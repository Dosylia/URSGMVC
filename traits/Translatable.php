<?php

namespace traits;

trait Translatable
{
    protected $translations = [];

    public function loadLanguage($lang)
    {
        $rootDir = $_ENV['environment'] === 'local' ? dirname(__DIR__) : $_SERVER['DOCUMENT_ROOT'];

        $path = $rootDir . '/lang/' . $lang . '.php'; 

        // Check if the language file exists
        if (file_exists($path)) {
            $this->translations = require $path;
        } else {
            // If the requested language file does not exist, fallback to English
            $fallbackPath = $rootDir . '/lang/en.php';
            if (file_exists($fallbackPath)) {
                $this->translations = require $fallbackPath;
            } else {
                // Handle error if the fallback language file does not exist
                throw new \Exception("Language files not found.");
            }
        }

        return $this->translations;
    }

    public function _($key)
    {
        return $this->translations[$key] ?? $key;
    }

    public function initializeLanguage()
    {
        $allowedLangs = ['en', 'fr', 'de', 'es'];

        if (isset($_GET['lang']) && in_array($_GET['lang'], $allowedLangs)) {
            $lang = $_GET['lang'];
        } elseif (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $allowedLangs)) {
            $lang = $_COOKIE['lang'];
        } elseif (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $allowedLangs)) {
            $lang = $_SESSION['lang'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
            $geo = @json_decode(file_get_contents("http://ip-api.com/json/{$ip}?fields=countryCode"), true);
            $countryCode = $geo['countryCode'] ?? 'US';

            $regionLangMap = [
                'FR' => 'fr',
                'DE' => 'de',
                'ES' => 'es',
                'AT' => 'de',
                'LU' => 'fr',
                'MX' => 'es',
                'AR' => 'es',
            ];

            $lang = $regionLangMap[$countryCode] ?? 'en';
        }

        setcookie('lang', $lang, time() + (7 * 24 * 60 * 60), "/");

        $_SESSION['lang'] = $lang;

        $this->loadLanguage($lang);
    }

}
