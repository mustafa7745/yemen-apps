<?php
namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\AppStores;
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

    public function getStores(Request $request)
    {
        $app = $this->getMyApp($request);
        // 
        $data = DB::table(AppStores::$tableName)
            ->where(AppStores::$appId, $app->id)
            ->get();
        return $data;
    }
}