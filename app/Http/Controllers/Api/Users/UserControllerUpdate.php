<?php
namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\Options;
use App\Models\Orders;
use App\Models\OrdersPayments;
use App\Models\Products;
use App\Models\StoreProducts;
use App\Models\UsersSessions;
use App\Traits\AllShared;
use App\Traits\UsersControllerShared;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class UserControllerUpdate extends Controller
{
    use UsersControllerShared;
    use AllShared;


    
    public function logout(Request $request)
    {

        $app = $this->getMyApp($request);
        $resultAccessToken = $this->getAccessToken($request, $app->id);
        if ($resultAccessToken->isSuccess == false) {
            return $this->responseError($resultAccessToken);
        }
        $accessToken = $resultAccessToken->message;

        // print_r($accessToken);

        return DB::transaction(function () use ($accessToken) {
            DB::table(table: UsersSessions::$tableName)
                ->where(UsersSessions::$id, '=', $accessToken->userSessionId)
                ->update([
                    UsersSessions::$isLogin => 0,
                    UsersSessions::$logoutCount => DB::raw(UsersSessions::$logoutCount . ' + 1'),
                    UsersSessions::$lastLogoutAt => Carbon::now()->format('Y-m-d H:i:s'),
                    UsersSessions::$updatedAt => Carbon::now()->format('Y-m-d H:i:s')
                ]);
            return response()->json([]);
        });
    }
}
