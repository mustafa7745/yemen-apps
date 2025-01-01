<?php
namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Controller;
use App\Models\AppStores;
use App\Models\Stores;
use App\Traits\UsersControllerShared;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class UserControllerGet extends Controller
{
    use UsersControllerShared;

    public function getApp(Request $request)
    {
        return response()->json($this->getMyApp($request));
    }

    public function login(Request $request)
    {
        $app = $this->getMyApp($request);
        return (new LoginController($app->id))->login($request);
    }

    public function getStores(Request $request)
    {
        $app = $this->getMyApp($request);
        // 
        $data = DB::table(AppStores::$tableName)
            ->join(
                Stores::$tableName,
                Stores::$tableName . '.' . Stores::$id,
                '=',
                AppStores::$tableName . '.' . AppStores::$storeId
            )

            ->where(AppStores::$appId, $app->id)
            ->get([
                Stores::$tableName . '.' . Stores::$id,
                Stores::$tableName . '.' . Stores::$name,
                Stores::$tableName . '.' . Stores::$logo,
                Stores::$tableName . '.' . Stores::$cover,
                Stores::$tableName . '.' . Stores::$typeId,
                Stores::$tableName . '.' . Stores::$likes,
                Stores::$tableName . '.' . Stores::$subscriptions,
                Stores::$tableName . '.' . Stores::$stars,
                Stores::$tableName . '.' . Stores::$reviews,


                // Stores::$tableName . '.' . Stores::$,
                // Stores::$tableName . '.' . Stores::$id,
                // Stores::$tableName . '.' . Stores::$id,
                // Stores::$tableName . '.' . Stores::$id,
                // Stores::$tableName . '.' . Stores::$id,
            ]);
        return $data;
    }
}