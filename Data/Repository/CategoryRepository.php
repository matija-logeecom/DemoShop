<?php

namespace DemoShop\Data\Repository;

use DemoShop\Data\Model\Category;

class CategoryRepository
{
    public function addCategory(array $data): bool
    {
        $newCategory = new Category();

        $newCategory->title = $data['title'];
        $newCategory->parent = $data['parent'];
        $newCategory->code = $data['code'];
        $newCategory->description = $data['description'];


        return $newCategory->save();
    }

    public function getCategories(): array
    {
        return Category::orderBy('title')
            ->get()
            ->toArray();
    }
}