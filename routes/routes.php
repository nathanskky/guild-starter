<?php declare(strict_types=1);

/**
 * Application routing and middleware configuration.
 *
 * @see https://route.thephpleague.com/6.x/
 * @todo link to example doc(s)
 */

use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Guild\Access\Authentication\OIDC\OidcAuthenticationMiddleware;
use Guild\Framework\Router;

return static function (Router $router) {
    /**
     * To require authentication for every route, uncomment this line.
     *
     * You can also add authentication -- or any other middleware -- to
     * individual routes and route groups.
     *
     * @see https://route.thephpleague.com/6.x/middleware/#defining-middleware
     */
    // $router->lazyMiddleware(OidcAuthenticationMiddleware::class);

    // An example route.
    $router->map('GET', '/', function (ServerRequestInterface $request): ResponseInterface {
        return new HtmlResponse('<h1>Hello, World!</h1>');
    });
};
