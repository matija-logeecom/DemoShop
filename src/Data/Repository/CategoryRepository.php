<?php

namespace DemoShop\Data\Repository;

use DemoShop\Business\Exception\ResourceNotFoundException;
use DemoShop\Business\Interfaces\Repository\CategoryRepositoryInterface;
use DemoShop\Business\Model\Category;
use DemoShop\Data\Model\Category as CategoryEntity;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use RuntimeException;

/*
 * Stores logic for CRUD operations with categories
 */

class CategoryRepository implements CategoryRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function addCategory(Category $category): bool
    {
        try {
            $newCategoryEntity = new CategoryEntity();

            $newCategoryEntity->title = $category->getTitle();
            $newCategoryEntity->parent = $category->getParent();
            $newCategoryEntity->code = $category->getCode();
            $newCategoryEntity->description = $category->getDescription();

            return $newCategoryEntity->save();
        } catch (QueryException $e) {
            error_log('addCategory: Database query failed. Error: ' . $e->getMessage());
            throw new RuntimeException('Failed to add category due to a database error', 0, $e);
        } catch (Exception $e) {
            error_log('addCategory: An unexpected error occurred. Error: ' . $e->getMessage());
            throw new RuntimeException('An unexpected error occurred while adding new category', 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function getCategories(): array
    {
        try {
            return CategoryEntity::orderBy('title')
                ->get()
                ->toArray();
        } catch (QueryException $e) {
            error_log('getCategory: Database query failed. Error: ' . $e->getMessage());
            throw new RuntimeException('Failed to retrieve categories due to a database error', 0, $e);
        } catch (Exception $e) {
            error_log('getCategory: An unexpected error occurred. Error: ' . $e->getMessage());
            throw new RuntimeException('An unexpected error occurred while retrieving categories', 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function updateCategory(Category $category): bool
    {
        $id = $category->getId();

        try {
            $categoryEntity = CategoryEntity::findOrFail($id);

            $oldTitle = $categoryEntity->title;

            $categoryEntity->title = $category->getTitle();
            $categoryEntity->parent = $category->getParent();
            $categoryEntity->code = $category->getCode();
            $categoryEntity->description = $category->getDescription();

            $categoryEntity->save();

            if ($oldTitle !== $categoryEntity->title) {
                CategoryEntity::where('parent', $oldTitle)
                    ->update(['parent' => $categoryEntity->title]);
            }

            return true;
        } catch (ModelNotFoundException $e) {
            error_log(
                "CategoryRepository::updateCategory - Category with ID {$id} not found: " . $e->getMessage());
            throw new ResourceNotFoundException("Category with ID {$id} not found for update.", 0, $e);
        } catch (QueryException $e) {
            error_log('updateCategory: Database query failed. Error: ' . $e->getMessage());
            throw new RuntimeException("Failed to update category {$id} due to a database error", 0, $e);
        } catch (Exception $e) {
            error_log('updateCategory: An unexpected error occurred. Error: ' . $e->getMessage());
            throw new RuntimeException(
                "An unexpected error occurred while updating category {$id}", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteCategory(int $id): bool
    {
        try {
            $categoryToDelete = CategoryEntity::findOrFail($id);
            $parentDeleted = $categoryToDelete->title;

            $this->deleteDescendants($parentDeleted);
            $categoryToDelete->delete();

            return true;
        } catch (ModelNotFoundException $e) {
            error_log(
                "CategoryRepository::deleteCategory - Category with ID {$id} not found: " . $e->getMessage());
            throw new ResourceNotFoundException("Category with ID {$id} not found for deletion.", 0, $e);
        } catch (QueryException $e) {
            error_log("
            deleteCategory - Database query failed while deleting category ID {$id}: " . $e->getMessage());
            throw new RuntimeException(
                "Failed to delete category ID {$id} due to a database error.", 0, $e);
        } catch (Exception $e) {
            error_log("deleteCategory - An unexpected error occurred for ID {$id}: " . $e->getMessage());
            throw new RuntimeException(
                "An unexpected error occurred while deleting category ID {$id}.", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function findByCode(string $code): ?Category
    {
        try {
            $categoryEntity = CategoryEntity::where('code', $code)->first();
            return $this->mapEloquentToBusinessModel($categoryEntity);
        } catch (QueryException $e) {
            error_log("CategoryRepository::findByCode - " .
                "DB query failed for code '{$code}'. Error: " . $e->getMessage());
            throw new RuntimeException("Database error while finding category by code.", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function findByTitle(string $title): ?Category
    {
        try {
            $categoryEntity = CategoryEntity::where('title', $title)->first();
            return $this->mapEloquentToBusinessModel($categoryEntity);
        } catch (QueryException $e) {
            error_log("CategoryRepository::findByTitle - 
            DB query failed for title '{$title}'. Error: " . $e->getMessage());
            throw new RuntimeException("Database error while finding category by title.", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function findById(int $id): ?Category
    {
        try {
            $eloquentCategory = CategoryEntity::find($id);
            return $this->mapEloquentToBusinessModel($eloquentCategory);
        } catch (QueryException $e) {
            error_log("CategoryRepository::findById - 
            DB query failed for ID '{$id}'. Error: " . $e->getMessage());
            throw new RuntimeException("Database error while finding category by ID.", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function findDirectChildrenByParentTitle(string $parentTitle): array
    {
        try {
            $eloquentChildren = CategoryEntity::where('parent', $parentTitle)
                ->orderBy('title')
                ->get();

            $businessModelChildren = [];
            foreach ($eloquentChildren as $eloquentChild) {
                $mappedChild = $this->mapEloquentToBusinessModel($eloquentChild);
                if ($mappedChild) {
                    $businessModelChildren[] = $mappedChild;
                }
            }
            return $businessModelChildren;
        } catch (QueryException $e) {
            error_log("CategoryRepository::findDirectChildrenByParentTitle - " .
                "DB query failed for parent title '{$parentTitle}'. Error: " . $e->getMessage());
            throw new RuntimeException(
                "Database error while finding child categories by parent title.", 0, $e);
        } catch (Exception $e) {
            error_log("CategoryRepository::findDirectChildrenByParentTitle - " .
                "Unexpected error for parent title '{$parentTitle}'. Error: " . $e->getMessage());
            throw new RuntimeException(
                "An unexpected error occurred while finding child categories.", 0, $e);
        }
    }

    /**
     * Converts Eloquent to DTO model
     *
     * @param CategoryEntity|null $categoryEntity
     *
     * @return Category|null
     */
    private function mapEloquentToBusinessModel(?CategoryEntity $categoryEntity): ?Category
    {
        if (!$categoryEntity) {
            return null;
        }

        return new Category(
            title: $categoryEntity->title,
            code: $categoryEntity->code,
            parent: $categoryEntity->parent,
            description: $categoryEntity->description,
            id: $categoryEntity->id
        );
    }

    /**
     * Deletes all descendants of category
     *
     * @param string $parent
     *
     * @return void
     */
    private function deleteDescendants(string $parent): void
    {
        try {
            $children = CategoryEntity::where('parent', $parent)->get();

            if ($children->isEmpty()) {
                return;
            }

            foreach ($children as $child) {
                $this->deleteDescendants($child->title);
                $child->delete();
            }
        } catch (QueryException $e) {
            error_log('deleteDescendants - Database query failed. Error: ' . $e->getMessage());
            throw new RuntimeException('Failed to delete descendants.', 0, $e);
        } catch (Exception $e) {
            error_log('deleteDescendants - An unexpected error occurred. Error: ' . $e->getMessage());
            throw new RuntimeException(
                'An unexpected error occurred while tryting to delete descendants.', 0, $e);
        }
    }
}
