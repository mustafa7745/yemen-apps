<?php
namespace App\Traits;
use App\Http\Controllers\Api\LoginController;
use App\Models\Products;
use App\Models\Stores;
use DB;
use Illuminate\Database\CustomException;

trait StoreManagerControllerShared
{
    use AllShared;
    public $appId = 1;
    public function getMyData($request, $withStore = true, $myProcessName = null)
    {
        $app = $this->getMyApp($request, $this->appId);
        $accessToken = (new LoginController($this->appId))->readAccessToken($request);
        $store = null;
        if ($withStore === true) {
            $store = $this->getMyStore($request, $accessToken->userId);
        }
        $myProcess = null;
        if ($myProcessName != null) {
            $myProcess = $this->checkProcessV1($myProcessName, $accessToken->deviceId, $accessToken->userId);
        }
        return ['app' => $app, 'accessToken' => $accessToken, 'store' => $store, 'myProcess' => $myProcess];
    }
    public function checkIfProductInStore($productId, $storeId)
    {
        print_r($productId);
        print_r($storeId);

        $data = DB::table(table: Products::$tableName)
            // ->join(
            //     Stores::$tableName,
            //     Stores::$tableName . '.' . Stores::$id,
            //     '=',
            //     Products::$tableName . '.' . Products::$storeId
            // )
            ->where(Products::$tableName . '.' . Products::$storeId, '=', $storeId)
            ->where(Products::$tableName . '.' . Products::$id, '=', $productId)
            ->first(
                [Products::$tableName . '.' . Products::$id]
            );

        if ($data == null) {
            throw new CustomException("Not have permission to update this product ", 0, 403);
            # code...
        }

    }
}