<?php

namespace DemoShop\Business\Service;

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