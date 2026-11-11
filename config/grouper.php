<?php

declare(strict_types=1);

/**
 * How to reach Grouper, for addAuthorization() in config/app.php.
 *
 * Read only when the application signs in through IU Login (docker/up-iu-services.sh,
 * and production). Persona sign-in takes groups from config/personas.php and never
 * reads this file.
 *
 * GROUPER_PASSWORD comes from a 1Password Environment (see docker/up-iu-services.sh),
 * never from a file. A blank GROUPER_STEM falls back to the ACM default rather than
 * meaning "unscoped", which would be an institution-wide lookup of several seconds
 * per user.
 *
 * The Grouper timeouts keep their defaults here; see GrouperDirectoryConfiguration
 * to change them.
 */

use Guild\Framework\Authorization\Membership\GrouperDirectoryConfiguration;
use Guild\Grouper\GrouperConfiguration;

return new GrouperDirectoryConfiguration(
    grouper: new GrouperConfiguration(
        serviceUrl: $_ENV['GROUPER_SERVICE_URL'] ?? '',
        username:   $_ENV['GROUPER_USERNAME'] ?? '',
        password:   $_ENV['GROUPER_PASSWORD'] ?? '',
        stem:       trim($_ENV['GROUPER_STEM'] ?? '') ?: GrouperConfiguration::ACM_STEM,
    ),
);
