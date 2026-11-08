<?php

namespace App\Core;

/**
 * Native PSR-4 Compliant Autoloader
 * 
 * Automatically loads PHP classes from the App namespace without Composer.
 */
class Autoloader
{
    /**
     * Namespace prefix to map
     * @var string
     */
    private static string $prefix = 'App\\';

    /**
     * Base directory corresponding to the namespace prefix
     * @var string
     */
    private static string $baseDir = '';

    /**
     * Register autoloader with SPL autoload queue
     */
    public static function register(): void
    {
        // Set the base directory to the /app folder
        self::$baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR;

        spl_autoload_register([__CLASS__, 'loadClass']);
    }

    /**
     * Load the class file for a given class name
     *
     * @param string $class
     * @return bool
     */
    public static function loadClass(string $class): bool
    {
        // Does the class use our namespace prefix?
        $len = strlen(self::$prefix);
        if (strncmp(self::$prefix, $class, $len) !== 0) {
            return false;
        }

        // Get relative class name
        $relativeClass = substr($class, $len);

        // Replace namespace separators with directory separators and append .php
        $file = self::$baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return true;
        }

        return false;
    }
}
