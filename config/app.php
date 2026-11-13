<?php

declare(strict_types=1);

/**
 * Application configuration using a (hopefully) fluent Builder.
 *
 * @todo either add plenty of details or make sure to link to related doc(s) for examples
 */

use Guild\Framework\Application;
use Guild\Framework\Authorization\IdentitySource;
use Guild\Framework\TemplateEngine;
use Guild\Starter\Authorization\AppPermission;

$basePath = dirname(__FILE__, 2);

return Application::configure($basePath)
    ->addRouting()
    ->addIlluminateDatabase()
    // Who is signed in, and which Grouper groups they are in. In production: IU Login
    // (config/authentication.php) and Grouper (config/grouper.php). Locally and in tests,
    // guild/dev-tools replaces both with the personas in config/personas.php when
    // PERSONA_SIGN_IN_ENABLED is on. See
    // https://github.com/nathanskky/guild-dev-tools/blob/main/docs/start/turn-persona-sign-in-on-and-off.md
    ->addAuthentication()
    // Maps groups to roles and permissions, and serves the administration pages at
    // /framework/authorization. See
    // https://github.com/nathanskky/guild-framework/blob/main/docs/guides/set-up-authorization.md
    ->addAuthorization(IdentitySource::Oidc, AppPermission::class)
    ->addTemplateEngine(TemplateEngine::Twig)
    ->addRivet(require __DIR__ . '/rivet.php')
    ->enableAutoWiring()
    ->create();
