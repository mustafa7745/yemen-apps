<?php

namespace App\Models;

class StoreCurencies
{
   public static $tableName = "storeCurencies";
   public static $id = "id";
   public static $storeId = "storeId";
   public static $currencyId = "currencyId";
   public static $lessCartPrice = "lessCartPrice";
   public static $freeDeliveryPrice = "freeDeliveryPrice";
   public static $deliveryPrice = "deliveryPrice";
   public static $isSelected = "isSelected";
   public static $countUsed = "countUsed";
   public static $createdAt = "createdAt";
   public static $updatedAt = "updatedAt";
}
