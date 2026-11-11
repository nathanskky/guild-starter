<?php

declare(strict_types=1);

/**
 * Simple application startup process.
 *
 * - Set sensible (PHP) defaults
 * - Autoload project dependencies
 * - Build the application and return it fully configured
 *
 * You'll usually never need to bother with this file, except maybe as part of a
 * framework upgrade.
 */

$applicationBasePath = dirname(__FILE__, 2);

// Set sensible (PHP) defaults, before autoloading so they cover every file loaded
ini_set('log_errors', 'On');
if (in_array($_ENV['DISPLAY_ERRORS_ENABLED'] ?? false, [true, 'true', 1, '1'], true)) {
    error_reporting(E_ALL);
    ini_set('display_startup_errors', 'On');
    ini_set('display_errors', 'On');

    // Deprecations raised by dependencies go to the log, not the page. Printed, they come
    // before a redirect's headers and break it: IU Login's OIDC client raises them on PHP 8.5.
    // The application's own deprecations are still displayed.
    $vendorPath = $applicationBasePath . '/vendor/';
    set_error_handler(static function (int $level, string $message, string $file, int $line) use ($vendorPath): bool {
        if (! str_starts_with($file, $vendorPath)) {
            return false;
        }
        error_log(sprintf('PHP Deprecated:  %s in %s on line %d', $message, $file, $line));

        return true;
    }, E_DEPRECATED | E_USER_DEPRECATED);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_startup_errors', 'Off');
    ini_set('display_errors', 'Off');
}
date_default_timezone_set($_ENV['DEFAULT_TIMEZONE'] ?? 'UTC');

// Autoload project dependencies
require_once $applicationBasePath . '/vendor/autoload.php';

// Build the application and return it fully configured
$application = require $applicationBasePath . '/config/app.php';
return $application;
