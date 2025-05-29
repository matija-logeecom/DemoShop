<?php

namespace DemoShop\Business\Interfaces\Repository;

use DemoShop\Business\Model\Category;
use RuntimeException;

interface CategoryRepositoryInterface
{
    /**
     * Adds category to database
     *
     * @param Category $category
     *
     * @return bool
     */
    public function addCategory(Category $category): bool;

    /**
     * Returns all categories from database
     *
     * @return array
     */
    public function getCategories(): array;

    /**
     * Updates a category in database
     *
     * @param Category $category
     *
     * @return bool
     */
    public function updateCategory(Category $category): bool;

    /**
     * Deletes a category from database
     *
     * @param int $id
     *
     * @return bool
     */
    public function deleteCategory(int $id): bool;

    /**
     * Finds a category by its code.
     *
     * @param string $code
     * @return Category|null Returns the category if found, null otherwise.
     * @throws RuntimeException If a database error occurs.
     */
    public function findByCode(string $code): ?Category;

    /**
     * Finds a category by its title.
     *
     * @param string $title
     * @return Category|null Returns the category if found, null otherwise.
     * @throws RuntimeException If a database error occurs.
     */
    public function findByTitle(string $title): ?Category;

    /**
     * Finds a category by its ID.
     *
     * @param int $id
     * @return Category|null Returns the category business model if found, null otherwise.
     * @throws RuntimeException If a database error occurs.
     */
    public function findById(int $id): ?Category;
}