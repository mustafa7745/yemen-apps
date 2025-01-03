<?php
namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Traits\AllShared;
use App\Traits\UsersControllerShared;
use Illuminate\Http\Request;

class UserControllerAdd extends Controller
{
    use UsersControllerShared;
    use AllShared;

    public function addLocation(Request $request)
    {
        $app = $this->getMyApp($request);
        return $this->addOurLocation($request, $app->id);
    }
}
