<?php

namespace DemoShop\Presentation\Controller;

use DemoShop\Infrastructure\Response\HtmlResponse;
use DemoShop\Infrastructure\Response\Response;
use DemoShop\Infrastructure\Request\Request;

class Controller
{
    /**
     * Makes landing page response
     *
     * @param Request $request
     *
     * @return Response
     */
    public function landingPage(Request $request): Response
    {
        $path = VIEWS_PATH . '/landing.phtml';

        return new HtmlResponse($path);
    }

    /**
     * Makes login page response
     *
     * @param Request $request
     *
     * @return Response
     */
    public function loginPage(Request $request): Response
    {
        $path = VIEWS_PATH . '/login.phtml';

        return new HtmlResponse($path);
    }
}
