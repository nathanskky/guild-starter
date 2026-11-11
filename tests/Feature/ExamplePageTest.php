<?php

declare(strict_types=1);

namespace Guild\Starter\Test\Feature;

use Guild\DevTools\Testing\VisitsApplication;
use PHPUnit\Framework\TestCase;

/**
 * The example page as each kind of visitor sees it, signed in as the personas
 * in config/personas.php through the application's real routes, Gate and grants.
 */
final class ExamplePageTest extends TestCase
{
    use VisitsApplication;

    public function testEditorsSeeTheExample(): void
    {
        $this->signedInAs('editor')->get('/')->assertOk()->assertSee('Example Page');
    }

    public function testGuestsAreAskedToSignIn(): void
    {
        $this->asGuest()->get('/')->assertRedirectsToSignIn();
    }

    public function testNewcomersAreForbidden(): void
    {
        $this->signedInAs('newcomer')->get('/')->assertForbidden();
    }

    public function testAGrouperOutageIsReported(): void
    {
        $this->signedInAs('editor')->duringDirectoryOutage()->get('/')->assertServiceUnavailable();
    }

    public function testGuestsCanOpenThePersonaPicker(): void
    {
        $this->asGuest()->get('/_dev/personas')->assertOk()->assertSee('Sign in as Erin Editor');
    }

    public function testOnlySystemAdminsSeeTheSystemSettingsMenu(): void
    {
        $this->signedInAs('admin')->get('/')->assertOk()->assertSee('System settings');
        $this->signedInAs('editor')->get('/')->assertOk()->assertDontSee('System settings');
    }
}
