<?php

namespace DemoShop\src\Presentation\Controller;

use DemoShop\src\Infrastructure\Request\Request;
use DemoShop\src\Infrastructure\Response\HtmlResponse;
use DemoShop\src\Infrastructure\Response\Response;

class ViewController
{
    /**
     * Makes landing page response
     *
     * @return Response
     */
    public function landingPage(): Response
    {
        $path = VIEWS_PATH . '/landing.phtml';

        return new HtmlResponse($path);
    }

}
