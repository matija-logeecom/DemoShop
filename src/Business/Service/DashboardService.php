<?php

namespace DemoShop\Business\Service;

use DemoShop\Business\Interfaces\Service\DashboardServiceInterface;

class DashboardService implements DashboardServiceInterface
{
    /**
     * @inheritDoc
     */
    public function getDashboardData(): array
    {
        return [
            'productsCount' => 120,
            'categoriesCount' => 15,
            'homePageOpeningCount' => 50,
            'mostOftenViewedProduct' => 'prod 1',
            'numberOfProd1Views' => 32
        ];
    }
}