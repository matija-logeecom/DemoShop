<?php

namespace DemoShop\src\Business\Interfaces\Repository;

use DemoShop\src\Business\Model\Category;
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
     *
     * @return Category|null
     *
     * @throws RuntimeException
     */
    public function findByCode(string $code): ?Category;

    /**
     * Finds a category by its title.
     *
     * @param string $title
     *
     * @return Category|null
     *
     * @throws RuntimeException
     */
    public function findByTitle(string $title): ?Category;

    /**
     * Finds a category by its ID.
     *
     * @param int $id
     *
     * @return Category|null
     *
     * @throws RuntimeException
     */
    public function findById(int $id): ?Category;

    /**
     * Finds all direct child categories based on the parent's title.
     *
     * @param string $parentTitle
     *
     * @return Category[]
     *
     * @throws RuntimeException
     */
    public function findDirectChildrenByParentTitle(string $parentTitle): array;
}