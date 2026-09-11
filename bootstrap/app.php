<?php

declare(strict_types=1);

/**
 * Simple application startup process.
 *
 * - Autoload project dependencies
 * - Set sensible (PHP) defaults
 * - Build the application and return it fully configured
 *
 * You'll usually never need to bother with this file, except maybe as part of a
 * framework upgrade.
 */

$applicationBasePath = dirname(__FILE__, 2);

// Autoload project dependencies
require_once $applicationBasePath . '/vendor/autoload.php';

// Set sensible (PHP) defaults
ini_set('log_errors', 'On');
if (in_array($_ENV['DISPLAY_ERRORS_ENABLED'] ?? false, [true, 'true', 1, '1'], true)) {
    error_reporting(E_ALL);
    ini_set('display_startup_errors', 'On');
    ini_set('display_errors', 'On');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_startup_errors', 'Off');
    ini_set('display_errors', 'Off');
}
date_default_timezone_set($_ENV['DEFAULT_TIMEZONE'] ?? 'UTC');

// Build the application and return it fully configured
$application = require $applicationBasePath . '/config/app.php';
return $application;
