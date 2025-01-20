<?php
namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\Options;
use App\Models\Orders;
use App\Models\OrdersPayments;
use App\Models\Products;
use App\Models\StoreProducts;
use App\Models\UsersSessions;
use App\Traits\AllShared;
use App\Traits\UsersControllerShared;
use Carbon\Carbon;
use DB;
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
}
