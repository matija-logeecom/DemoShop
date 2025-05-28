<?php

namespace DemoShop\Business\Interfaces\Repository;

interface CategoryRepositoryInterface
{
    /**
     * Adds category to database
     *
     * @param array $data
     *
     * @return bool
     */
    public function addCategory(array $data): bool;

    /**
     * Returns all categories from database
     *
     * @return array
     */
    public function getCategories(): array;

    /**
     * Updates a category in database
     *
     * @param array $data
     *
     * @return bool
     */
    public function updateCategory(array $data): bool;

    /**
     * Deletes a category from database
     *
     * @param int $id
     *
     * @return bool
     */
    public function deleteCategory(int $id): bool;
}