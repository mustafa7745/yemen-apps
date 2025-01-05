<?php

namespace App\Models;

class OrdersProducts
{
   public static $tableName = "ordersProducts";
   public static $id = "id";
   public static $orderId = "orderId";
   public static $storeProductId = "storeProductId";
   public static $productName = "productName";
   public static $productPrice = "productPrice";
   public static $productQuantity = "productQuantity";
   public static $createdAt = "createdAt";
   public static $updatedAt = "updatedAt";
}
