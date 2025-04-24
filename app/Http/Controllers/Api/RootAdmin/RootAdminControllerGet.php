<?php
namespace App\Http\Controllers\Api\RootAdmin;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Controller;
use App\Models\Users;
use App\Traits\AllShared;
use App\Traits\RootAdminControllerShared;
use DB;
use Illuminate\Http\Request;

class RootAdminControllerGet extends Controller
{
    use RootAdminControllerShared;
    use AllShared;


    public function login(Request $request)
    {
        return (new LoginController($this->appId))->login($request);
    }

    public function getUsers(Request $request)
    {
        $options = DB::table(table: Users::$tableName)->get();
        return response()->json($options);
    }


}