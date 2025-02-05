<?php
namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Controller;
use App\Models\AppStores;
use App\Models\PaymentTypes;
use App\Models\SharedStoresConfigs;
use App\Models\StorePaymentTypes;
use App\Models\Stores;
use App\Models\Users;
use App\Models\UsersSessions;
use App\Traits\AllShared;
use App\Traits\UsersControllerShared;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class UserControllerGet extends Controller
{
    use UsersControllerShared;
    use AllShared;


    public function getApp(Request $request)
    {
        return response()->json($this->getMyApp($request));
    }

    public function login(Request $request)
    {
        $myData = $this->getMyData($request, null, false, false, 'login');
        $app = $myData['app'];
        return (new LoginController($app->id))->login($request);
    }
    public function refreshToken(Request $request)
    {
        $app = $this->getMyApp($request);
        return $this->refreshOurToken($request, $app->id);
    }

    public function getStores(Request $request)
    {
        $app = $this->getMyApp($request);
        return $this->getOurStores($app->id);
    }
    public function getHome(Request $request)
    {
        return $this->getOurHome($request);
    }
    public function getProducts(Request $request)
    {
        return $this->getOurProducts2($request);
    }
    public function search(Request $request)
    {
        return $this->searchOurProducts($request);
    }
    public function getLocations(Request $request)
    {
        $app = $this->getMyApp($request);
        return $this->getOurLocations($request, $app->id);
    }
    public function getOrders(Request $request)
    {
        $app = $this->getMyApp($request);
        $resultAccessToken = $this->getAccessToken($request, $app->id);
        if ($resultAccessToken->isSuccess == false) {
            return $this->responseError($resultAccessToken);
        }
        $accessToken = $resultAccessToken->message;

        return $this->getOurOrders($request, $accessToken->userId);
    }
    public function getPaymentTypes(Request $request)
    {
        return $this->getOurPaymentTypes($request);
    }
    public function getUserProfile(Request $request)
    {
        $app = $this->getMyApp($request);
        return $this->getOurUserProfile($request, $app->id);
    }


    public function getOrderProducts(Request $request)
    {
        $orderDelivery = $this->getOurOrderDelivery($request);
        $orderProducts = $this->getOurOrderProducts($request);
        $orderPayment = $this->getOurOrderPayment($request);
        $orderDetail = $this->getOurOrderDetail($request);
        return response()->json(['orderDelivery' => $orderDelivery, 'orderProducts' => $orderProducts, 'orderPayment' => $orderPayment, 'orderDetail' => $orderDetail]);
        // return $this->getOurOrderProducts($request);
    }
}