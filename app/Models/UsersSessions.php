<?php

namespace App\Models;

class UsersSessions
{
   public static $tableName = "usersSessions";
   public static $id = "id";
   public static $deviceSessionId = "deviceSessionId";
   public static $userId = "userId";
   public static $isLogin = "isLogin";
   public static $lastLoginAt = "lastLoginAt";
   public static $lastLogoutAt = "lastLogoutAt";
   public static $logoutCount = "logoutCount";
   public static $loginCount = "loginCount";
   public static $createdAt = "createdAt";
   public static $updatedAt = "updatedAt";
}
