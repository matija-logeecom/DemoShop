<?php

namespace DemoShop\Business\Service;

use DemoShop\Business\Exception\CategoryInUseException;
use DemoShop\Business\Exception\InvalidCategoryDataException;
use DemoShop\Business\Exception\MissingCategoryFieldException;
use DemoShop\Business\Exception\NoChangesMadeException;
use DemoShop\Business\Exception\ResourceNotFoundException;
use DemoShop\Business\Interfaces\Repository\CategoryRepositoryInterface;
use DemoShop\Business\Interfaces\Repository\ProductRepositoryInterface;
use DemoShop\Business\Interfaces\Service\CategoryServiceInterface;
use DemoShop\Business\Model\Category;
use DemoShop\Infrastructure\DI\ServiceRegistry;
use Exception;
use RuntimeException;

class CategoryService implements CategoryServiceInterface
{
    private CategoryRepositoryInterface $categoryRepository;
    private ProductRepositoryInterface $productRepository;

    public function __construct()
    {
        try {
            $this->categoryRepository = ServiceRegistry::get(CategoryRepositoryInterface::class);
            $this->productRepository = ServiceRegistry::get(ProductRepositoryInterface::class);
        } catch (Exception $e) {
            error_log("CRITICAL: CategoryService could not be initialized. " .
                "Failed to get CategoryRepository service. Original error: " . $e->getMessage());
            throw new RuntimeException("CategoryService failed to initialize due to a " .
                "missing critical dependency (CategoryRepository).",
                0, $e
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function createCategory(Category $category): bool
    {
        try {
            $this->validateRequiredFields($category);
            $this->checkIfAlreadyExistsForCreate($category);
            return $this->categoryRepository->addCategory($category);
        } catch (InvalidCategoryDataException $e) {
            error_log("createCategory - A field is missing. Error: " . $e->getMessage());
            throw $e;
        } catch (RuntimeException $e) {
            error_log("createCategory - Failed due to repository error. Data: " .
                json_encode($category) . ". Error: " . $e->getMessage());
            throw new RuntimeException("Failed to create category.", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function getCategories(): array
    {
        try {
            return $this->categoryRepository->getCategories();
        } catch (RuntimeException $e) {
            error_log("getCategories - Failed due to repository error: " . $e->getMessage());
            throw new RuntimeException("Failed to retrieve categories. Please check logs for details.",
                0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function updateCategory(Category $category): bool
    {
        if ($category->getId() === null) {
            throw new MissingCategoryFieldException('id', 'Category ID is required for an update.');
        }

        try {
            $this->validateRequiredFields($category);
            $this->checkIfAlreadyExistsForUpdate($category);
            $this->checkIfChangesMade($category);
            $this->checkIfNewParentIsDescendant($category);

            return $this->categoryRepository->updateCategory($category);
        } catch (InvalidCategoryDataException $e) {
            error_log("createCategory - A field is missing. Error: " . $e->getMessage());
            throw $e;
        } catch (ResourceNotFoundException $e) {
            error_log("CategoryService::updateCategory
             - Resource not found for ID {$category->getId()}. Error: " . $e->getMessage());
            throw $e;
        } catch (RuntimeException $e) {
            $categoryId = $category->getId();
            error_log("updateCategory - Failed for category ID {$categoryId} due to repository error. Data: "
                . json_encode($category) . ". Error: " . $e->getMessage());
            throw new RuntimeException("Failed to update category.", 0, $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteCategory(int $id): bool
    {
        $allCategoriesData = $this->categoryRepository->getCategories();
        $categoryAndDescendantIds = $this->collectCategoryAndDescendantIds($id, $allCategoriesData);

        if (empty($categoryAndDescendantIds)) {
            throw new ResourceNotFoundException("Category with id {$id} not found.");
        }

        $hasProducts = $this->productRepository->hasProductsInCategories($categoryAndDescendantIds);
        if ($hasProducts) {
            throw new CategoryInUseException("Cannot delete category ID {$id} " .
                " as it or its subcategories contain products.");
        }

        try {
            return $this->categoryRepository->deleteCategory($id);
        } catch (ResourceNotFoundException $e) {
            error_log("CategoryService::deleteCategory - " .
                "Resource not found for ID {$id}. Error: " . $e->getMessage());
            throw $e;
        } catch (RuntimeException $e) {
            error_log(
                "deleteCategory - Failed for category ID {$id} due to repository error: " . $e->getMessage());
            throw new RuntimeException("Failed to delete category.", 0, $e);
        }
    }

    /**
     * Collects the target category ID and all its descendant IDs.
     *
     * @param int $targetCategoryId
     * @param array $allCategoriesData
     * @return array
     */
    private function collectCategoryAndDescendantIds(int $targetCategoryId, array $allCategoriesData): array
    {
        $maps = $this->buildCategoryMaps($allCategoriesData);
        $categoriesById = $maps['byId'] ?? [];

        $idsToProcess = [$targetCategoryId];
        $collectedIds = [];
        $visitedForQueue = [];

        while (!empty($idsToProcess)) {
            $currentId = array_shift($idsToProcess);
            if (!isset($categoriesById[$currentId]) || isset($collectedIds[$currentId])) {
                continue;
            }

            $collectedIds[$currentId] = $currentId;
            $currentCategory = $categoriesById[$currentId];

            $currentCategoryTitle = $currentCategory['title'] ?? null;
            if ($currentCategoryTitle === null) continue;

            foreach ($allCategoriesData as $potentialChild) {
                if (isset($potentialChild['parent'], $potentialChild['id']) &&
                    $potentialChild['parent'] === $currentCategoryTitle) {
                    if (!isset($visitedForQueue[$potentialChild['id']])) {
                        $idsToProcess[] = $potentialChild['id'];
                        $visitedForQueue[$potentialChild['id']] = true;
                    }
                }
            }
        }

        return array_values($collectedIds);
    }

    /**
     * Validates title and code fields
     *
     * @param Category $category
     *
     * @return void
     */
    private function validateRequiredFields(Category $category): void
    {
        if (empty(trim($category->getTitle()))) {
            throw new MissingCategoryFieldException('title');
        }
        if (empty(trim($category->getCode()))) {
            throw new MissingCategoryFieldException('code');
        }
        if (strlen(trim($category->getCode())) > 4) {
            throw new InvalidCategoryDataException('The code must be 4 characters or less.');
        }
    }

    /**
     * Checks if a category with the same code or title already exists
     *
     * @param Category $category
     *
     * @return void
     */
    private function checkIfAlreadyExistsForCreate(Category $category): void
    {
        $existingByCode = $this->categoryRepository->findByCode($category->getCode());
        if ($existingByCode !== null) {
            throw new InvalidCategoryDataException(
                "A category with code '{$category->getCode()}' already exists.");
        }
        $existingByTitle = $this->categoryRepository->findByTitle($category->getTitle());
        if ($existingByTitle !== null) {
            throw new InvalidCategoryDataException(
                "A category with title '{$category->getTitle()}' already exists.");
        }
    }

    /**
     * Checks if a category with the same code or title already exists
     *
     * @param Category $category
     *
     * @return void
     */
    private function checkIfAlreadyExistsForUpdate(Category $category): void
    {
        $categoryId = $category->getId();
        $existingByCode = $this->categoryRepository->findByCode($category->getCode());
        if ($existingByCode !== null && $existingByCode->getId() !== $categoryId) {
            throw new InvalidCategoryDataException(
                "Another category with code '{$category->getCode()}' already exists.");
        }
        $existingByTitle = $this->categoryRepository->findByTitle($category->getTitle());
        if ($existingByTitle !== null && $existingByTitle->getId() !== $categoryId) {
            throw new InvalidCategoryDataException(
                "Another category with title '{$category->getTitle()}' already exists.");
        }
    }

    /**
     * Checks if there are any fields to update
     *
     * @param Category $category
     *
     * @return void
     */
    private function checkIfChangesMade(Category $category): void
    {
        $currentCategory = $this->categoryRepository->findById($category->getId());
        if ($currentCategory === null) {
            throw new ResourceNotFoundException(
                "Category with ID {$category->getId()} not found, cannot update.");
        }
        if (
            strtolower(trim($currentCategory->getTitle())) === strtolower(trim($category->getTitle())) &&
            strtolower(trim($currentCategory->getCode())) === strtolower(trim($category->getCode())) &&
            ($currentCategory->getParent() === $category->getParent()) &&
            (trim($currentCategory->getDescription()) === trim($category->getDescription()))
        ) {
            throw new NoChangesMadeException(
                "The submitted data for category {$category->getTitle()} " .
                "is identical to the existing record. No update performed.");
        }
    }

    /**
     * Checks if a given category is a descendant of another category.
     *
     * @param int $potentialDescendantId
     * @param int $potentialAncestorId
     * @param array $allRawCategoriesData
     *
     * @return bool
     */
    private function isDescendantOf(int $potentialDescendantId,
                                    int $potentialAncestorId, array $allRawCategoriesData): bool
    {
        $maps = $this->buildCategoryMaps($allRawCategoriesData);
        $categoryMapById = $maps['byId'];
        $categoryMapByTitle = $maps['byTitle'];

        $currentIdInTraversal = $potentialDescendantId;
        $maxDepth = count($allRawCategoriesData) + 1;
        $depth = 0;

        while ($depth++ < $maxDepth) {
            if (!isset($categoryMapById[$currentIdInTraversal])) {
                return false;
            }

            $currentCategoryData = $categoryMapById[$currentIdInTraversal];
            $parentTitle = $currentCategoryData['parent'] ?? null;
            if ($parentTitle === null) {
                return false;
            }

            $parentCategoryData = $categoryMapByTitle[$parentTitle];
            $parentId = $parentCategoryData['id'] ?? null;
            if ($parentId === null) {
                return false;
            }
            if ($parentId === $potentialAncestorId) {
                return true;
            }

            $currentIdInTraversal = $parentId;
        }

        return false;
    }

    /**
     * Builds helper maps
     *
     * @param array $allRawCategoriesData
     *
     * @return array[]
     */
    private function buildCategoryMaps(array $allRawCategoriesData): array
    {
        $categoryMapById = [];
        $categoryMapByTitle = [];
        foreach ($allRawCategoriesData as $catData) {
            if (isset($catData['id'])) {
                $categoryMapById[$catData['id']] = $catData;
            }
            if (isset($catData['title'])) {
                $categoryMapByTitle[$catData['title']] = $catData;
            }
        }

        return ['byId' => $categoryMapById, 'byTitle' => $categoryMapByTitle];
    }

    /**
     * Checks if the categories are circular parents
     *
     * @param Category $category
     *
     * @return void
     */
    private function checkIfNewParentIsDescendant(Category $category): void
    {
        $categoryIdBeingUpdated = $category->getId();
        $newParentTitle = $category->getParent();

        if ($newParentTitle === null) {
            return;
        }

        $newParentCategoryObject = $this->categoryRepository->findByTitle($newParentTitle);

        if ($newParentCategoryObject === null) {
            throw new InvalidCategoryDataException(
                "Specified parent category '{$newParentTitle}' does not exist.");
        }

        $newParentId = $newParentCategoryObject->getId();

        if ($newParentId === $categoryIdBeingUpdated) {
            throw new InvalidCategoryDataException("A category cannot be its own parent.");
        }

        $allRawCategoriesData = $this->categoryRepository->getCategories();

        if ($this->isDescendantOf($newParentId, $categoryIdBeingUpdated, $allRawCategoriesData)) {
            $currentCategory = $this->categoryRepository->findById($categoryIdBeingUpdated);
            $currentCategoryTitle = $currentCategory ? $currentCategory->getTitle() : "ID {$categoryIdBeingUpdated}";
            throw new InvalidCategoryDataException(
                "Cannot set '{$newParentTitle}' as parent for '{$currentCategoryTitle}', " .
                "since '{$newParentTitle}' is a descendant of '{$currentCategoryTitle}'"
            );
        }
    }
}