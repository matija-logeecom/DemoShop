<?php

namespace DemoShop\Business\Exception;

class CategoryInUseException extends InvalidCategoryDataException
{
    public function __construct(
        string $message = "Category cannot be deleted because it or its subcategories contain products.",
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}