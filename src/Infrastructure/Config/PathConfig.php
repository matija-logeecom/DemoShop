<?php

namespace DemoShop\Infrastructure\Config;

class PathConfig
{
    private string $productImagePhysicalPath;
    private string $productImageUrlBase;

    public function __construct(string $physicalPath, string $urlBase)
    {
        $this->productImagePhysicalPath = rtrim($physicalPath, '/');
        $this->productImageUrlBase = rtrim($urlBase, '/');
    }

    public function getProductImagePhysicalPath(): string
    {
        return $this->productImagePhysicalPath;
    }

    public function getProductImageUrlBase(): string
    {
        return $this->productImageUrlBase;
    }
}