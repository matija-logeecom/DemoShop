<?php

namespace DemoShop\Data\Repository;

use DemoShop\Data\Model\Category;
use Illuminate\Support\Facades\DB;
use Exception;

class CategoryRepository
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
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    public function getCategories(): array
    {
        try {
            return Category::orderBy('title')
                ->get()
                ->toArray();
        } catch (Exception $e) {
            echo $e->getMessage();
            return [];
        }

    }

    public function updateCategory(array $data): bool
    {
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
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
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
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
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
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
