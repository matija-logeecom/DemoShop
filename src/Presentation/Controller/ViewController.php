<?php

namespace DemoShop\Presentation\Controller;

use DemoShop\Infrastructure\Response\HtmlResponse;
use DemoShop\Infrastructure\Response\Response;

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
