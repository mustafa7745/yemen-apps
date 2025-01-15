<?php
namespace App\Http\Controllers\Api\Stores;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Controller;
use App\Models\Locations;
use App\Models\PaymentTypes;
use App\Models\SharedStoresConfigs;
use App\Models\StoreInfo;
use App\Models\StoreNestedSections;
use App\Models\Options;
use App\Models\StorePaymentTypes;
use App\Models\Stores;
use App\Models\ProductImages;
use App\Models\Products;
use App\Models\StoreProducts;
use App\Traits\AllShared;
use App\Traits\StoresControllerShared;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Validator;

class StoresControllerGet extends Controller
{
    use StoresControllerShared;
    use AllShared;
    public function index()
    {

        $stores = DB::table(Stores::$tableName)

            ->get()->toArray();

        $storeIds = [];
        foreach ($stores as $store) {
            if ($store->typeId == 1) {
                $storeIds[] = $store->id;
            }
        }

        $storeConfigs = DB::table(table: SharedStoresConfigs::$tableName)
            ->whereIn(SharedStoresConfigs::$tableName . '.' . SharedStoresConfigs::$storeId, $storeIds)
            ->get();

        // print_r($storeConfigs);





        foreach ($storeConfigs as $storeConfig) {
            foreach ($stores as $index => $store) {
                // print_r($storeConfig);
                if ($storeConfig->storeId == $store->id && $store->typeId == 1) {
                    $categories = json_decode($storeConfig->categories);
                    $sections = json_decode($storeConfig->sections);
                    $nestedSections = json_decode($storeConfig->nestedSections);
                    $products = json_decode($storeConfig->products);
                    // $stores[$index] = (array)$stores[$index];
                    $stores[$index]->storeConfig = ['storeIdReference' => $storeConfig->storeIdReference, 'categories' => $categories, 'sections' => $sections, 'nestedSections' => $nestedSections, 'products' => $products];
                } else
                    $stores[$index]->storeConfig = null;
            }
        }

        return response()->json($stores);
    }
    public function getProducts(Request $request)
    {
        return $this->getOurProducts($request);
    }
    public function getStores(Request $request)
    {

        $stores = DB::table(Stores::$tableName)
            // ->where(Stores::$tableName . '.' . Stores::$userId, '=', $accessToken->userId)
            ->get()->toArray();

        $storeIds = [];
        foreach ($stores as $store) {
            if ($store->typeId == 1) {
                $storeIds[] = $store->id;
            }
        }

        $storeConfigs = DB::table(table: SharedStoresConfigs::$tableName)
            ->whereIn(SharedStoresConfigs::$tableName . '.' . SharedStoresConfigs::$storeId, $storeIds)
            ->get();

        // print_r($storeConfigs);





        foreach ($storeConfigs as $storeConfig) {
            foreach ($stores as $index => $store) {
                // print_r($storeConfig);
                if ($storeConfig->storeId == $store->id && $store->typeId == 1) {
                    $categories = json_decode($storeConfig->categories);
                    $sections = json_decode($storeConfig->sections);
                    $nestedSections = json_decode($storeConfig->nestedSections);
                    $products = json_decode($storeConfig->products);
                    // $stores[$index] = (array)$stores[$index];
                    $stores[$index]->storeConfig = ['storeIdReference' => $storeConfig->storeIdReference, 'categories' => $categories, 'sections' => $sections, 'nestedSections' => $nestedSections, 'products' => $products];
                } else
                    $stores[$index]->storeConfig = null;
            }
        }

        return response()->json($stores);
    }

    public function getLocations(Request $request)
    {
        return $this->getOurLocations($request, $this->appId);
    }
    public function getHome(Request $request)
    {
        return $this->getOurHome($request);
    }

    public function getStoreInfo(Request $request)
    {
        // $storeId = 1;
        $storeId = $request->input('storeId');
        $data = DB::table(table: StoreInfo::$tableName)
            ->where(StoreInfo::$tableName . '.' . StoreInfo::$storeId, '=', $storeId)
            ->get(
            );

        return response()->json($data);
    }

   

    public function login(Request $request)
    {
        return (new LoginController($this->appId))->login($request);
    }
    public function refreshToken(Request $request)
    {
        return $this->refreshOurToken($request, $this->appId);
    }

}
