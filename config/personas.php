<?php

declare(strict_types=1);

/**
 * The personas a developer or a test can sign in as, when persona sign-in is on
 * (docker/up-personas.sh, and the tests). Read only then; production never reads it.
 *
 * Groups name real Grouper system names and ACM labels, so the grants below are the
 * ones production would need. See AGENTS.md, "Personas".
 *
 * @see https://github.com/nathanskky/guild-dev-tools#the-roster-configpersonasphp
 */

use Guild\DevTools\Persona\DirectoryStatus;
use Guild\DevTools\Persona\Persona;
use Guild\DevTools\Persona\PersonaGroup;
use Guild\DevTools\Persona\PersonaRoster;
use Guild\DevTools\Persona\RoleGrant;
use Guild\Starter\Authorization\AppPermission;

$editors = new PersonaGroup('iu:roles:sys:acm:starter-editors', 'Starter Editors');
$admins = new PersonaGroup('iu:roles:sys:acm:starter-admins', 'Starter Admins');

return new PersonaRoster(
    personas: [
        new Persona('editor', username: 'persona-editor', name: 'Erin Editor', groups: [$editors]),
        new Persona('newcomer', username: 'persona-newcomer', name: 'Nico Newcomer'),
        new Persona('admin', username: 'persona-admin', name: 'Ada Admin', groups: [$admins]),
        new Persona('outage', username: 'persona-outage', name: 'Grouper Down', directory: DirectoryStatus::Unavailable),
    ],
    grants: [
        new RoleGrant('Editor', groups: [$editors], permissions: [AppPermission::ExampleView]),
    ],
    systemAdminGroup: $admins,
);
