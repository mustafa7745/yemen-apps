<?php

namespace App\Models;

class GooglePurchases
{
   public static $tableName = "googlePurchases";
   public static $id = "id";
   public static $purchaseToken = "purchaseToken";
   public static $productId = "productId";
   public static $isPending = "isPending";
   public static $isAck = "isAck";
   public static $isCounsumed = "isCounsumed";
   public static $storeId = "storeId";
   public static $isSubs = "isSubs";

   public static $createdAt = "createdAt";
   public static $updatedAt = "updatedAt";
}
