<?php

declare(strict_types=1);

namespace Guild\Starter\Example;

use Guild\Framework\View;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ServerRequestInterface;

readonly class ExampleController
{
    public function __construct(private View $view)
    {
    }

    public function index(ServerRequestInterface $request): HtmlResponse
    {
        $html = $this->view->render('example/index.html.twig', [
            'title' => 'Example Page',
            'message' => 'This is an example page rendered with Twig.',
        ]);

        return new HtmlResponse($html);
    }
}
