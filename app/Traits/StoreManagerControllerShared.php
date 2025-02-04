<?php
namespace App\Traits;
use App\Http\Controllers\Api\LoginController;

trait StoreManagerControllerShared
{
    use AllShared;
    public $appId = 1;
    public function getMyData($request, $myProcessName = null)
    {
        $app = $this->getMyApp($request);
        $accessToken = (new LoginController($this->appId))->getAccessTokenByTokenV1($request);
        $store = $this->getMyStore($request, $accessToken->userId);
        $myProcess = null;
        if ($myProcessName != null) {
            $myProcess = $this->checkProcessV1($myProcessName, $accessToken->deviceId, $accessToken->userId);
        }
        return ['app' => $app, 'accessToken' => $accessToken, 'store' => $store, 'myProcess' => $myProcess];
    }
}