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
    // Authorization: resolves each user's Grouper groups to roles and permissions, and serves the
    // administration pages at /framework/authorization. Off by default, because it needs an identity
    // source (CAS in public/.htaccess, or ->addAuthentication() with IdentitySource::Oidc), the
    // Grouper settings config/authorization.php reads, and the framework's migrations.
    // See AGENTS.md, "Authorization".
    // ->addAuthorization(IdentitySource::Cas, AppPermission::class)
    ->addTemplateEngine(TemplateEngine::Twig)
    ->addRivet(require __DIR__ . '/rivet.php')
    ->enableAutoWiring()
    ->create();
