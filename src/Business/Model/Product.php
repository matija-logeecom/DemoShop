<?php

namespace DemoShop\Business\Model;

class Product
{
    private string $sku;
    private string $title;
    private ?string $brand; // Optional
    private int $categoryId;
    private float $price;
    private ?string $shortDescription; // Optional
    private ?string $description;    // Optional
    private ?array $imageFileInfo;  // Optional, raw $_FILES['image'] entry
    private bool $isEnabled;
    private bool $isFeatured;

    /**
     * @param string $sku
     * @param string $title
     * @param int $categoryId
     * @param float $price
     * @param string|null $brand
     * @param string|null $shortDescription
     * @param string|null $description
     * @param array|null $imageFileInfo Raw $_FILES entry for the image
     * @param bool $isEnabled
     * @param bool $isFeatured
     */
    public function __construct(
        string $sku,
        string $title,
        int $categoryId,
        float $price,
        ?string $brand,
        ?string $shortDescription,
        ?string $description,
        ?array $imageFileInfo,
        bool $isEnabled,
        bool $isFeatured
    ) {
        $this->sku = $sku;
        $this->title = $title;
        $this->brand = $brand;
        $this->categoryId = $categoryId;
        $this->price = $price;
        $this->shortDescription = $shortDescription;
        $this->description = $description;
        $this->imageFileInfo = $imageFileInfo;
        $this->isEnabled = $isEnabled;
        $this->isFeatured = $isFeatured;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Returns the raw file information array (e.g., from $_FILES).
     * @return array|null
     */
    public function getImageFileInfo(): ?array
    {
        return $this->imageFileInfo;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }
}