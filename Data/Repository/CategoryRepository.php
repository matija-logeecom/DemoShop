<?php

namespace DemoShop\Data\Repository;

use DemoShop\Data\Model\Category;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use RuntimeException;

/*
 * Stores logic for CRUD operations with categories
 */

class CategoryRepository implements \DemoShop\Business\Interfaces\Repository\CategoryRepositoryInterface
{
    public function addCategory(array $data): bool
    {
        try {
            $newCategory = new Category();

            $newCategory->title = $data['title'];
            $newCategory->parent = $data['parent'];
            $newCategory->code = $data['code'];
            $newCategory->description = $data['description'];

            return $newCategory->save();
        } catch (QueryException $e) {
            error_log('addCategory: Database query failed. Error: ' . $e->getMessage());
            throw new RuntimeException('Failed to add category due to a database error', 0, $e);
        } catch (Exception $e) {
            error_log('addCategory: An unexpected error occurred. Error: ' . $e->getMessage());
            throw new RuntimeException('An unexpected error occurred while adding new category', 0, $e);
        }
    }

    public function getCategories(): array
    {
        try {
            return Category::orderBy('title')
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

    public function updateCategory(array $data): bool
    {
        // TODO This check will be performed inside the service
        if (empty($data['id'])) { // Or !isset($data['id'])
            error_log("CategoryRepository::updateCategory - ID is missing from data array. Data received: " . json_encode($data));
            // This is a programming error if the controller/service is supposed to guarantee it.
            throw new RuntimeException("Category ID is required for update and was not provided to the repository.");
        }

        $id = $data['id'];

        try {
            $category = Category::findOrFail($id);

            $oldTitle = $category->title;

            $category->title = $data['title'];
            $category->parent = $data['parent'];
            $category->code = $data['code'];
            $category->description = $data['description'];

            $category->save();

            if ($oldTitle !== $category->title) {
                Category::where('parent', $oldTitle)
                    ->update(['parent' => $category->title]);
            }

            return true;
        } catch (ModelNotFoundException $e) {
            error_log("updateCategory - Category with ID {$id} not found: " . $e->getMessage());
            throw new RuntimeException("Category with ID {$id} not found for update.", 0, $e);
        } catch (QueryException $e) {
            error_log('updateCategory: Database query failed. Error: ' . $e->getMessage());
            throw new RuntimeException("Failed to update category {$id} due to a database error", 0, $e);
        } catch (Exception $e) {
            error_log('updateCategory: An unexpected error occurred. Error: ' . $e->getMessage());
            throw new RuntimeException("An unexpected error occurred while updating category {$id}", 0, $e);
        }
    }

    public function deleteCategory(int $id): bool
    {
        try {
            $categoryToDelete = Category::findOrFail($id);
            $parentDeleted = $categoryToDelete->title;

            $this->deleteDescendants($parentDeleted);
            $categoryToDelete->delete();

            return true;
        } catch (ModelNotFoundException $e) {
            error_log("deleteCategory - Category with ID {$id} not found: " . $e->getMessage());
            throw new RuntimeException("Category with ID {$id} not found for deletion.", 0, $e);
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

    private function deleteDescendants(string $parent): void
    {
        try {
            $children = Category::where('parent', $parent)
                ->get();

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
