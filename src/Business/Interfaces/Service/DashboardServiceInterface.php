<?php

namespace DemoShop\src\Business\Interfaces\Service;

interface DashboardServiceInterface
{
    /**
     * Returns dashboard data
     *
     * @return int[]
     */
    public function getDashboardData(): array;
}