<?php 

namespace DemoShop\Presentation\Controller;

use DemoShop\Infrastructure\Response\HtmlResponse;
use DemoShop\Infrastructure\Response\Response;
use DemoShop\Infrastructure\Request\Request;

class Controller
{
    public function landingPage(Request $request): Response
    {
        $path = VIEWS_PATH . '/landing.phtml';
        return new HtmlResponse($path);
    }

    public function loginPage(Request $request): Response
    {
        $path = VIEWS_PATH . '/login.phtml';
        return new HtmlResponse($path);
    }
}