<?php

namespace App\Models;

class MyResponse
{
   public $isSuccess;
   public $message;
   public $responseCode;
   public $code;
   public $errors = [];

   public function __construct($isSuccess, $message, $responseCode, $code)
   {
      $this->isSuccess = $isSuccess;
      $this->message = $message;
      $this->responseCode = $responseCode;
      $this->code = $code;
   }
}
