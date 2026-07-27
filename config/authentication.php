<?php declare(strict_types=1);

/**
 * Configuration for the OIDC Authentication middleware.
 *
 * @see https://github.com/nathanskky/guild-access#configuration
 */

use Guild\Access\Authentication\OIDC\OidcConfiguration;

return new OidcConfiguration(
    providerUrl:  $_ENV['OIDC_ISSUER'], // e.g. 'https://idp.login.iu.edu'
    clientId:     $_ENV['OIDC_CLIENT_ID'],
    clientSecret: $_ENV['OIDC_CLIENT_SECRET'],
    redirectUri:  $_ENV['OIDC_REDIRECT_URI'], // e.g. 'https://your-app.webapps.iu.edu/signin-oidc'
    scopes:       ['profile', 'email'],
);
