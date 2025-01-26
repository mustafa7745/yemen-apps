<?php
namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Traits\AllShared;
use App\Traits\UsersControllerShared;
use Illuminate\Http\Request;

class UserControllerUpdate extends Controller
{
    use UsersControllerShared;
    use AllShared;



    public function logout(Request $request)
    {
        $app = $this->getMyApp($request);
        return $this->ourLogout($request, $app->id);
    }
    public function updateProfile(Request $request)
    {
        $app = $this->getMyApp($request);
        return $this->updateOurProfile($request, $app->id);
    }
    
}
