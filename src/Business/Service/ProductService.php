<?php

namespace DemoShop\Business\Service;

use DemoShop\Business\Interfaces\Repository\CategoryRepositoryInterface;
use DemoShop\Business\Interfaces\Repository\ProductRepositoryInterface;
// TODO: Uncomment when CategoryRepositoryInterface is used for category validation
// use DemoShop\src\Business\Interfaces\Repository\CategoryRepositoryInterface;
use DemoShop\Business\Interfaces\Service\ProductServiceInterface;
use DemoShop\Business\Model\Product;
use DemoShop\Data\Model\Product as ProductEloquentModel;
use DemoShop\Business\Exception\ValidationException;
use DemoShop\Business\Exception\FileUploadException;
use DemoShop\Infrastructure\DI\ServiceRegistry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService implements ProductServiceInterface
{
    private ProductRepositoryInterface $productRepository;
    private CategoryRepositoryInterface $categoryRepository;
    private string $physicalImageUploadPath;
    private string $imageUrlBasePath;
    private ?array $allCategoriesCache = null;
    private ?array $categoriesByIdCache = null;
    private ?array $categoriesByTitleCache = null;


    public function __construct(
        // TODO: CategoryRepositoryInterface $categoryRepository, // For validating category_id
        string $physicalImageUploadPath,
        string $imageUrlBasePath
    ) {
        $this->productRepository = ServiceRegistry::get(ProductRepositoryInterface::class);
        $this->categoryRepository = ServiceRegistry::get(CategoryRepositoryInterface::class);
        // TODO: $this->categoryRepository = $categoryRepository;
        $this->physicalImageUploadPath = rtrim($physicalImageUploadPath, '/');
        $this->imageUrlBasePath = rtrim($imageUrlBasePath, '/');
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
        } elseif ($fileInfo && $fileInfo['error'] !== UPLOAD_ERR_NO_FILE) {
            // Handle other $_FILES errors if a file was attempted but failed system checks
            throw new FileUploadException("File upload system error. Code: " . $fileInfo['error']);
        }
        // Note: You might decide an image is mandatory and throw a ValidationException if $imagePathForDb is null.

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

    public function getProducts(int $page = 1, int $perPage = 10): LengthAwarePaginator
    {
        $paginatedResult = $this->productRepository->getAll($page, $perPage);

        $this->loadAndCacheCategories();

        $paginatedResult->getCollection()->transform(function ($product) {
            if ($product->category_id && $this->categoriesByIdCache) {
                $product->category_hierarchy_display = $this->getCategoryHierarchyString(
                    $product->category_id
                );
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
    public function deleteProducts(array $productIds): int // <-- IMPLEMENT THIS METHOD
    {
        if (empty($productIds)) {
            return 0;
        }

        // Validate IDs are integers
        $validatedIds = [];
        foreach ($productIds as $id) {
            if (filter_var($id, FILTER_VALIDATE_INT) && (int)$id > 0) {
                $validatedIds[] = (int)$id;
            } else {
                // Or collect errors and throw ValidationException
                error_log("ProductService::deleteProducts - Invalid product ID provided: " . $id);
            }
        }

        if (empty($validatedIds)) {
            // Optionally throw ValidationException if all IDs were invalid or none provided.
            // For now, just return 0 if no valid IDs after filtering.
            return 0;
        }

        // 1. Fetch products to get image paths
        $productsToDelete = $this->productRepository->findByIds($validatedIds);

        // 2. Delete image files
        foreach ($productsToDelete as $product) {
            if (!empty($product->image_path)) {
                $this->removeImage($product);
            }
        }
        // 3. Delete product records from database
        return $this->productRepository->deleteByIds($validatedIds);
    }

    /**
     * @inheritDoc
     */
    public function updateProductsEnabledStatus(array $productIds, bool $newStatus): int // <-- IMPLEMENT THIS METHOD
    {
        if (empty($productIds)) {
            return 0;
        }

        $validatedIds = [];
        $errors = [];
        foreach ($productIds as $id) {
            if (filter_var($id, FILTER_VALIDATE_INT) && (int)$id > 0) {
                $validatedIds[] = (int)$id;
            } else {
                $errors['ids'] = 'One or more product IDs are invalid.';
                error_log(
                    "updateProductsEnabledStatus - Invalid product ID provided: " . print_r($id, true));
            }
        }

        if (!empty($errors)) {
            throw new ValidationException("Invalid input for updating product status.", $errors);
        }

        if (empty($validatedIds)) {
            return 0;
        }

        return $this->productRepository->updateIsEnabledStatus($validatedIds, $newStatus);
    }

    private function removeImage(mixed $product): void
    {
        $filename = basename($product->image_path);
        $physicalFilePath = $this->physicalImageUploadPath . '/' . $filename;

        if (file_exists($physicalFilePath)) {
            if (!@unlink($physicalFilePath)) {
                error_log("ProductService::deleteProducts - Failed to delete image file: " . $physicalFilePath);
            }
        }
    }

    /**
     * Loads all categories from the CategoryService and caches them as maps.
     */
    private function loadAndCacheCategories(): void
    {
        if ($this->allCategoriesCache === null) {
            $this->allCategoriesCache = $this->categoryRepository->getCategories(); // Returns array of arrays
            $this->categoriesByIdCache = [];
            $this->categoriesByTitleCache = [];

            foreach ($this->allCategoriesCache as $category) {
                if (isset($category['id'])) {
                    $this->categoriesByIdCache[$category['id']] = $category;
                }
                if (isset($category['title'])) {
                    // Assuming titles are unique enough to be keys for parent lookup.
                    // If not, this strategy needs refinement or parent lookup by ID.
                    $this->categoriesByTitleCache[$category['title']] = $category;
                }
            }
        }
    }

    private function getCategoryHierarchyString(int $categoryId): string
    {
        $pathParts = [];
        $currentCatId = $categoryId;

        while ($currentCatId !== null && isset($this->categoriesByIdCache[$currentCatId])) {
            $category = $this->categoriesByIdCache[$currentCatId];
            array_unshift($pathParts, $category['title']); // Add to the beginning of the path

            $parentTitle = $category['parent'] ?? null; // 'parent' field stores the parent's title

            if ($parentTitle && isset($this->categoriesByTitleCache[$parentTitle])) {
                $currentCatId = $this->categoriesByTitleCache[$parentTitle]['id'];
            } else {
                $currentCatId = null; // No more parents or parent not found
            }
        }

        return implode(' > ', $pathParts);
    }

    /**
     * Validates the product input data from the DTO.
     * @param Product $product
     * @return array Associative array of validation errors. Empty if valid.
     */
    private function validateProductInput(Product $product): array
    {
        $errors = [];

        if (empty(trim($product->getSku()))) {
            $errors['sku'] = 'SKU is required.';
        } //else {
            // TODO: Implement SKU uniqueness check using $this->productRepository->findBySku($product->getSku())
            // if ($this->productRepository->findBySku($product->getSku()) !== null) {
            //     $errors['sku'] = 'SKU already exists.';
            // }
       // }

        if (empty(trim($product->getTitle()))) {
            $errors['title'] = 'Title is required.';
        }

        if (!is_numeric($product->getPrice()) || $product->getPrice() < 0) {
            $errors['price'] = 'Price must be a non-negative number.';
        }

        if ($product->getCategoryId() === null || !filter_var($product->getCategoryId(), FILTER_VALIDATE_INT) || $product->getCategoryId() <= 0) {
            $errors['category_id'] = 'A valid category is required.';
        } //else {
            // TODO: Validate category_id existence using $this->categoryRepository->findById($product->getCategoryId())
            // if ($this->categoryRepository->findById($product->getCategoryId()) === null) {
            //    $errors['category_id'] = 'Selected category does not exist.';
            // }
       // }
        // Add more validation as needed (e.g., max lengths, specific formats)

        return $errors;
    }

    /**
     * Processes the uploaded image file: validates, moves, and returns its storable path.
     * @param array $fileInfo The $_FILES entry for the uploaded image.
     * @return string|null The relative path to the stored image, or null on failure.
     * @throws FileUploadException|ValidationException
     */
    private function processImageUpload(array $fileInfo): ?string
    {
        $errors = [];

        // Check for upload errors provided by PHP
        if ($fileInfo['error'] !== UPLOAD_ERR_OK) {
            throw new FileUploadException("File upload error. Code: " . $fileInfo['error']);
        }

        $tempFilePath = $fileInfo['tmp_name'];
        $imageInfo = @getimagesize($tempFilePath);

        if ($imageInfo === false) {
            throw new ValidationException("Uploaded file is not a valid image.", ['image_format' => 'Invalid image format.']);
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $mime = $imageInfo['mime'];

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mime, $allowedMimes)) {
            $errors['image_type'] = 'Invalid image type. Allowed: JPEG, PNG, GIF, WebP.';
        }

        if ($width < 600) {
            $errors['image_resolution'] = "Image width must be at least 600px (is {$width}px).";
        }

        if ($height > 0) {
            $isAtLeast4x3 = ($width * 3) >= ($height * 4);
            $isAtMost16x9 = ($width * 9) <= ($height * 16);
            if (!$isAtLeast4x3 || !$isAtMost16x9) {
                $ratio = round($width / $height, 2);
                $errors['image_aspect_ratio'] = "Aspect ratio must be between 4:3 and 16:9 (yours is ~{$ratio}:1).";
            }
        } else {
            $errors['image_dimensions'] = "Image height is invalid (0px).";
        }

        if (!empty($errors)) {
            throw new ValidationException("Image validation failed.", $errors);
        }

        // File is valid, proceed to move
        if (!is_dir($this->physicalImageUploadPath)) {
            if (!@mkdir($this->physicalImageUploadPath, 0775, true)) {
                throw new FileUploadException("Failed to create image upload directory: "
                    . $this->physicalImageUploadPath);
            }
        }
        if (!is_writable($this->physicalImageUploadPath)) {
            throw new FileUploadException("Image upload directory is not writable: " . $this->physicalImageUploadPath);
        }

        $originalFilename = $fileInfo['name'];
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $safeFilenamePart = preg_replace(
            '/[^a-zA-Z0-9_-]/', '_', pathinfo($originalFilename, PATHINFO_FILENAME));
        $uniqueFilename = $safeFilenamePart . '_' . uniqid() . '.' . $extension;
        $destinationPath = $this->physicalImageUploadPath . '/' . $uniqueFilename;

        if (!move_uploaded_file($tempFilePath, $destinationPath)) {
            throw new FileUploadException("Failed to move uploaded image to destination.");
        }

        return $this->imageUrlBasePath . '/' . $uniqueFilename;
    }
}