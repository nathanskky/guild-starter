<?php declare(strict_types=1);

/**
 * Simple application startup process.
 *
 * - Autoload project dependencies
 * - Load needed project values into the environment
 * - Set sensible (PHP) defaults
 * - Build the application and return it fully configured (in separate file)
 *
 * You'll usually never need to bother with this file, except maybe as part of a
 * framework upgrade.
 */

use Dotenv\Dotenv;

$applicationBasePath = dirname(__FILE__, 2);

// Autoload project dependencies
require_once $applicationBasePath . '/vendor/autoload.php';

// Load needed project values into the environment
/* TODO: this should be configurable so that it doesn't happen on AppKube (since
         the environment is already loaded and you reference the values in the
         .env file instead) */
// TODO: eventually account for Hashicorp Vault PHP library implementation
$dotenv = Dotenv::createImmutable($applicationBasePath);
$dotenv->load();

// Set sensible (PHP) defaults
// TODO: redo, make configurable (explicit .env values with prod defaults)
// TODO: find out if AppKube test/prod have these configured appropriately already/have other opinions
ini_set('log_errors', 'On');
if (in_array($_ENV['DEBUG'], [true, 'true', 1, '1'], true)) {
    error_reporting(-1);
    ini_set('display_startup_errors', 'On');
    ini_set('display_errors', 'On');
} else {
    error_reporting(E_ALL & ~E_NOTICE);
    ini_set('display_startup_errors', 'Off');
    ini_set('display_errors', 'Off');
}
date_default_timezone_set($_ENV['DEFAULT_TIMEZONE']);

// Build the application and return it fully configured
$application = require $applicationBasePath . '/config/app.php';
return $application;