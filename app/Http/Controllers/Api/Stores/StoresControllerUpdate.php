<?php
namespace App\Http\Controllers\Api\Stores;

use App\Http\Controllers\Controller;
use App\Traits\AllShared;
use App\Traits\StoresControllerShared;
use Illuminate\Http\Request;


class StoresControllerUpdate extends Controller
{
    use StoresControllerShared;
    use AllShared;

    public function updateProfile(Request $request)
    {
        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: false, withUser: true);
        $accessToken = $myData['accessToken'];
        return $this->updateOurProfile($request, $accessToken->userId);
    }
}
