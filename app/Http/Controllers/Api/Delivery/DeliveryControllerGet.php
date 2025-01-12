<?php
namespace App\Http\Controllers\Api\Delivery;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Controller;
use App\Models\AccessTokens1;
use App\Models\DeliveryMen;
use App\Models\UsersSessions;
use App\Traits\AllShared;
use App\Traits\DeliveryControllerShared;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class DeliveryControllerGet extends Controller
{
    use DeliveryControllerShared;
    use AllShared;


    public function login(Request $request)
    {
        $loginController = (new LoginController($this->appId));
        $res = $loginController->loginNew($request);
        if ($res == false) {
            return response()->json(["message" => $res->message, 'code' => $res->code, 'errors' => []], $res->responseCode);
        }


        // print_r($res->message['token']);
        $token = $res->message['token'];
        $userId = DB::table(table: AccessTokens1::$tableName)
            ->join(
                UsersSessions::$tableName,
                UsersSessions::$tableName . '.' . UsersSessions::$id,
                '=',
                AccessTokens1::$tableName . '.' . AccessTokens1::$userSessionId
            )
            ->where(AccessTokens1::$tableName . '.' . AccessTokens1::$token, '=', $token)
            ->first(
                [
                    UsersSessions::$tableName . '.' . UsersSessions::$userId
                ]
            );

        // $userId = $data->userId;



        $count = DB::table(table: DeliveryMen::$tableName)
            ->where(DeliveryMen::$tableName . '.' . DeliveryMen::$userId, '=', $userId)
            ->count();

        if ($count == 0) {
            $inserted = DB::table(table: DeliveryMen::$tableName)
                ->insert([
                    DeliveryMen::$id => null,
                    DeliveryMen::$userId => $userId,
                    DeliveryMen::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                    DeliveryMen::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
            if ($inserted) {
                return response()->json($res->message);
            } else
                return response()->json(["message" => "Error on Add Delivery Man", 'code' => 0, 'errors' => []], 500);
        } else
            return response()->json($res->message);
    }
    public function refreshToken(Request $request)
    {
        return $this->refreshOurToken($request, $this->appId);
    }
}