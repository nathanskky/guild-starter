<?php

declare(strict_types=1);

namespace Guild\Starter\Test\Configuration;

use Guild\Access\Authentication\OIDC\Exception\OidcConfigurationException;
use Guild\Access\Authentication\OIDC\OidcConfiguration;
use Guild\Framework\Exception\ConfigurationException;
use PHPUnit\Framework\TestCase;

/**
 * config/authentication.php is read only when persona sign-in did not take over the sign-in method. With
 * the flag on, that means guild/dev-tools is not there to take it over, as after composer install --no-dev.
 */
final class AuthenticationConfigTest extends TestCase
{
    private const array SETTINGS = ['PERSONA_SIGN_IN_ENABLED', 'OIDC_ISSUER', 'OIDC_CLIENT_ID', 'OIDC_CLIENT_SECRET', 'OIDC_REDIRECT_URI'];

    /** @var array<string, array{mixed, string|false}> */
    private array $saved = [];

    protected function setUp(): void
    {
        foreach (self::SETTINGS as $name) {
            $this->saved[$name] = [$_ENV[$name] ?? null, getenv($name)];
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->saved as $name => [$env, $process]) {
            if ($env === null) {
                unset($_ENV[$name]);
            } else {
                $_ENV[$name] = $env;
            }

            putenv($process === false ? $name : "{$name}={$process}");
        }
    }

    public function testPersonaSignInSwitchedOnWithoutDevToolsSaysSo(): void
    {
        $this->environment(personaSignIn: 'true', iuLogin: false);

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'PERSONA_SIGN_IN_ENABLED is on, but persona sign-in is not running, so the application fell back to IU Login, '
            . 'whose OIDC_* settings are not set. guild/dev-tools is probably not installed: run composer install '
            . 'without --no-dev, or switch PERSONA_SIGN_IN_ENABLED off to use IU Login.'
        );

        require dirname(__DIR__, 2) . '/config/authentication.php';
    }

    public function testWithTheFlagOffAMissingSettingIsIuLoginsToReport(): void
    {
        $this->environment(personaSignIn: 'false', iuLogin: false);

        $this->expectException(OidcConfigurationException::class);

        require dirname(__DIR__, 2) . '/config/authentication.php';
    }

    public function testAProductionBuildWithTheFlagLeftOnStillSignsInThroughIuLogin(): void
    {
        $this->environment(personaSignIn: 'true', iuLogin: true);

        self::assertInstanceOf(
            OidcConfiguration::class,
            require dirname(__DIR__, 2) . '/config/authentication.php',
            'the flag alone changes nothing where IU Login is configured, as the --no-dev CI check requires',
        );
    }

    private function environment(string $personaSignIn, bool $iuLogin): void
    {
        $values = [
            'PERSONA_SIGN_IN_ENABLED' => $personaSignIn,
            'OIDC_ISSUER' => $iuLogin ? 'https://idp.login.iu.edu' : '',
            'OIDC_CLIENT_ID' => $iuLogin ? 'client' : '',
            'OIDC_CLIENT_SECRET' => $iuLogin ? 'secret' : '',
            'OIDC_REDIRECT_URI' => $iuLogin ? 'https://app.example.edu/signin-oidc' : '',
        ];

        foreach ($values as $name => $value) {
            $_ENV[$name] = $value;
            putenv("{$name}={$value}");
        }
    }
}
