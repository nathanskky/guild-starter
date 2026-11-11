<?php

declare(strict_types=1);

/**
 * Configuration for addAuthorization(): everything that varies by deployment.
 * How to reach Grouper is in config/grouper.php.
 *
 * A blank System Admin label fails at startup, unless persona sign-in supplies
 * it from the roster's systemAdminGroup in config/personas.php.
 *
 * Membership TTL and stale cap keep their defaults here; see
 * AuthorizationConfiguration to change them.
 */

use Guild\Framework\Authorization\AuthorizationConfiguration;

return new AuthorizationConfiguration(
    // The ACM label of the group whose members administer this application.
    systemAdminGroup: $_ENV['AUTHORIZATION_SYSTEM_ADMIN_GROUP'] ?? '',
);
