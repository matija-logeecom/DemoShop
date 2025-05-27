<?php

namespace DemoShop\Business\Service;

interface CategoryServiceInterface
{
    /**
     * Adds category to database
     *
     * @param array $data
     *
     * @return bool
     */
    public function createCategory(array $data): bool;

    /**
     * Returns all categories from database
     *
     * @return array
     */
    public function getCategories(): array;

    /**
     * Updates a category
     *
     * @param array $data
     *
     * @return bool
     */
    public function updateCategory(array $data): bool;

    /**
     * Deletes a category
     *
     * @param int $id
     *
     * @return bool
     */
    public function deleteCategory(int $id): bool;

}