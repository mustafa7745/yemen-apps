<?php
namespace App\Traits;
use App\Http\Controllers\Api\LoginController;
use App\Models\Products;
use App\Models\Stores;
use DB;
use Illuminate\Database\CustomException;

trait StoreManagerControllerShared
{
    use AllShared;
    public $appId = 1;

}