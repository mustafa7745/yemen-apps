<?php
namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\Options;
use App\Models\Orders;
use App\Models\OrdersPayments;
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
        $myData = $this->getMyData(request: $request, appId: null, withStore: false, withUser: true);
        $storeId = $request->input('storeId');
        // $app = $myData['app'];
        $accessToken = $myData['accessToken'];

        // $app = $this->getMyApp($request);
        return $this->addOurLocation($request, $accessToken->userId, $storeId);
    }
    public function addPaidCode(Request $request)
    {
        $app = $this->getMyApp($request);
        $paidCode = $request->input('paidCode');
        $paid = $request->input('paid');
        $orderId = $request->input('orderId');

        if ($paidCode == '123456') {
            $data = DB::table(Orders::$tableName)->where(Orders::$id, '=', $orderId)->first([
                Orders::$tableName . "." . Orders::$paid
            ]);

            DB::table(OrdersPayments::$tableName)
                ->insert(
                    [
                        OrdersPayments::$id => null,
                        OrdersPayments::$orderId => $orderId,
                        OrdersPayments::$paymentId => $data->paid,
                        OrdersPayments::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                        OrdersPayments::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                    ]
                );
            return response()->json($this->getOurOrderPayment($request));
        }
        return response()->json(['message' => "رقم كود الشراء غير صحيح", 'errors' => [], 'code' => 0], 403);

    }
    public function confirmOrder(Request $request)
    {
        $myData = $this->getMyData(request: $request, withStore: false, withUser: true);
        $accessToken = $myData['accessToken'];
        $app = $myData['app'];
        $appStore = $this->getAppStore($request, $app->id);
        $this->checkStoreOpen($appStore->storeId);
        return $this->confirmOurOrder($request, $accessToken->userId, $appStore->storeId);
    }
    // {
    //     $app = $this->getMyApp($request);

    // $storeId = $request->input('storeId');
    // $orderProducts = $request->input('orderProducts');
    // $orderProducts = json_decode($orderProducts);

    // $ids = [];

    // foreach ($orderProducts as $orderProduct) {
    //     array_push($ids, $orderProduct->id);
    // }

    // $storeProducts = DB::table(StoreProducts::$tableName)
    //     ->whereIn(StoreProducts::$tableName . '.' . StoreProducts::$id, $ids)
    //     ->join(
    //         Products::$tableName,
    //         Products::$tableName . '.' . Products::$id,
    //         '=',
    //         StoreProducts::$tableName . '.' . StoreProducts::$productId
    //     )
    //     ->join(
    //         Options::$tableName,
    //         Options::$tableName . '.' . Options::$id,
    //         '=',
    //         StoreProducts::$tableName . '.' . StoreProducts::$optionId
    //     )
    //     ->get([
    //         StoreProducts::$tableName . '.' . StoreProducts::$id,
    //         StoreProducts::$tableName . '.' . StoreProducts::$price,
    //         Products::$tableName . '.' . Products::$name . ' as productName',
    //         Options::$tableName . '.' . Options::$name . ' as optionName',
    //     ]);




    // if (count($storeProducts) != count($orderProduct)) {
    //     return "error";
    // }

    // return DB::transaction(function () use ($request, $accessToken) {

    //     $orderId = DB::table(table: Orders::$tableName)
    //     ->insertGetId([
    //         Orders::$id => null,
    //         Orders::$storeId => $storeId,
    //         Orders::$userId => $accessToken->userId,
    //         Orders::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
    //         Orders::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
    //     ]);


    // });







    //     // return $this->addOurLocation($request, $app->id);
    // }

}
