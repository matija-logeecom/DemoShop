<?php

namespace DemoShop\Business\Service;

interface DashboardServiceInterface
{
    /**
     * Returns dashboard data
     *
     * @return int[]
     */
    public function getDashboardData(): array;
}