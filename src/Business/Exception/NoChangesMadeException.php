<?php

namespace DemoShop\src\Business\Exception;

class NoChangesMadeException extends InvalidCategoryDataException
{
    public function __construct(
        string $message = "No changes were made as the submitted data is identical to the existing record.",
        int    $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}