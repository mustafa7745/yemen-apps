<?php
namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Traits\AllShared;
use App\Traits\UsersControllerShared;
use Illuminate\Http\Request;

class UserControllerUpdate extends Controller
{
    use UsersControllerShared;



    public function logout(Request $request)
    {
        $myData = $this->getMyData($request, null, false);
        $accessToken = $myData['accessToken'];
        return $this->ourLogout($accessToken->userSessionId);
    }
    public function updateProfile(Request $request)
    {
        $app = $this->getMyApp($request);
        return $this->updateOurProfile($request, $app->id);
    }

}
