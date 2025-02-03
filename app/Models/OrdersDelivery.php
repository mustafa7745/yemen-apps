<?php

namespace App\Models;

class OrdersDelivery
{
   public static $tableName = "ordersDelivery";
   public static $id = "id";
   public static $orderId = "orderId";
   public static $locationId = "locationId";
   public static $deliveryPrice = "deliveryPrice";
   public static $deliveryPriceCurrency = "deliveryPriceCurrency";
   public static $deliveryManId = "deliveryManId";
   public static $createdAt = "createdAt";
   public static $updatedAt = "updatedAt";
}
