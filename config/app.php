<?php

declare(strict_types=1);

/**
 * Application configuration using a (hopefully) fluent Builder.
 *
 * @todo either add plenty of details or make sure to link to related doc(s) for examples
 */

use Guild\Framework\Application;
use Guild\Framework\TemplateEngine;

$basePath = dirname(__FILE__, 2);

return Application::configure($basePath)
    ->addRouting()
    ->addIlluminateDatabase()
    ->addTemplateEngine(TemplateEngine::Twig)
    ->enableAutoWiring()
    ->create();
