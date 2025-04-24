<?php
namespace App\Http\Controllers\Api\RootAdmin;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Controller;
use App\Traits\AllShared;
use App\Traits\RootAdminControllerShared;
use Illuminate\Http\Request;

class RootAdminControllerUpdate extends Controller
{
    use RootAdminControllerShared;
    use AllShared;


    public function login(Request $request)
    {
        return (new LoginController($this->appId))->login($request);
    }
    public function logout(Request $request)
    {
        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: false);
        $accessToken = $myData['accessToken'];
        return $this->ourLogout($accessToken->userSessionId);
    }
}