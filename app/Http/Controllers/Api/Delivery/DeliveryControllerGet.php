<?php
namespace App\Http\Controllers\Api\Delivery;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Controller;
use App\Traits\AllShared;
use App\Traits\DeliveryControllerShared;
use Illuminate\Http\Request;

class DeliveryControllerGet extends Controller
{
    use DeliveryControllerShared;
    use AllShared;


    public function login(Request $request)
    {
        return (new LoginController($this->appId))->login($request);
    }
    public function refreshToken(Request $request)
    {
        return $this->refreshOurToken($request, $this->appId);
    }
}