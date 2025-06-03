<?php

namespace DemoShop\Business\Service;

use DemoShop\Business\Interfaces\Repository\ProductRepositoryInterface;
use DemoShop\Business\Interfaces\Service\CategoryServiceInterface;
use DemoShop\Business\Interfaces\Service\ProductServiceInterface;
use DemoShop\Business\Model\Product;
use DemoShop\Business\Exception\ValidationException;
use DemoShop\Business\Exception\FileUploadException;
use DemoShop\Infrastructure\Config\PathConfig;
use DemoShop\Infrastructure\DI\ServiceRegistry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService implements ProductServiceInterface
{
    private ProductRepositoryInterface $productRepository;
    private CategoryServiceInterface $categoryService;
    private string $physicalImageUploadPath;
    private string $imageUrlBasePath;
    private ?array $allCategoriesCache = null;
    private ?array $categoriesByIdCache = null;
    private ?array $categoriesByTitleCache = null;


    public function __construct()
    {
        $this->productRepository = ServiceRegistry::get(ProductRepositoryInterface::class);
        $this->categoryService = ServiceRegistry::get(CategoryServiceInterface::class);

        $this->physicalImageUploadPath = ServiceRegistry::get(PathConfig::class)->getProductImagePhysicalPath();
        $this->imageUrlBasePath = ServiceRegistry::get(PathConfig::class)->getProductImageUrlBase();
    }

    /**
     * @inheritDoc
     */
    public function createProduct(Product $product): bool
    {
        $validationErrors = $this->validateProductInput($product);
        if (!empty($validationErrors)) {
            throw new ValidationException("Product validation failed.", $validationErrors);
        }

        $imagePathForDb = null;
        $fileInfo = $product->getImageFileInfo();

        if ($fileInfo && isset($fileInfo['tmp_name']) && $fileInfo['error'] === UPLOAD_ERR_OK) {
            $imagePathForDb = $this->processImageUpload($fileInfo);
        }
        if ($fileInfo && $fileInfo['error'] !== UPLOAD_ERR_NO_FILE && $fileInfo['error'] !== UPLOAD_ERR_OK) {
            throw new FileUploadException("File upload system error. Code: " . $fileInfo['error']);
        }

        $dataForRepository = [
            'sku' => $product->getSku(),
            'title' => $product->getTitle(),
            'brand' => $product->getBrand(),
            'category_id' => $product->getCategoryId(),
            'price' => $product->getPrice(),
            'short_description' => $product->getShortDescription(),
            'description' => $product->getDescription(),
            'image_path' => $imagePathForDb,
            'is_enabled' => $product->isEnabled(),
            'is_featured' => $product->isFeatured(),
        ];

        return $this->productRepository->create($dataForRepository);
    }

    /**
     * @inheritDoc
     */
    public function getProducts(int $page = 1, int $perPage = 10, array $filters = []): LengthAwarePaginator
    {
        if (!empty($filters['category_id'])) {
            $selectedCategoryId = (int)$filters['category_id'];

            $allCategoriesData = $this->categoryService->getCategories();
            $filters['category_ids'] = $this->categoryService
                ->collectCategoryAndDescendantIds($selectedCategoryId, $allCategoriesData);

            unset($filters['category_id']);
        }

        $paginatedResult = $this->productRepository->getAll($page, $perPage, $filters);

        $this->loadAndCacheCategories();

        $paginatedResult->getCollection()->transform(function ($product) {
            if ($product->category_id && $this->categoriesByIdCache) {
                $product->category_hierarchy_display = $this->getCategoryHierarchyString($product->category_id);
            } else {
                $product->category_hierarchy_display = 'N/A';
            }

            return $product;
        });

        return $paginatedResult;
    }

    /**
     * @inheritDoc
     */
    public function deleteProducts(array $productIds): int
    {
        if (empty($productIds)) {
            return 0;
        }

        $validatedIds = $this->validateIds($productIds);

        $productsToDelete = $this->productRepository->findByIds($validatedIds);

        foreach ($productsToDelete as $product) {
            if (!empty($product->image_path)) {
                $this->removeImage($product);
            }
        }

        return $this->productRepository->deleteByIds($validatedIds);
    }

    /**
     * @inheritDoc
     */
    public function updateProductsEnabledStatus(array $productIds, bool $newStatus): int
    {
        if (empty($productIds)) {
            throw new ValidationException('No product ids provided.');
        }

        $validatedIds = $this->validateIds($productIds);

        return $this->productRepository->updateIsEnabledStatus($validatedIds, $newStatus);
    }

    /**
     * Returns an array of validated Ids
     *
     * @param array $productIds
     *
     * @return array
     *
     * @throws ValidationException
     */
    private function validateIds(array $productIds): array
    {
        $validatedIds = [];
        foreach ($productIds as $id) {
            if (filter_var($id, FILTER_VALIDATE_INT) && (int)$id > 0) {
                $validatedIds[] = (int)$id;
            }
        }

        if (empty($validatedIds)) {
            throw new ValidationException("Invalid input for updating product ids.");
        }

        return $validatedIds;
    }

    /**
     * Removes image from filesystem of provided product
     *
     * @param mixed $product
     *
     * @return void
     */
    private function removeImage(mixed $product): void
    {
        $filename = basename($product->image_path);
        $physicalFilePath = $this->physicalImageUploadPath . '/' . $filename;

        if (file_exists($physicalFilePath)) {
            if (!@unlink($physicalFilePath)) {
                error_log(
                    "ProductService::deleteProducts - Failed to delete image file: " . $physicalFilePath);
            }
        }
    }

    /**
     * Loads all categories from the CategoryService and caches them as maps.
     */
    private function loadAndCacheCategories(): void
    {
        if ($this->allCategoriesCache !== null) {
            return;
        }

        $this->allCategoriesCache = $this->categoryService->getCategories();
        $this->categoriesByIdCache = [];
        $this->categoriesByTitleCache = [];

        foreach ($this->allCategoriesCache as $category) {
            if (isset($category['id'])) {
                $this->categoriesByIdCache[$category['id']] = $category;
            }
            if (isset($category['title'])) {
                $this->categoriesByTitleCache[$category['title']] = $category;
            }
        }
    }

    /**
     * Returns the product category hierarchy as string
     *
     * @param int $categoryId
     *
     * @return string
     */
    private function getCategoryHierarchyString(int $categoryId): string
    {
        $pathParts = [];
        $currentCatId = $categoryId;

        while ($currentCatId !== null && isset($this->categoriesByIdCache[$currentCatId])) {
            $category = $this->categoriesByIdCache[$currentCatId];
            array_unshift($pathParts, $category['title']);

            $parentTitle = $category['parent'] ?? null;

            $currentCatId = $parentTitle && isset($this->categoriesByTitleCache[$parentTitle])
                ? $this->categoriesByTitleCache[$parentTitle]['id']
                : null;
        }

        return implode(' > ', $pathParts);
    }

    /**
     * Validates the product input data from the DTO.
     *
     * @param Product $product
     *
     * @return array
     */
    private function validateProductInput(Product $product): array
    {
        $errors = [];

        if (empty(trim($product->getSku()))) {
            $errors['sku'] = 'SKU is required.';
        }
        if (!empty(trim($product->getSku())) &&
            $this->productRepository->findBySku($product->getSku()) !== null) {
            $errors['sku'] = 'Product with same SKU already exists';
        }

        if (empty(trim($product->getTitle()))) {
            $errors['title'] = 'Title is required.';
        }

        if (!is_numeric($product->getPrice()) || $product->getPrice() < 0) {
            $errors['price'] = 'Price must be a non-negative number.';
        }

        if ($product->getCategoryId() === null ||
            !filter_var($product->getCategoryId(), FILTER_VALIDATE_INT) ||
            $product->getCategoryId() <= 0) {
            $errors['category_id'] = 'A valid category is required.';
        }

        return $errors;
    }

    /**
     * Orchestrates the image upload process
     *
     * @param array $fileInfo
     *
     * @return string|null
     *
     * @throws FileUploadException|ValidationException
     */
    private function processImageUpload(array $fileInfo): ?string
    {
        $this->validateInitialUpload($fileInfo);

        $tempFilePath = $fileInfo['tmp_name'];
        $imageDetails = $this->getImageDetails($tempFilePath);

        $this->validateImageContent($imageDetails['width'], $imageDetails['height'], $imageDetails['mime']);

        $this->ensureUploadDirectoryExistsAndWritable();

        $uniqueFilename = $this->generateUniqueFilename($fileInfo['name']);
        $destinationPath = $this->physicalImageUploadPath . '/' . $uniqueFilename;

        $this->moveUploadedImage($tempFilePath, $destinationPath);

        return $this->imageUrlBasePath . '/' . $uniqueFilename;
    }

    /**
     * Validates initial PHP file upload status.
     *
     * @throws FileUploadException
     */
    private function validateInitialUpload(array $fileInfo): void
    {
        if ($fileInfo['error'] !== UPLOAD_ERR_OK) {
            throw new FileUploadException("System file upload error. Code: " . $fileInfo['error']);
        }
    }

    /**
     * Gets image details (width, height, mime)
     * .
     * @throws ValidationException if file is not a valid image.
     */
    private function getImageDetails(string $tempFilePath): array
    {
        $imageInfo = @getimagesize($tempFilePath);
        if ($imageInfo === false) {
            throw new ValidationException("Uploaded file is not a valid image or format is not supported.",
                ['image_format' => 'Invalid image format.']);
        }

        return ['width' => $imageInfo[0], 'height' => $imageInfo[1], 'mime' => $imageInfo['mime']];
    }

    /**
     * Validates image content properties (mime, width, height, aspect ratio).
     *
     * @throws ValidationException
     */
    private function validateImageContent(int $width, int $height, string $mime): void
    {
        $errors = [];
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime, $allowedMimes)) {
            $errors['image_type'] = 'Invalid image type. Allowed: JPEG, PNG, GIF, WebP.';
        }

        if ($width < 600) {
            $errors['image_resolution'] = "Image width must be at least 600px (is {$width}px).";
        }

        if ($height <= 0) {
            $errors['image_dimensions'] = "Image height is invalid or zero.";
        } else {
            $isAtLeast4x3 = ($width * 3) >= ($height * 4);
            $isAtMost16x9 = ($width * 9) <= ($height * 16);
            if (!$isAtLeast4x3 || !$isAtMost16x9) {
                $ratio = round($width / $height, 2);
                $errors['image_aspect_ratio'] =
                    "Aspect ratio must be between 4:3 (approx 1.33) and 16:9 (approx 1.78). Yours is ~{$ratio}:1.";
            }
        }

        if (!empty($errors)) {
            throw new ValidationException("Image content validation failed.", $errors);
        }
    }

    /**
     * Ensures the physical upload directory exists and is writable.
     *
     * @throws FileUploadException
     */
    private function ensureUploadDirectoryExistsAndWritable(): void
    {
        if (!is_dir($this->physicalImageUploadPath)) {
            if (!mkdir($this->physicalImageUploadPath, 0775, true)) {
                throw new FileUploadException("Failed to create image upload directory: " .
                    $this->physicalImageUploadPath . ". Check parent directory permissions.");
            }
        }
        if (!is_writable($this->physicalImageUploadPath)) {
            throw new FileUploadException(
                "Image upload directory is not writable: " . $this->physicalImageUploadPath);
        }
    }

    /**
     * Generates a safe and unique filename for the uploaded image.
     */
    private function generateUniqueFilename(string $originalFilename): string
    {
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $safeFilenamePart = preg_replace('/[^a-zA-Z0-9_-]/',
            '_', pathinfo($originalFilename, PATHINFO_FILENAME));

        return $safeFilenamePart . '_' . uniqid() . '.' . $extension;
    }

    /**
     * Moves the uploaded temporary file to its permanent destination.
     *
     * @throws FileUploadException
     */
    private function moveUploadedImage(string $tempFilePath, string $destinationPath): void
    {
        if (!move_uploaded_file($tempFilePath, $destinationPath)) {
            $errorMessage = "Failed to move uploaded image to destination.";
            error_log("move_uploaded_file failed: from {$tempFilePath} to {$destinationPath}. Error: ");
            throw new FileUploadException($errorMessage);
        }
    }
}
