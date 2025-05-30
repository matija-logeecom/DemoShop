<?php

namespace DemoShop\src\Business\Exception;

class ResourceNotFoundException extends \RuntimeException
{
    public function __construct(string $message = "The requested resource was not found.",
                                int    $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}