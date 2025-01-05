<?php
namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\Options;
use App\Models\Orders;
use App\Models\Products;
use App\Models\StoreProducts;
use App\Traits\AllShared;
use App\Traits\UsersControllerShared;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class UserControllerAdd extends Controller
{
    use UsersControllerShared;
    use AllShared;

    public function addLocation(Request $request)
    {
        $app = $this->getMyApp($request);
        return $this->addOurLocation($request, $app->id);
    }
    // public function confirmOrder(Request $request)
    // {
    //     $app = $this->getMyApp($request);

    //     $storeId = $request->input('storeId');
    //     $orderProducts = $request->input('orderProducts');
    //     $orderProducts = json_decode($orderProducts);

    //     $ids = [];

    //     foreach ($orderProducts as $orderProduct) {
    //         array_push($ids, $orderProduct->id);
    //     }

    //     $storeProducts = DB::table(StoreProducts::$tableName)
    //         ->whereIn(StoreProducts::$tableName . '.' . StoreProducts::$id, $ids)
    //         ->join(
    //             Products::$tableName,
    //             Products::$tableName . '.' . Products::$id,
    //             '=',
    //             StoreProducts::$tableName . '.' . StoreProducts::$productId
    //         )
    //         ->join(
    //             Options::$tableName,
    //             Options::$tableName . '.' . Options::$id,
    //             '=',
    //             StoreProducts::$tableName . '.' . StoreProducts::$optionId
    //         )
    //         ->get([
    //             StoreProducts::$tableName . '.' . StoreProducts::$id,
    //             StoreProducts::$tableName . '.' . StoreProducts::$price,
    //             Products::$tableName . '.' . Products::$name . ' as productName',
    //             Options::$tableName . '.' . Options::$name . ' as optionName',
    //         ]);




    //     if (count($storeProducts) != count($orderProduct)) {
    //         return "error";
    //     }

    //     return DB::transaction(function () use ($request, $accessToken) {

    //         $orderId = DB::table(table: Orders::$tableName)
    //         ->insertGetId([
    //             Orders::$id => null,
    //             Orders::$storeId => $storeId,
    //             Orders::$userId => $accessToken->userId,
    //             Orders::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
    //             Orders::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
    //         ]);


    //     });







    //     // return $this->addOurLocation($request, $app->id);
    // }

}
