<?php
namespace App\Http\Controllers\Api;

use App\Models\Apps;
use App\Models\Categories;
use App\Models\Devices;
use App\Models\DevicesSessions;
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
        $phone = $request->input('phone');
        $password = $request->input('password');

        $device = $this->getDevice($request);
        $deviceSession = $this->getDeviceSession($request, $device->id);


        $user = DB::table(table: Users::$tableName)
            ->where(Users::$tableName . '.' . Users::$phone, '=', $phone)
            ->where(Users::$tableName . '.' . Users::$password, '=', $password)
            ->first();
        if ($user == null) {
            return response()->json(["message" => "Phone Or Password Error"], 400);
        }
        $this->updateAppToken($request, $deviceSession);

        $userSession = $this->getUserFinalSession($user->id, $deviceSession->id);
        if ($userSession == false) {
            return response()->json(["message" => "لايمكنك تسجيل الدخول في حال وجود جهاز اخر مسجل"], 400);
        }
        return response()->json($userSession);
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
            abort(
                403,

                json_encode([
                    'message' => "App not Auth"
                ])
            );
            // return response()->json(, 400)->send();
            // exit;
        }
        if ($app->id != $this->appId) {
            abort(
                403,

                json_encode([
                    'message' => "App not in Auth"
                ])
            );
        }
        return $app;
    }
}
