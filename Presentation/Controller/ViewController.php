<?php

namespace DemoShop\Presentation\Controller;

use DemoShop\Infrastructure\Response\HtmlResponse;
use DemoShop\Infrastructure\Response\Response;
use DemoShop\Infrastructure\Request\Request;

class ViewController
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

}
