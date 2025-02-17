<?php

namespace App\Models;

class GooglePurchases
{
   public static $tableName = "googlePurchases.php";
   public static $id = "id";
   public static $purchaseToken = "purchaseToken";
   public static $productId = "productId";
   public static $isPending = "isPending";
   public static $isAck = "isAck";
   public static $isCounsumed = "isCounsumed";
   public static $storeId = "storeId";
   public static $createdAt = "createdAt";
   public static $updatedAt = "updatedAt";
}
