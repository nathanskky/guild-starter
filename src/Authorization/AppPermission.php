<?php

declare(strict_types=1);

namespace Guild\Starter\Authorization;

use Guild\Framework\Authorization\Permission;

/**
 * The permissions this application checks, and the only ones its roles can be
 * granted in the framework's administration pages. Each value is the name
 * stored for a grant, so renaming one orphans the grants that use it.
 *
 * Check one in a controller with Gate::authorize(AppPermission::ExampleView),
 * or in a template with can('example.view').
 */
enum AppPermission: string implements Permission
{
    case ExampleView = 'example.view';
}
