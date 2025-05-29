<?php

namespace DemoShop\Business\Exception;

use Throwable;

class MissingCategoryFieldException extends InvalidCategoryDataException
{
    public function __construct(string $fieldName, string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        if (empty($message)) {
            $message = "Category field '{$fieldName}' is required and was not provided.";
        }
        parent::__construct($message, $code, $previous);
    }
}