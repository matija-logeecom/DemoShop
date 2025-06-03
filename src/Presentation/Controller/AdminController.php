<?php

namespace DemoShop\Presentation\Controller;

use DemoShop\Business\Interfaces\Service\DashboardServiceInterface;
use DemoShop\Infrastructure\DI\ServiceRegistry;
use DemoShop\Infrastructure\Response\HtmlResponse;
use DemoShop\Infrastructure\Response\JsonResponse;
use DemoShop\Infrastructure\Response\Response;
use Exception;
use RuntimeException;

/*
 * Stores logic for handling Admin requests
 */

class AdminController
{
    private DashboardServiceInterface $dashboardService;

    /**
     * Constructs Admin Controller instance
     */
    public function __construct()
    {
        try {
            $this->dashboardService = ServiceRegistry::get(DashboardServiceInterface::class);
        } catch (Exception $e) {
            error_log("CRITICAL: AdminController could not be initialized.
             Failed to get DashboardService service. Original error: " . $e->getMessage());
            throw new RuntimeException(
                "AdminController failed to initialize due to a
                 missing critical dependency.",
                0, $e
            );
        }
    }

    public function adminPage(): Response
    {
        $path = VIEWS_PATH . '/admin.phtml';

        return new HtmlResponse($path);
    }

    /**
     * Handles request for dashboard data
     *
     * @return Response
     */
    public function dashboardData(): Response
    {
        $data = $this->dashboardService->getDashboardData();

        return new JsonResponse($data, 200);
    }
}
