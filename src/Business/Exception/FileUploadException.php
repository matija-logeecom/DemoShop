<?php

namespace DemoShop\Business\Exception;

use Exception;
use Throwable;

class FileUploadException extends Exception
{
    public function __construct(string $message = "File upload failed.", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}