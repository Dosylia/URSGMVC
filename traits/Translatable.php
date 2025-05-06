<?php

namespace traits;

trait Translatable
{
    protected $translations = [];

    public function loadLanguage($lang)
    {
        // Get the root directory of your project
        $rootDir = $_SERVER['DOCUMENT_ROOT'];  // Or use the appropriate root directory

        // Construct the relative path to the lang directory
        $path = $rootDir . '/lang/' . $lang . '.php';  // Assuming lang/ is in the root directory

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
}
