<?php
namespace App\Http\Controllers\Api;

use App\Models\AccessTokens1;
use App\Models\Apps;
use App\Models\Categories;
use App\Models\Devices;
use App\Models\DevicesSessions;
use App\Models\MyResponse;
use App\Models\Users;
use App\Models\UsersSessions;
use Carbon\Carbon;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginController
{
    private $appId;
    public function __construct($appId)
    {
        $this->appId = $appId;
    }
    public function login(Request $request)
    {
        $app = $this->getApp($request);
        if ($app->isSuccess == false) {
            return response()->json(["message" => $app->message, 'code' => $app->code], $app->responseCode);
        }
        $phone = $request->input('phone');
        $password = $request->input('password');

        $device = $this->getDevice(request: $request);
        $deviceSession = $this->getDeviceSession($request, $device->id);


        $user = DB::table(table: Users::$tableName)
            ->where(Users::$tableName . '.' . Users::$phone, '=', $phone)
            ->where(Users::$tableName . '.' . Users::$password, '=', $password)
            ->first();
        if ($user == null) {
            return response()->json(["message" => "Phone Or Password Error", 'code' => 0 , 'eroors'=>[]], 400);
        }
        $this->updateAppToken($request, $deviceSession);

        $userSession = $this->getUserFinalSession($user->id, $deviceSession->id);
        if ($userSession == false) {
            return response()->json(["message" => "لايمكنك تسجيل الدخول في حال وجود جهاز اخر مسجل", 'code' => 0], 400);
        }

        $accessToken = $this->getAccessTokenByUserSessionId($userSession->id);
        return response()->json(["token" => $accessToken->token, 'expireAt' => $accessToken->expireAt]);
    }

    private function getDevice(Request $request)
    {
        $deviceId = $request->input('deviceId');
        $model = $request->input('model');
        $version = $request->input('version');
        $device = DB::table(table: Devices::$tableName)
            ->where(Devices::$tableName . '.' . Devices::$deviceId, '=', $deviceId)
            ->first();

        if ($device == null) {
            $insertedId = DB::table(Devices::$tableName)->insertGetId([
                Devices::$id => null,
                Devices::$model => $model,
                Devices::$deviceId => $deviceId,
                Devices::$version => $version,
                Devices::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
            return DB::table(table: Devices::$tableName)
                ->where(Devices::$tableName . '.' . Devices::$id, '=', $insertedId)
                ->first();
        }
        return $device;
    }
    private function getDeviceSession(Request $request, $deviceId)
    {
        $appToken = $request->input('appToken');
        $deviceSession = DB::table(table: DevicesSessions::$tableName)
            ->where(DevicesSessions::$tableName . '.' . DevicesSessions::$deviceId, '=', $deviceId)
            ->where(DevicesSessions::$tableName . '.' . DevicesSessions::$appId, '=', $this->appId)
            ->first();

        if ($deviceSession == null) {
            $insertedId = DB::table(DevicesSessions::$tableName)->insertGetId([
                DevicesSessions::$id => null,
                DevicesSessions::$appId => $this->appId,
                DevicesSessions::$deviceId => $deviceId,
                DevicesSessions::$appToken => $appToken,
                DevicesSessions::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                DevicesSessions::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),

            ]);
            return DB::table(table: DevicesSessions::$tableName)
                ->where(DevicesSessions::$tableName . '.' . DevicesSessions::$id, '=', $insertedId)
                ->first();
        }
        return $deviceSession;
    }
    private function getUserFinalSession($userId, $deviceSessionId)
    {
        $userSession = $this->getUserSession($userId);

        if (count($userSession) == 1) {
            if ($userSession[0]->deviceSessionId != $deviceSessionId) {
                return false;
            }
            return $this->updateLastLoginAt($userSession[0]);
        } else {
            $userSession = $this->getUserSessionByUserIdAndDevicesSessionId($userId, $deviceSessionId);
            if (count($userSession) == 0) {
                $insertedId = DB::table(UsersSessions::$tableName)->insertGetId([
                    UsersSessions::$id => null,
                    UsersSessions::$userId => $userId,
                    UsersSessions::$deviceSessionId => $deviceSessionId,
                    UsersSessions::$lastLoginAt => Carbon::now()->format('Y-m-d H:i:s'),
                    UsersSessions::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                    UsersSessions::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),

                ]);
                return DB::table(table: UsersSessions::$tableName)
                    ->where(UsersSessions::$tableName . '.' . UsersSessions::$id, '=', $insertedId)
                    ->first();
            } elseif (count($userSession) == 1) {
                return $this->updateLastLoginAt($userSession[0]);
            } else {
                abort(405, "error: 6748");
            }

        }
    }
    private function getUserSession($userId)
    {
        return DB::table(table: UsersSessions::$tableName)
            ->where(UsersSessions::$tableName . '.' . UsersSessions::$userId, '=', $userId)
            ->where(UsersSessions::$tableName . '.' . UsersSessions::$isLogin, '=', 1)
            ->where(DevicesSessions::$tableName . '.' . DevicesSessions::$appId, '=', $this->appId)
            ->join(
                DevicesSessions::$tableName,
                DevicesSessions::$tableName . '.' . Categories::$id,
                '=',
                UsersSessions::$tableName . '.' . UsersSessions::$deviceSessionId
            )
            // ->where(UsersSessions::$tableName . '.' . UsersSessions::$deviceSessionId, '<>', $deviceSessionId)
            ->get([
                UsersSessions::$tableName . '.' . UsersSessions::$id . ' as id',
                DevicesSessions::$tableName . '.' . DevicesSessions::$appId . ' as appId',
                DevicesSessions::$tableName . '.' . DevicesSessions::$id . ' as deviceSessionId',
                UsersSessions::$tableName . '.' . UsersSessions::$userId . ' as userId',
            ])->toArray();
    }
    private function getUserSessionByUserIdAndDevicesSessionId($userId, $deviceSessionId)
    {
        return DB::table(table: UsersSessions::$tableName)
            ->where(UsersSessions::$tableName . '.' . UsersSessions::$userId, '=', $userId)
            ->where(UsersSessions::$tableName . '.' . UsersSessions::$deviceSessionId, '=', $deviceSessionId)
            ->get()->toArray();
    }
    private function updateAppToken(Request $request, $deviceSession)
    {
        $appToken = $request->input('appToken');
        if ($appToken != $deviceSession->appToken) {
            DB::table(table: DevicesSessions::$tableName)
                ->where(DevicesSessions::$tableName . '.' . DevicesSessions::$id, '=', $deviceSession->id)
                ->update([
                    DevicesSessions::$tableName . '.' . DevicesSessions::$appToken => $appToken,
                    DevicesSessions::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
        }
    }
    private function updateLastLoginAt($userSession)
    {
        DB::table(table: UsersSessions::$tableName)
            ->where(UsersSessions::$tableName . '.' . UsersSessions::$id, '=', $userSession->id)
            ->update([
                UsersSessions::$loginCount => DB::raw(UsersSessions::$loginCount . ' + 1'), //h
                UsersSessions::$isLogin => 1,
                UsersSessions::$lastLoginAt => Carbon::now()->format('Y-m-d H:i:s'),
                UsersSessions::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        return DB::table(table: UsersSessions::$tableName)
            ->where(UsersSessions::$tableName . '.' . UsersSessions::$id, '=', $userSession->id)
            ->first();
    }
    private function getApp(Request $request)
    {
        $sha = $request->input('sha');
        $packageName = $request->input('packageName');

        $app = DB::table(table: Apps::$tableName)
            ->where(Apps::$tableName . '.' . Apps::$packageName, '=', $packageName)
            ->where(Apps::$tableName . '.' . Apps::$sha, '=', $sha)
            ->first();

        if ($app == null) {
            // return response()->json(["message" => "App not Auth", 'code' => 0], 403);

            return (new MyResponse(false, "App not Auth", 403, 105));
        }
        if ($app->id != $this->appId) {
            // return response()->json(["message" => "App not in Auth", 'code' => 0], 403);
            return (new MyResponse(false, "App not in Auth", 403, 106));
        }
        return (new MyResponse(true, $app, 200, 0));
    }
    private function getAccessTokenByUserSessionId($userSessionId)
    {
        $accessToken = DB::table(table: AccessTokens1::$tableName)
            ->join(
                UsersSessions::$tableName,
                UsersSessions::$tableName . '.' . UsersSessions::$id,
                '=',
                AccessTokens1::$tableName . '.' . AccessTokens1::$userSessionId
            )
            ->join(
                Users::$tableName,
                Users::$tableName . '.' . Users::$id,
                '=',
                UsersSessions::$tableName . '.' . UsersSessions::$userId
            )
            ->where(AccessTokens1::$tableName . '.' . AccessTokens1::$userSessionId, '=', $userSessionId)
            ->first([
                AccessTokens1::$tableName . '.' . AccessTokens1::$id . ' as id',
                AccessTokens1::$tableName . '.' . AccessTokens1::$token . ' as token',
                AccessTokens1::$tableName . '.' . AccessTokens1::$expireAt . ' as expireAt',
                    //
                Users::$tableName . '.' . Users::$id . ' as userId',
                Users::$tableName . '.' . Users::$firstName . ' as firstName',
                Users::$tableName . '.' . Users::$lastName . ' as lastName',
                Users::$tableName . '.' . Users::$logo . ' as logo',
            ]);

        if ($accessToken == null) {
            $insertedId = DB::table(AccessTokens1::$tableName)->insertGetId([
                AccessTokens1::$id => null,
                AccessTokens1::$token => $this->getUniqueToken(),
                AccessTokens1::$userSessionId => $userSessionId,
                AccessTokens1::$expireAt => $this->getRemainedMinute(),
                AccessTokens1::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                AccessTokens1::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),

            ]);
            return DB::table(table: AccessTokens1::$tableName)
                ->where(AccessTokens1::$tableName . '.' . AccessTokens1::$id, '=', $insertedId)
                ->first();
        }
        if ($this->compareExpiration($accessToken)) {
            $this->refreshAccessToken($accessToken->token);
        }
        return $accessToken;

    }
    function getAccessTokenByToken($token, $deviceId)
    {
        $accessToken = DB::table(table: AccessTokens1::$tableName)
            ->where(AccessTokens1::$tableName . '.' . AccessTokens1::$token, '=', $token)
            ->where(DevicesSessions::$tableName . '.' . DevicesSessions::$appId, '=', $this->appId)
            ->where(Devices::$tableName . '.' . Devices::$deviceId, '=', $deviceId)
            ->join(
                UsersSessions::$tableName,
                UsersSessions::$tableName . '.' . UsersSessions::$id,
                '=',
                AccessTokens1::$tableName . '.' . AccessTokens1::$userSessionId
            )
            ->join(
                DevicesSessions::$tableName,
                DevicesSessions::$tableName . '.' . DevicesSessions::$id,
                '=',
                UsersSessions::$tableName . '.' . UsersSessions::$deviceSessionId
            )
            ->join(
                Users::$tableName,
                Users::$tableName . '.' . Users::$id,
                '=',
                UsersSessions::$tableName . '.' . UsersSessions::$userId
            )
            ->join(
                Devices::$tableName,
                Devices::$tableName . '.' . Devices::$id,
                '=',
                DevicesSessions::$tableName . '.' . DevicesSessions::$deviceId
            )
            ->first([
                AccessTokens1::$tableName . '.' . AccessTokens1::$id . ' as id',
                AccessTokens1::$tableName . '.' . AccessTokens1::$token . ' as token',
                AccessTokens1::$tableName . '.' . AccessTokens1::$expireAt . ' as expireAt',
                    //
                Users::$tableName . '.' . Users::$id . ' as userId',
                Users::$tableName . '.' . Users::$firstName . ' as firstName',
                Users::$tableName . '.' . Users::$lastName . ' as lastName',
                Users::$tableName . '.' . Users::$logo . ' as logo',
                    //
                DevicesSessions::$tableName . '.' . DevicesSessions::$appId . ' as appId',
                DevicesSessions::$tableName . '.' . DevicesSessions::$deviceId . ' as deviceId',
            ]);
        // print_r($accessToken->toSql());
        if ($accessToken == null) {
            return new MyResponse(false, "Inv Tok", 403, 2000);
        }
        // print_r($accessToken);
        return new MyResponse(true, $accessToken, 200, 20005);
        // return $accessToken;
    }

    private function getUniqueToken()
    {
        $baseToken = md5(uniqid(mt_rand(), true));

        // Special characters to include in the token
        $specialChars = '!@#$%^&*()-_=+[]{}|;:,.<>?/~';

        // Number of special characters to insert
        $numSpecialChars = 5; // For example, inserting 5 special characters

        // Convert the token to an array of characters
        $tokenArray = str_split($baseToken);

        // Randomly insert special characters into the token
        for ($i = 0; $i < $numSpecialChars; $i++) {
            $randomPosition = mt_rand(0, count($tokenArray) - 1); // Choose random position
            $randomSpecialChar = $specialChars[mt_rand(0, strlen($specialChars) - 1)]; // Choose random special char
            array_splice($tokenArray, $randomPosition, 0, $randomSpecialChar); // Insert special char
        }

        // Convert the array back to a string
        $uniqueTokenWithSpecialChars = implode('', $tokenArray);

        return $uniqueTokenWithSpecialChars;
    }
    private function getRemainedMinute($minutes = null)
    {
        if ($minutes === null) {
            // Get the end of the day (tomorrow at 00:00:00 - 1 second)
            $end_of_day = Carbon::tomorrow()->startOfDay()->subSecond();
            return $end_of_day->format('Y-m-d H:i:s');
        } else {
            // Add minutes to the current time
            $date = Carbon::now()->addMinutes($minutes);
            return $date->format('Y-m-d H:i:s');
        }

    }
    private function compareExpiration($loginToken)
    {
        // Get current time using Carbon
        $currentDate = Carbon::now();

        // Get the expiration date from the $loginToken object and convert it to a Carbon instance
        $expireAt = Carbon::parse($loginToken->expireAt);

        // Compare the dates
        if ($currentDate->gt($expireAt)) {
            // Current time is greater than expiration time (token expired)
            return true;
        } else {
            // Token is still valid
            return false;
        }
    }
    function readAndRefreshAccessToken($preToken, $deviceId)
    {
        $accessToken = $this->getAccessTokenByToken($preToken, $deviceId);
        if ($this->compareExpiration($accessToken->message)) {
            return $this->refreshAccessToken($preToken);
        }
        return $accessToken;
    }
    function readAccessToken($token, $deviceId)
    {
        $myResult = $this->getAccessTokenByToken($token, $deviceId);

        if ($myResult->isSuccess == false) {
            return $myResult;
        }
        // print_r($myResult->message);
        if ($this->compareExpiration($myResult->message)) {
            // print_r("sdsdsd");
            return new MyResponse(false, "Need refresh", 405, 1000);
        }


        return $myResult;
    }
    private function refreshAccessToken($preToken)
    {
        $newToken = $this->getUniqueToken();
        DB::table(table: AccessTokens1::$tableName)
            ->where(AccessTokens1::$tableName . '.' . AccessTokens1::$token, '=', $preToken)
            ->update([
                AccessTokens1::$expireAt => $this->getRemainedMinute(), //h
                AccessTokens1::$token => $newToken,
                AccessTokens1::$refreshCount => DB::raw(AccessTokens1::$refreshCount . ' + 1'),
                AccessTokens1::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        return DB::table(table: AccessTokens1::$tableName)
            ->where(AccessTokens1::$tableName . '.' . AccessTokens1::$token, '=', $newToken)
            ->first();
    }
    function exitFromScript($message, $response_code = 400, $code = 0)
    {
        http_response_code($response_code);
        $res = json_encode(array("code" => $code, "message" => $message));
        die($res);
    }
}
