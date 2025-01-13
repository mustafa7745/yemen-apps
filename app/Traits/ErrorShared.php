<?php
namespace App\Traits;
use App\Http\Controllers\Api\LoginController;
use App\Models\AppStores;
use App\Models\Categories;
use App\Models\Currencies;
use App\Models\Locations;
use App\Models\MyResponse;
use App\Models\NestedSections;
use App\Models\Options;
use App\Models\Orders;
use App\Models\OrdersAmounts;
use App\Models\OrdersProducts;
use App\Models\ProductImages;
use App\Models\Products;
use App\Models\Sections;
use App\Models\SharedStoresConfigs;
use App\Models\StoreCategories;
use App\Models\StoreNestedSections;
use App\Models\StoreProducts;
use App\Models\Stores;
use App\Models\StoreSections;
use App\Models\Users;
use Carbon\Carbon;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Validator;

trait ErrorShared
{
    ///
    function queryEX(QueryException $e)
    {
        if ($e->getCode() == 23000) {
            return response()->json(['message' => "duplicate", 'code' => 0, 'errors' => []], 409);
        }
        return response()->json(['message' => "D Unkwon ", 'code' => 0, 'errors' => []], 409);
    }
    function soleEX()
    {
        return response()->json(['message' => "سجل غير موجود", 'code' => 0, 'errors' => []], 409);
    }
}