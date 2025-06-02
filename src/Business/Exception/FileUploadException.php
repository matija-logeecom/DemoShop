<?php

namespace DemoShop\Business\Exception;

use Exception;

class FileUploadException extends Exception
{
    // You can add specific properties or methods if needed later
    public function __construct(string $message = "File upload failed.", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}