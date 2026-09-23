<?php

declare(strict_types=1);

/**
 * Configuration for addAuthorization(): everything that varies by deployment.
 * Read only when addAuthorization() is called in config/app.php.
 *
 * GROUPER_PASSWORD comes from your shell (see docker/docker-compose.yml), never
 * from a file. A blank GROUPER_STEM falls back to the ACM default rather than
 * meaning "unscoped", which would be an institution-wide lookup of several
 * seconds per user.
 *
 * Membership TTL, stale cap and Grouper timeouts keep their defaults here; see
 * AuthorizationConfiguration to change them.
 */

use Guild\Framework\Authorization\AuthorizationConfiguration;
use Guild\Grouper\GrouperConfiguration;

return new AuthorizationConfiguration(
    grouper: new GrouperConfiguration(
        serviceUrl: $_ENV['GROUPER_SERVICE_URL'] ?? '',
        username:   $_ENV['GROUPER_USERNAME'] ?? '',
        password:   $_ENV['GROUPER_PASSWORD'] ?? '',
        stem:       trim($_ENV['GROUPER_STEM'] ?? '') ?: GrouperConfiguration::ACM_STEM,
    ),
    // The ACM label of the group whose members administer this application.
    systemAdminGroup: $_ENV['AUTHORIZATION_SYSTEM_ADMIN_GROUP'] ?? '',
);
