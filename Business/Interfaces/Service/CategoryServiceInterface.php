<?php

namespace DemoShop\Business\Interfaces\Service;

use DemoShop\Business\Exception\InvalidCategoryDataException;
use DemoShop\Business\Exception\MissingCategoryFieldException;
use DemoShop\Business\Model\Category;
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