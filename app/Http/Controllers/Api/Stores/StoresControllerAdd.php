<?php
namespace App\Http\Controllers\Api\Stores;

use App\Http\Controllers\Controller;
use App\Traits\AllShared;
use App\Traits\StoresControllerShared;
use Illuminate\Http\Request;


class StoresControllerAdd extends Controller
{
    use StoresControllerShared;
    use AllShared;

    public function addLocation(Request $request)
    {
        return $this->addOurLocation($request, $this->appId);
    }
}
