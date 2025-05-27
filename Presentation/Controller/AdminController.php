<?php

namespace DemoShop\Presentation\Controller;

use DemoShop\Business\Service\DashboardServiceInterface;
use DemoShop\Infrastructure\Response\JsonResponse;
use DemoShop\Infrastructure\Request\Request;
use DemoShop\Infrastructure\Response\HtmlResponse;
use DemoShop\Infrastructure\Response\Response;

/*
 * Stores logic for handling Admin requests
 */

class AdminController
{
    private DashboardServiceInterface $dashboardService;

    /**
     * Constructs Admin Controller instance
     *
     * @param DashboardServiceInterface $dashboardService
     */
    public function __construct(DashboardServiceInterface $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function adminPage(): Response
    {
        $path = VIEWS_PATH . '/admin.phtml';

        return new HtmlResponse($path);
    }

    /**
     * Handles request for dashboard data
     *
     * @param Request $request
     *
     * @return Response
     */
    public function dashboardData(Request $request): Response
    {
        $data = $this->dashboardService->getDashboardData();

        return new JsonResponse($data, 200, [], true);
    }
}
