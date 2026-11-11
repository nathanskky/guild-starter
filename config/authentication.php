<?php

declare(strict_types=1);

/**
 * Configuration for the OIDC Authentication middleware.
 *
 * @see https://github.com/nathanskky/guild-access#configuration
 */

use Guild\Access\Authentication\OIDC\OidcConfiguration;
use Guild\Framework\Exception\ConfigurationException;

// This file is read only when persona sign-in did not take over signing in. With the flag on, that means
// guild/dev-tools is not there to take over (a --no-dev install leaves it out), so say so rather than fail
// on IU Login settings a persona setup never has. The flag is read as guild/dev-tools reads it.
$personaFlag = getenv('PERSONA_SIGN_IN_ENABLED');
$personaFlag = $personaFlag === false ? ($_ENV['PERSONA_SIGN_IN_ENABLED'] ?? '') : $personaFlag;
$iuLoginSettings = [$_ENV['OIDC_ISSUER'] ?? '', $_ENV['OIDC_CLIENT_ID'] ?? '', $_ENV['OIDC_CLIENT_SECRET'] ?? '', $_ENV['OIDC_REDIRECT_URI'] ?? ''];

if (
    in_array(strtolower(trim((string) $personaFlag)), ['true', '1'], true)
    && in_array('', array_map(static fn (mixed $value): string => trim((string) $value), $iuLoginSettings), true)
) {
    throw new ConfigurationException(
        'PERSONA_SIGN_IN_ENABLED is on, but persona sign-in is not running, so the application fell back to IU Login, '
        . 'whose OIDC_* settings are not set. guild/dev-tools is probably not installed: run composer install '
        . 'without --no-dev, or switch PERSONA_SIGN_IN_ENABLED off to use IU Login.'
    );
}

return new OidcConfiguration(
    providerUrl:  $_ENV['OIDC_ISSUER'], // e.g. 'https://idp.login.iu.edu'
    clientId:     $_ENV['OIDC_CLIENT_ID'],
    clientSecret: $_ENV['OIDC_CLIENT_SECRET'],
    redirectUri:  $_ENV['OIDC_REDIRECT_URI'], // e.g. 'https://your-app.webapps.iu.edu/signin-oidc'
    scopes:       ['profile', 'email'],
);
