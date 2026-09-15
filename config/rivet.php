<?php

declare(strict_types=1);

/**
 * Application-wide values the rvt_page layout reads on every page: app title, navigation,
 * footer links.
 *
 * @see https://github.com/nathanskky/guild-rivet#page-layout
 */

use Guild\Rivet\Page\PageDefaults;

return new PageDefaults(
    appTitle: 'Guild Starter',
    navItems: [
        ['label' => 'Home', 'href' => '/', 'current' => true],
    ],
);
