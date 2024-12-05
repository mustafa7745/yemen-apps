<?php

namespace App\Models;

class MyResponse
{
   public static $isSuccess;
   public static $message;
   public static $responseCode;
   public static $code;


   public function __construct($isSuccess, $message, $responseCode, $code)
   {
      $this->isSuccess = $isSuccess;
      $this->message = $message;
      $this->responseCode = $responseCode;
      $this->code = $code;
   }
}
