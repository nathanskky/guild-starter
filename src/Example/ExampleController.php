<?php

declare(strict_types=1);

namespace Guild\Starter\Example;

use Guild\Framework\Authorization\Gate;
use Guild\Framework\View;
use Guild\Starter\Authorization\AppPermission;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ServerRequestInterface;

readonly class ExampleController
{
    public function __construct(private View $view, private Gate $gate)
    {
    }

    public function index(ServerRequestInterface $request): HtmlResponse
    {
        $this->gate->authorize(AppPermission::ExampleView);

        $html = $this->view->render('example/index.html.twig');

        return new HtmlResponse($html);
    }
}
