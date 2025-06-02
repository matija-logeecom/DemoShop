<?php

namespace DemoShop\Business\Service;

use DemoShop\Business\Interfaces\Repository\ProductRepositoryInterface;
// TODO: Uncomment when CategoryRepositoryInterface is used for category validation
// use DemoShop\src\Business\Interfaces\Repository\CategoryRepositoryInterface;
use DemoShop\Business\Interfaces\Service\ProductServiceInterface;
use DemoShop\Business\Model\Product;
use DemoShop\Data\Model\Product as ProductEloquentModel;
use DemoShop\Business\Exception\ValidationException;
use DemoShop\Business\Exception\FileUploadException;
use DemoShop\Infrastructure\DI\ServiceRegistry;

class ProductService implements ProductServiceInterface
{
    private ProductRepositoryInterface $productRepository;
    // TODO: private CategoryRepositoryInterface $categoryRepository;
    private string $physicalImageUploadPath;
    private string $imageUrlBasePath;

    public function __construct(
        // TODO: CategoryRepositoryInterface $categoryRepository, // For validating category_id
        string $physicalImageUploadPath,
        string $imageUrlBasePath
    ) {
        $this->productRepository = ServiceRegistry::get(ProductRepositoryInterface::class);
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
                throw new FileUploadException("Failed to create image upload directory: " . $this->physicalImageUploadPath);
            }
        }
        if (!is_writable($this->physicalImageUploadPath)) {
            throw new FileUploadException("Image upload directory is not writable: " . $this->physicalImageUploadPath);
        }

        $originalFilename = $fileInfo['name'];
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $safeFilenamePart = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalFilename, PATHINFO_FILENAME));
        $uniqueFilename = $safeFilenamePart . '_' . uniqid() . '.' . $extension;
        $destinationPath = $this->physicalImageUploadPath . '/' . $uniqueFilename;

        if (!move_uploaded_file($tempFilePath, $destinationPath)) {
            throw new FileUploadException("Failed to move uploaded image to destination.");
        }

        return $this->imageUrlBasePath . '/' . $uniqueFilename;
    }
}