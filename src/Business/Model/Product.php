<?php

namespace DemoShop\Business\Model;

class Product
{
    private string $sku;
    private string $title;
    private ?string $brand;
    private int $categoryId;
    private float $price;
    private ?string $shortDescription;
    private ?string $description;
    private ?array $imageFileInfo;
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
     * @param array|null $imageFileInfo
     * @param bool $isEnabled
     * @param bool $isFeatured
     */
    public function __construct(
        string  $sku,
        string  $title,
        int     $categoryId,
        float   $price,
        ?string $brand,
        ?string $shortDescription,
        ?string $description,
        ?array  $imageFileInfo,
        bool    $isEnabled,
        bool    $isFeatured
    )
    {
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

    /**
     * @return string
     */
    public function getSku(): string
    {
        return $this->sku;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string|null
     */
    public function getBrand(): ?string
    {
        return $this->brand;
    }

    /**
     * @return int
     */
    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @return string|null
     */
    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return array|null
     */
    public function getImageFileInfo(): ?array
    {
        return $this->imageFileInfo;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    /**
     * @return bool
     */
    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }
}