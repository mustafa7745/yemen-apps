<?php
namespace App\Http\Controllers\Api\Stores;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Controller;
use App\Models\Countries;
use App\Models\Languages;
use App\Models\Locations;
use App\Models\MainCategories;
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
use App\Models\Users;
use App\Traits\AllShared;
use App\Traits\StoresControllerShared;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Validator;

class StoresControllerGet extends Controller
{
    use StoresControllerShared;
    use AllShared;
    public function getMain(Request $request)
    {
        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: false, withUser: true);
        $accessToken = $myData['accessToken'];

        $stores = DB::table(Stores::$tableName)
            ->get();

        $storeIds = [];
        foreach ($stores as $store) {
            $storeIds[] = $store->id;
            // }
        }

        $storeConfigs = DB::table(table: SharedStoresConfigs::$tableName)
            ->whereIn(SharedStoresConfigs::$tableName . '.' . SharedStoresConfigs::$storeId, $storeIds)
            ->get();

        // print_r($storeConfigs);

        // First, filter the storeConfigs by storeIds (matching storeConfig's storeId to store's id)
        $filteredStoreConfigs = collect($storeConfigs)->keyBy('storeId');

        // Now, update the stores with the corresponding storeConfig data
        foreach ($stores as $index => $store) {
            if ($store->typeId == 1 && isset($filteredStoreConfigs[$store->id])) {
                $storeConfig = $filteredStoreConfigs[$store->id];

                // Handle JSON decoding and checking for errors
                $categories = json_decode($storeConfig->categories);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $categories = [];  // Handle invalid JSON
                }

                $sections = json_decode($storeConfig->sections);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $sections = [];  // Handle invalid JSON
                }

                $nestedSections = json_decode($storeConfig->nestedSections);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $nestedSections = [];  // Handle invalid JSON
                }

                $products = json_decode($storeConfig->products);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $products = [];  // Handle invalid JSON
                }

                // Merge the storeConfig data into the store object
                $stores[$index]->storeConfig = [
                    'storeIdReference' => $storeConfig->storeIdReference,
                    'categories' => $categories,
                    'sections' => $sections,
                    'nestedSections' => $nestedSections,
                    'products' => $products
                ];
            } else {
                // If storeConfig doesn't exist for the store or doesn't match, set storeConfig to null
                $stores[$index]->storeConfig = null;
            }
        }

        $mainCatgories = DB::table(table: MainCategories::$tableName)
            ->get();

        $userInfo = DB::table(table: Users::$tableName)
            ->where(Users::$tableName . '.' . Users::$id, '=', $accessToken->userId)

            ->join(
                Countries::$tableName,
                Countries::$tableName . '.' . Countries::$id,
                '=',
                Users::$tableName . '.' . Users::$countryId
            )
            ->first([
                Users::$tableName . '.' . Users::$firstName,
                Users::$tableName . '.' . Users::$lastName,
                Countries::$tableName . '.' . Countries::$image,
                Countries::$tableName . '.' . Countries::$name,
            ]);
            // $userInfo
            $lang = $this->getLanguage($request);
            $d = json_decode($userInfo->name, true);
            $userInfo->name = $d[$lang];
        //  [
        //     ['name'=>'المطاعم','image'=>],
        //     ['name'=>'الهواتف الذكية وملحقاتها','image'=>],
        //     ['name'=>'الملابس والأحذية','image'=>],
        //     ['name'=>'المواد البلاستيكية','image'=>],
        //     ['name'=>'المستلزمات المكتبية','image'=>],
        //     ['name'=>'الاثاث','image'=>],
        //     ['name'=>'العطور','image'=>],
        //     ['name'=>'المواد الغذائية','image'=>],
        //     ['name'=>'مواد البناء','image'=>],
        //     ['name'=>'الشنط','image'=>],
        //     ['name'=>'الاجهزة الكهربائية','image'=>],
        //     ['name'=>'الالكترونيات','image'=>],
        //     ['name'=>'الحواسيب ومستلزماتها','image'=>],

        // ];

        return response()->json(['userInfo' => $userInfo, 'stores' => $stores, 'categories' => $mainCatgories]);
    }
    public function getLoginConfiguration(Request $request)
    {
        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: false, withUser: false);
        // $store = $myData['store'];
        // // $app = $myData['app'];

        // // if ($withSituations === true) {
        $languages = DB::table(Languages::$tableName)
            ->get();
        $countries = DB::table(Countries::$tableName)
            ->get();
        $lang = $this->getLanguage($request);

        foreach ($countries as $key => $value) {
            $data = json_decode($value->name, true);
            $countries[$key]->name = $data[$lang];
        }

        return response()->json(['languages' => $languages, 'countries' => $countries]);
    }
    public function getProducts(Request $request)
    {
        return $this->getOurProducts2($request);
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
        $store = $this->getMyStore($request, null);
        return $this->getOurHome($store);
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
    public function getPaymentTypes(Request $request)
    {
        return $this->getOurPaymentTypes($request);
    }

}
