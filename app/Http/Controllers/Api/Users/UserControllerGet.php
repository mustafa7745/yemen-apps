<?php
namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Controller;
use App\Models\AppStores;
use App\Models\SharedStoresConfigs;
use App\Models\Stores;
use App\Traits\AllShared;
use App\Traits\UsersControllerShared;
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
        $app = $this->getMyApp($request);
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
        return $this->getOurProducts($request);
    }
    public function getLocations(Request $request)
    {
        $app = $this->getMyApp($request);
        return $this->getOurLocations($request, $app->id);
    }
}