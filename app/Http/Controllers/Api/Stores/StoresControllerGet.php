<?php
namespace App\Http\Controllers\Api\Stores;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Controller;
use App\Models\Countries;
use App\Models\Currencies;
use App\Models\Languages;
use App\Models\Locations;
use App\Models\MainCategories;
use App\Models\PaymentTypes;
use App\Models\SharedStoresConfigs;
use App\Models\StoreCurencies;
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

    function index()
    {
        return response()->json(['userInfo' => "fdfdfdf"]);
    }
    public function getStores(Request $request)
    {
        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: false, withUser: true);
        $accessToken = $myData['accessToken'];
        $mainCategoryId = $request->input('mainCategoryId');

        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        $isLocationNull = is_null($latitude) || is_null($longitude);
        // print_r($latitude.$longitude);
        // print_r($isLocationNull ? "null":"not null");

        $stores = DB::table(Stores::$tableName)
            ->where(Stores::$tableName . '.' . Stores::$mainCategoryId, '=', $mainCategoryId)
            ->where(Stores::$tableName . '.' . Stores::$countryId, '=', $accessToken->countryId)
            ->where(Stores::$tableName . '.' . Stores::$latLong, '<>', null)

            ->get(
                [
                    Stores::$tableName . '.' . Stores::$id,
                    Stores::$tableName . '.' . Stores::$typeId,
                    Stores::$tableName . '.' . Stores::$name,
                    Stores::$tableName . '.' . Stores::$logo,
                    Stores::$tableName . '.' . Stores::$cover,
                    Stores::$tableName . '.' . Stores::$cover,
                    Stores::$tableName . '.' . Stores::$reviews,
                    Stores::$tableName . '.' . Stores::$subscriptions,
                    Stores::$tableName . '.' . Stores::$stars,
                    Stores::$tableName . '.' . Stores::$likes,
                    DB::raw(
                        $isLocationNull
                        ? "NULL AS distance" // Return NULL if latitude or longitude is null
                        : "ROUND(ST_Distance_Sphere(ST_GeomFromText('POINT($latitude $longitude)', 4326), " . Stores::$tableName . '.' . Stores::$latLong . ") * 1.45 / 1000, 2) AS distance"
                    ),
                ]
            );

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


        $storeCurrencies = DB::table(table: StoreCurencies::$tableName)
            ->join(
                Currencies::$tableName,
                Currencies::$tableName . '.' . Currencies::$id,
                '=',
                StoreCurencies::$tableName . '.' . StoreCurencies::$currencyId
            )
            ->whereIn(StoreCurencies::$tableName . '.' . StoreCurencies::$storeId, $storeIds)
            ->get([
                Currencies::$tableName . '.' . Currencies::$id . ' as currencyId',
                Currencies::$tableName . '.' . Currencies::$name . ' as currencyName',
                StoreCurencies::$tableName . '.' . StoreCurencies::$id,
                StoreCurencies::$tableName . '.' . StoreCurencies::$lessCartPrice,
                StoreCurencies::$tableName . '.' . StoreCurencies::$storeId,
                StoreCurencies::$tableName . '.' . StoreCurencies::$freeDeliveryPrice,
                StoreCurencies::$tableName . '.' . StoreCurencies::$deliveryPrice,
                StoreCurencies::$tableName . '.' . StoreCurencies::$isSelected,
                StoreCurencies::$tableName . '.' . StoreCurencies::$countUsed,
            ]);

        foreach ($stores as $index => $store) {
            $res = [];
            foreach ($storeCurrencies as $key => $storeCurrency) {
                if ($store->id == $storeCurrency->storeId) {
                    $res[] = $storeCurrency;
                }
            }
            $stores[$index]->storeCurrencies = $res;
        }


        return response()->json($stores);
    }
    public function getMain(Request $request)
    {
        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: false, withUser: true);
        $accessToken = $myData['accessToken'];


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
                Users::$tableName . '.' . Users::$logo,
                Countries::$tableName . '.' . Countries::$image . ' as flag',
                Countries::$tableName . '.' . Countries::$name . ' as countryName',
            ]);
        // $userInfo
        $lang = $this->getLanguage($request);
        $d = json_decode($userInfo->countryName, true);
        $userInfo->countryName = $d[$lang];


        return response()->json(['userInfo' => $userInfo, 'categories' => $mainCatgories]);
    }

    public function getUserProfile(Request $request)
    {
        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: false, withUser: true);
        $accessToken = $myData['accessToken'];
        return $this->getOurUserProfile($accessToken->userId);
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
    public function getLanguages(Request $request)
    {
        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: false, withUser: false);
        // $store = $myData['store'];
        // // $app = $myData['app'];

        // // if ($withSituations === true) {
        $languages = DB::table(Languages::$tableName)
            ->get();




        return response()->json($languages);
    }
    public function getProducts(Request $request)
    {
        return $this->getOurProducts2($request);
    }

    public function getLocations(Request $request)
    {
        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: false, withUser: true);
        $storeId = $request->input('storeId');
        // $app = $myData['app'];
        $accessToken = $myData['accessToken'];

        return $this->getOurLocations( $accessToken->userId,$storeId);
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
        return (new LoginController($this->appId,$request))->login($request);
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
