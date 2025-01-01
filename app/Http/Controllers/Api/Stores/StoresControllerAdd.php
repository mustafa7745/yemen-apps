<?php
namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Controller;
use App\Models\Locations;
use App\Models\Sections;
use App\Traits\StoresControllerShared;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Validator;

class StoresControllerAdd extends Controller
{
    use StoresControllerShared;

    public function addLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'accessToken' => 'required|string|max:255',
            'deviceId' => 'required|string|max:255',
            'latLng' => 'required|string|max:100',
            'street' => 'required|string|max:100',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            // Return a JSON response with validation errors
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 0
            ], 422);  // 422 Unprocessable Entity
        }


        $loginController = (new LoginController($this->appId));
        $token = $request->input('accessToken');
        $deviceId = $request->input('deviceId');

        // print_r($request->all());
        $myResult = $loginController->readAccessToken($token, $deviceId);
        if ($myResult->isSuccess == false) {
            return response()->json(['message' => $myResult->message, 'code' => $myResult->code], $myResult->responseCode);
        }
        $accessToken = $myResult->message;
        // 
        $latLng = $request->input('latLng');
        $street = $request->input('street');
        $insertedId = DB::table(table: Locations::$tableName)
            ->insertGetId([
                Locations::$id => null,
                Locations::$userId => $accessToken->userId,
                Locations::$latLng => $latLng,
                Locations::$street => $street,
                Locations::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                Locations::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

        $category = DB::table(Locations::$tableName)
            ->where(Locations::$tableName . '.' . Sections::$id, '=', $insertedId)
            ->sole([
                Locations::$tableName . '.' . Locations::$id,
                Locations::$tableName . '.' . Locations::$street,
            ]);
        return response()->json($category);
    }
}
