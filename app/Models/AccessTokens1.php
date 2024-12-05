<?php

namespace App\Models;

class AccessTokens1
{
   public static $tableName = "accessTokens";
   public static $id = "id";
   public static $userSessionId = "userSessionId";
   public static $token = "token";
   public static $refreshCount = "refreshCount";

   public static $expireAt = "expireAt";
   public static $createdAt = "createdAt";
   public static $updatedAt = "updatedAt";
}
