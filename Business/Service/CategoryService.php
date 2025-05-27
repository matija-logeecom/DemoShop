<?php

namespace DemoShop\Business\Service;

use DemoShop\Data\Repository\CategoryRepository;

class CategoryService implements CategoryServiceInterface
{
    private CategoryRepository $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @inheritDoc
     */
    public function createCategory(array $data): bool
    {
        return $this->categoryRepository->addCategory($data);
    }

    /**
     * @inheritDoc
     */
    public function getCategories(): array
    {
        return $this->categoryRepository->getCategories();
    }

    /**
     * @inheritDoc
     */
    public function updateCategory(array $data): bool
    {
        return $this->categoryRepository->updateCategory($data);
    }

    /**
     * @inheritDoc
     */
    public function deleteCategory(int $id): bool
    {
        return $this->categoryRepository->deleteCategory($id);
    }
}