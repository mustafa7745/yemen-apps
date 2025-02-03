<?php

namespace Illuminate\Database;

use Exception;

class CustomException extends Exception
{
    protected $message;
    protected $code;  // Custom error code
    protected $responseCode; // Custom response code

    // Constructor accepts message, custom code, and response code
    public function __construct($message, $code, $responseCode)
    {
        $this->message = $message;
        $this->code = $code;
        $this->responseCode = $responseCode;

        parent::__construct($this->message, $this->code);
    }

    public function getErrorCode()
    {
        return $this->code;
    }

    public function getResponseCode()
    {
        return $this->responseCode;
    }
}
