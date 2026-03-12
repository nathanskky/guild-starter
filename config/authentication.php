<?php declare(strict_types=1);

/**
 * Configuration for the Authentication middleware provided by the Shadow\Access
 * library.
 *
 * Instantiate a configuration object for the authentication protocol you intend
 * to use (i.e. CAS, SAML, OIDC). Only CAS has an implementation currently; OIDC
 * is planned next.
 *
 * @todo clean up this docblock -- too obtuse
 */

use Shadow\Access\Authentication\CasAuthenticationConfiguration;

// TODO: get (all?) values from environment
$config = new CasAuthenticationConfiguration(
    host: 'idp-stg.login.iu.edu',
    serviceBaseUrl: 'http://localhost',
);

$config->sslValidate = false;
$config->debug = true;

return $config;