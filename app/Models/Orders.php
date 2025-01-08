<?php

namespace App\Models;

class Orders
{
   public static $tableName = "orders";
   public static $id = "id";
   public static $storeId = "storeId";
   public static $userId = "userId";
   public static $casherId = "casherId";
   public static $situationId = "situationId";
   public static $withApp = "withApp";
   public static $inStore = "inStore";
   public static $amount = "amount";
   public static $paid = "paid";
   public static $createdAt = "createdAt";
   public static $updatedAt = "updatedAt";
}
