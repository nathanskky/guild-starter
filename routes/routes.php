<?php

declare(strict_types=1);

/**
 * Application routing and middleware configuration.
 *
 * @see https://route.thephpleague.com/6.x/
 * @todo link to example doc(s)
 */

use Guild\Framework\Middleware\RequireSignIn;
use Guild\Framework\Router;
use Guild\Starter\Example\ExampleController;

return static function (Router $router) {
    /**
     * Routes that need a signed-in user take RequireSignIn: IU Login in production,
     * the persona picker locally. A route group can carry it for many routes.
     *
     * Don't add it to the whole router: that also guards the persona picker at
     * /_dev/personas, which would send a guest back to itself.
     *
     * @see https://route.thephpleague.com/6.x/middleware/#defining-middleware
     */
    $router->map('GET', '/', [ExampleController::class, 'index'])->lazyMiddleware(RequireSignIn::class);
};
