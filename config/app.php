<?php declare(strict_types=1);

/**
 * Application configuration using a (hopefully) fluent Builder.
 *
 * @todo either add plenty of details or make sure to link to related doc(s) for examples
 */

use Guild\Framework\Application;

$basePath = dirname(__FILE__, 2);

return Application::configure($basePath)
    ->addRouting()
    ->addIlluminateDatabase()
    ->enableAutoWiring()
    ->create();
