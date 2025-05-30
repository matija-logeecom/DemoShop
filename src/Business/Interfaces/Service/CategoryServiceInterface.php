<?php

namespace DemoShop\src\Business\Interfaces\Service;

use DemoShop\src\Business\Exception\InvalidCategoryDataException;
use DemoShop\src\Business\Model\Category;
use RuntimeException;

interface CategoryServiceInterface
{
    /**
     * Adds category to database
     *
     * @param Category $category
     *
     * @return bool
     *
     * @throws InvalidCategoryDataException
     * @throws RuntimeException
     */
    public function createCategory(Category $category): bool;

    /**
     * Returns all categories from database
     *
     * @return array
     *
     * @throws RuntimeException
     */
    public function getCategories(): array;

    /**
     * Updates a category
     *
     * @param Category $category
     *
     * @return bool
     *
     * @throws InvalidCategoryDataException
     * @throws RuntimeException
     */
    public function updateCategory(Category $category): bool;

    /**
     * Deletes a category
     *
     * @param int $id
     *
     * @return bool
     *
     * @throws RuntimeException
     */
    public function deleteCategory(int $id): bool;
}