<?php declare(strict_types=1);

/**
 * Application routing and middleware configuration.
 *
 * @see https://route.thephpleague.com/
 * @todo link to example doc(s)
 */

use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Shadow\Framework\Router;

return static function (Router $router) {
    // An example route. Delete or change as needed.
    $router->map('GET', '/', function (ServerRequestInterface $request): ResponseInterface {
        return new HtmlResponse('<h1>Hello, World!</h1>');
    });
};
