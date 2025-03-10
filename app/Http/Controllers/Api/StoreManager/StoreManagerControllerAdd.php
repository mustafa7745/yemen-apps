<?php
namespace App\Http\Controllers\Api\StoreManager;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Controller;
use App\Models\Apps;
use App\Models\Categories;
use App\Models\Countries;
use App\Models\Currencies;
use App\Models\CustomPrices;
use App\Models\DeliveryMen;
use App\Models\InAppProducts;
use App\Models\NestedSections;
use App\Models\Options;
use App\Models\ProductImages;
use App\Models\Products;
use App\Models\SharedableStores;
use App\Models\SharedStoresConfigs;
use App\Models\StoreAds;
use App\Models\StoreCurencies;
use App\Models\StoreDeliveryMen;
use App\Models\StoreProducts;
use App\Models\StoreSections;
use App\Models\StoreNestedSections;
use App\Models\Sections;
use App\Models\Stores;
use App\Models\StoreCategories;
use App\Models\StoreSubscriptions;
use App\Models\Users;
use App\Services\FirebaseService;
use App\Traits\AllShared;
use App\Traits\ErrorShared;
use App\Traits\StoreManagerControllerShared;
use Illuminate\Database\CustomException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Kreait\Firebase\Messaging\CloudMessage;
use libphonenumber\PhoneNumberUtil;
use Notification;
use Storage;
use Str;
use Validator;

class StoreManagerControllerAdd extends Controller
{

    use StoreManagerControllerShared;
    // use ErrorShared;
    // use AllShared;
    public function addCategory(Request $request)
    {
        $storeId = $request->input('storeId');
        $name = $request->input('name');
        $insertedId = DB::table(table: Categories::$tableName)
            ->insertGetId([
                Categories::$id => null,
                Categories::$storeId => $storeId,
                Categories::$name => $name,
                Categories::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                Categories::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

        $category = DB::table(Categories::$tableName)
            ->where(Categories::$tableName . '.' . Categories::$id, '=', $insertedId)
            ->sole([
                Categories::$tableName . '.' . Categories::$id,
                Categories::$tableName . '.' . Categories::$name,
                Categories::$tableName . '.' . Categories::$acceptedStatus,
            ]);
        return response()->json($category);
    }
    public function addSection(Request $request)
    {
        $storeId = $request->input('storeId');
        $categoryId = $request->input('categoryId');
        $name = $request->input('name');
        $insertedId = DB::table(table: Sections::$tableName)
            ->insertGetId([
                Sections::$id => null,
                Sections::$storeId => $storeId,
                Sections::$categoryId => $categoryId,
                Sections::$name => $name,
                Sections::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                Sections::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

        $category = DB::table(Sections::$tableName)
            ->where(Sections::$tableName . '.' . Sections::$id, '=', $insertedId)
            ->sole([
                Sections::$tableName . '.' . Sections::$id,
                Sections::$tableName . '.' . Sections::$name,
                Sections::$tableName . '.' . Sections::$acceptedStatus,
            ]);
        return response()->json($category);
    }
    public function addNestedSection(Request $request)
    {
        $storeId = $request->input('storeId');
        $sectionId = $request->input('sectionId');
        $name = $request->input('name');
        $insertedId = DB::table(table: NestedSections::$tableName)
            ->insertGetId([
                NestedSections::$id => null,
                NestedSections::$storeId => $storeId,
                NestedSections::$sectionId => $sectionId,
                NestedSections::$name => $name,
                NestedSections::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                NestedSections::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

        $category = DB::table(NestedSections::$tableName)
            ->where(NestedSections::$tableName . '.' . NestedSections::$id, '=', $insertedId)
            ->sole([
                NestedSections::$tableName . '.' . NestedSections::$id,
                NestedSections::$tableName . '.' . NestedSections::$name,
                NestedSections::$tableName . '.' . NestedSections::$acceptedStatus,
            ]);
        return response()->json($category);
    }
    public function addStoreCategory(Request $request)
    {
        $storeId = $request->input('storeId');
        $categoryId = $request->input('categoryId');
        $insertedId = DB::table(table: StoreCategories::$tableName)
            ->insertGetId([
                StoreCategories::$id => null,
                StoreCategories::$categoryId => $categoryId,
                StoreCategories::$storeId => $storeId,
                StoreCategories::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                StoreCategories::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        $storeCategory = DB::table(table: StoreCategories::$tableName)->where(StoreCategories::$tableName . '.' . StoreCategories::$id, '=', $insertedId)
            ->join(
                Categories::$tableName,
                Categories::$tableName . '.' . Categories::$id,
                '=',
                StoreCategories::$tableName . '.' . StoreCategories::$categoryId
            )
            ->first(
                [
                    StoreCategories::$tableName . '.' . StoreCategories::$id . ' as id',
                    Categories::$tableName . '.' . Categories::$id . ' as categoryId',
                    Categories::$tableName . '.' . Categories::$name . ' as categoryName'
                ]
            );

        return response()->json($storeCategory);
    }
    public function addStoreSection(Request $request)
    {
        $storeId = $request->input('storeId');
        $storeCategoryId = $request->input('storeCategoryId');
        $sectionId = $request->input('sectionId');

        $insertedId = DB::table(table: StoreSections::$tableName)
            ->insertGetId([
                StoreSections::$id => null,
                StoreSections::$sectionId => $sectionId,
                StoreSections::$storeCategoryId => $storeCategoryId,
                StoreSections::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                StoreSections::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        $storeCategory = DB::table(table: StoreSections::$tableName)->where(StoreSections::$tableName . '.' . StoreSections::$id, '=', $insertedId)
            ->join(
                Sections::$tableName,
                Sections::$tableName . '.' . Sections::$id,
                '=',
                StoreSections::$tableName . '.' . StoreSections::$sectionId

            )
            ->first(
                [
                    StoreSections::$tableName . '.' . StoreSections::$id . ' as id',
                    Sections::$tableName . '.' . Sections::$id . ' as sectionId',
                    Sections::$tableName . '.' . Sections::$name . ' as sectionName',
                    StoreSections::$tableName . '.' . StoreSections::$storeCategoryId . ' as storeCategoryId'

                ]
            );

        return response()->json($storeCategory);
    }
    public function addProduct(Request $request)
    {
        $storeId = $request->input('storeId');
        $nestedSectionId = $request->input('nestedSectionId');
        $name = $request->input('name');
        $description = $request->input('description');

        $insertedId = DB::table(table: Products::$tableName)
            ->insertGetId([
                Products::$id => null,
                Products::$nestedSectionId => $nestedSectionId,
                Products::$name => $name,
                Products::$storeId => $storeId,
                Products::$description => $description,
                Products::$orderNo => 9000,
                Products::$orderAt => Carbon::now()->format('Y-m-d H:i:s'),
                Products::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                Products::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        $product = DB::table(table: Products::$tableName)->where(Products::$tableName . '.' . Products::$id, '=', $insertedId)
            ->first(
                [
                    Products::$tableName . '.' . Products::$id,
                    Products::$tableName . '.' . Products::$name,
                    Products::$tableName . '.' . Products::$description,
                    Products::$tableName . '.' . Products::$acceptedStatus,
                ]
            );

        return response()->json($product);
    }
    public function addStoreNestedSection(Request $request)
    {
        $storeId = 1;
        $storeSectionId = $request->input('storeSectionId');
        $nestedSectionId = $request->input('nestedSectionId');

        $insertedId = DB::table(table: StoreNestedSections::$tableName)
            ->insertGetId([
                StoreNestedSections::$id => null,
                StoreNestedSections::$nestedSectionId => $nestedSectionId,
                StoreNestedSections::$storeSectionId => $storeSectionId,
                StoreNestedSections::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                StoreNestedSections::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        $storeCategory = DB::table(table: StoreNestedSections::$tableName)->where(StoreNestedSections::$tableName . '.' . StoreNestedSections::$id, '=', $insertedId)
            ->join(
                NestedSections::$tableName,
                NestedSections::$tableName . '.' . NestedSections::$id,
                '=',
                StoreNestedSections::$tableName . '.' . StoreNestedSections::$nestedSectionId
            )
            ->first(
                [
                    StoreNestedSections::$tableName . '.' . StoreNestedSections::$id . ' as id',
                    StoreNestedSections::$tableName . '.' . StoreNestedSections::$storeSectionId . ' as storeSectionId',
                    NestedSections::$tableName . '.' . NestedSections::$id . ' as nestedSectionId',
                    NestedSections::$tableName . '.' . NestedSections::$name . ' as nestedSectionName',
                ]
            );

        return response()->json($storeCategory);
    }

    public function addProductOption(Request $request)
    {
        $productId = $request->input('productId');
        $optionId = $request->input('optionId');
        $currencyId = $request->input('currencyId');
        $price = $request->input('price');
        $storeNestedSectionId = $request->input(key: 'storeNestedSectionId');
        $getWithProduct = $request->input(key: 'getWithProduct');
        // $storeId = $request->input('storeId');

        $myData = $this->getMyData(request: $request, appId: $this->appId);
        $accessToken = $myData['accessToken'];
        $store = $myData['store'];

        try {
            $insertedId = DB::table(table: StoreProducts::$tableName)
                ->insertGetId([
                    StoreProducts::$id => null,
                    StoreProducts::$optionId => $optionId,
                    StoreProducts::$productId => $productId,
                    StoreProducts::$price => $price,
                    StoreProducts::$currencyId => $currencyId,
                    StoreProducts::$storeNestedSectionId => $storeNestedSectionId,
                    StoreProducts::$storeId => $store->id,
                    StoreProducts::$orderNo => 9000,
                    StoreProducts::$orderAt => Carbon::now()->format('Y-m-d H:i:s'),
                    StoreProducts::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                    StoreProducts::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                ]);

            $currency = DB::table(Currencies::$tableName)
                ->where(Currencies::$id, '=', $currencyId)
                ->first();
            $storeCurrency = DB::table(table: StoreCurencies::$tableName)
                ->where(StoreCurencies::$tableName . '.' . StoreCurencies::$storeId, '=', $store->id)
                ->where(StoreCurencies::$tableName . '.' . StoreCurencies::$currencyId, '=', $currency->id)
                ->get();
            print_r($storeCurrency);
            if ($storeCurrency == null) {
                DB::table(table: StoreCurencies::$tableName)
                    ->insertGetId([
                        StoreCurencies::$id => null,
                        StoreCurencies::$currencyId => $currencyId,
                        StoreCurencies::$storeId => $store->id,
                        StoreCurencies::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                        StoreCurencies::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
            }

            $productOption = null;
            if ($getWithProduct == 1) {
                $product = DB::table(StoreProducts::$tableName)
                    // ->where(StoreProducts::$storeId, $storeId)
                    ->join(
                        Products::$tableName,
                        Products::$tableName . '.' . Products::$id,
                        '=',
                        StoreProducts::$tableName . '.' . StoreProducts::$productId
                    )
                    ->join(
                        Options::$tableName,
                        Options::$tableName . '.' . Options::$id,
                        '=',
                        StoreProducts::$tableName . '.' . StoreProducts::$optionId
                    )
                    ->join(
                        StoreNestedSections::$tableName,
                        StoreNestedSections::$tableName . '.' . StoreNestedSections::$id,
                        '=',
                        StoreProducts::$tableName . '.' . StoreProducts::$storeNestedSectionId
                    )
                    // ->join(
                    //     Categories::$tableName,
                    //     Categories::$tableName . '.' . Categories::$id,
                    //     '=',
                    //     StoreCategories::$tableName . '.' . StoreCategories::$categoryId
                    // )
                    ->where(StoreProducts::$tableName . '.' . StoreProducts::$id, '=', $insertedId)
                    ->select(
                        StoreProducts::$tableName . '.' . StoreProducts::$id . ' as storeProductId',
                        StoreProducts::$tableName . '.' . StoreProducts::$storeNestedSectionId . ' as storeNestedSectionId',
                        Products::$tableName . '.' . Products::$id . ' as productId',
                        Products::$tableName . '.' . Products::$name . ' as productName',
                        Products::$tableName . '.' . Products::$description . ' as productDescription',
                        StoreProducts::$tableName . '.' . StoreProducts::$price . ' as price',
                            // 
                        Options::$tableName . '.' . Options::$id . ' as optionId',
                        Options::$tableName . '.' . Options::$name . ' as optionName',
                        // 

                        // Categories::$tableName . '.' . Categories::$id . ' as categoryId',
                        // Categories::$tableName . '.' . Categories::$name . ' as categoryName',

                    )
                    ->first();

                $images = DB::table(table: ProductImages::$tableName)->where(ProductImages::$tableName . '.' . ProductImages::$productId, '=', $product->productId)
                    ->get([
                        ProductImages::$tableName . '.' . ProductImages::$id,
                        ProductImages::$tableName . '.' . ProductImages::$image
                    ]);



                $result = [
                    'productId' => $product->productId,
                    'storeNestedSectionId' => $product->storeNestedSectionId,
                    'productName' => $product->productName,
                    'productDescription' => $product->productDescription,
                    'options' => [['optionId' => $product->optionId, 'storeProductId' => $product->storeProductId, 'name' => $product->optionName, 'price' => $product->price, 'currency' => $currency]],
                    'images' => $images
                ];
                return response()->json($result);

            } else {
                $productOption = DB::table(table: StoreProducts::$tableName)->where(StoreProducts::$tableName . '.' . StoreProducts::$id, '=', $insertedId)
                    ->join(
                        Options::$tableName,
                        Options::$tableName . '.' . Options::$id,
                        '=',
                        StoreProducts::$tableName . '.' . StoreProducts::$optionId
                    )
                    ->first(
                        [
                            Options::$tableName . '.' . Options::$id . ' as optionId',
                            Options::$tableName . '.' . Options::$name . ' as optionName',
                            StoreProducts::$tableName . '.' . StoreProducts::$id . ' as storeProductId',
                            StoreProducts::$tableName . '.' . StoreProducts::$price . ' as price',
                        ]
                    );
                return response()->json([
                    'optionId' => $productOption->optionId,
                    'storeProductId' => $productOption->storeProductId,
                    'name' => $productOption->optionName,
                    'price' => $productOption->price,
                    'currency' => $currency,
                    'isCustomPrice' => false
                ]);
            }
        } catch (QueryException $e) {
            return $this->queryEX($e);
        }




    }

    function getInfoLocation()
    {
        // 15.33196939619582, 44.19890626711651
        // Google Maps Reverse Geocoding API URL
        $apiKey = 'your_google_maps_api_key'; // Replace with your API key
        $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=15.33196939619582,44.19890626711651&key=AIzaSyBcKkdvibY3J8wJmIwC3Ws9mA2crZvPC8c";

        // Fetch the data
        $response = file_get_contents($url);

        // Decode the JSON response
        $data = json_decode($response, true);

        // Check if the response is OK
        if ($data['status'] === 'OK') {
            // Extract address components
            $addressComponents = $data['results'][0]['address_components'];

            $countryCode = '';

            // Loop through address components to find the country code
            foreach ($addressComponents as $component) {
                if (in_array('country', $component['types'])) {
                    $countryCode = $component['short_name']; // ISO country code (e.g., "MN")
                    break;
                }
            }

            // Get the calling code using libphonenumber
            $phoneUtil = PhoneNumberUtil::getInstance();
            $callingCode = $phoneUtil->getCountryCodeForRegion($countryCode);

            if ($callingCode) {
                echo "ISO Country Code: $countryCode<br>";
                echo "Country Calling Code: +$callingCode";
            } else {
                echo "Calling code not found for country code: $countryCode";
            }
        } else {
            echo "Unable to fetch location data. Status: " . $data['status'];
        }
    }

    public function addStore(Request $request)
    {
        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: false, storePoints: 2);
        $store = $myData['store'];
        $accessToken = $myData['accessToken'];

        $this->validRequestV1($request, [
            'logo' => 'required|image|max:100',
            'name' => 'required|string|max:100',
            'typeId' => 'required|string|max:1',
            'currencyId' => 'required|string|max:1',
            'cover' => 'required|image|max:100',
            'latitude' => 'required|string|max:100',
            'longitude' => 'required|string|max:100',
        ]);

        // $validator = Validator::make($request->all(), [
        //     // 'accessToken' => 'required|string|max:255',
        //     // 'deviceId' => 'required|string|max:255',

        // ]);

        // // Check if validation fails
        // if ($validator->fails()) {
        //     // Return a JSON response with validation errors
        //     return response()->json([
        //         'message' => 'Validation failed',
        //         'errors' => $validator->errors(),
        //         'code' => 0
        //     ], 422);  // 422 Unprocessable Entity
        // }


        // $loginController = (new LoginController($this->appId));
        // $token = $request->input('accessToken');
        // $deviceId = $request->input('deviceId');

        // // print_r($request->all());
        // $myResult = $loginController->readAccessToken($token, $deviceId);
        // if ($myResult->isSuccess == false) {
        //     return response()->json(['message' => $myResult->message, 'code' => $myResult->code], $myResult->responseCode);
        // }
        // $accessToken = $myResult->message;

        return DB::transaction(function () use ($request, $accessToken) {

            $name = $request->input('name');
            $typeId = $request->input('typeId');
            $currencyId = $request->input('currencyId');

            // print_r($currencyId);
            $logo = $request->file('logo');
            $cover = $request->file('cover');
            $mainCategoryId = $request->input('mainCategoryId');

            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');
            if ($logo->isValid() == false) {
                return response()->json(['error' => 'Invalid Logo file.'], 400);
            }

            if ($cover->isValid() == false) {
                return response()->json(['error' => 'Invalid Cover file.'], 400);
            }


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
                    Countries::$tableName . '.' . Countries::$id . ' as countryId',
                ]);

            $logoName = Str::random(10) . '_' . time() . '.jpg';
            $coverName = Str::random(10) . '_' . time() . '.jpg';

            $insertedId = DB::table(table: Stores::$tableName)
                ->insertGetId([
                    Stores::$id => null,
                    Stores::$name => $name,
                    Stores::$userId => $accessToken->userId,
                    Stores::$typeId => $typeId,
                    Stores::$logo => $logoName,
                    Stores::$cover => $coverName,
                    Stores::$latLong => DB::raw("ST_GeomFromText('POINT($latitude $longitude)', 4326)"),
                    Stores::$countryId => $userInfo->countryId,
                    Stores::$mainCategoryId => $mainCategoryId,
                    Stores::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                    Stores::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
            $insertedIdSubscribe = DB::table(table: StoreSubscriptions::$tableName)
                ->insertGetId([
                    StoreSubscriptions::$id => null,
                    StoreSubscriptions::$isPremium => 0,
                    StoreSubscriptions::$storeId => $insertedId,
                    StoreSubscriptions::$points => 250,
                    StoreSubscriptions::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                    StoreSubscriptions::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                    StoreSubscriptions::$expireAt => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
            $currency = DB::table(table: Currencies::$tableName)
                ->where(Currencies::$tableName . '.' . Currencies::$id, '=', $currencyId)
                ->first([
                    Currencies::$tableName . '.' . Currencies::$id
                ]);


            if ($currency == null) {
                throw new CustomException("عملة غير صحيحة", 0, 403);
            }
            DB::table(table: StoreCurencies::$tableName)
                ->insert([
                    StoreCurencies::$id => null,
                    StoreCurencies::$storeId => $insertedId,
                    StoreCurencies::$currencyId => $currencyId,
                    StoreCurencies::$isSelected => 1,
                    StoreCurencies::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                    StoreCurencies::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                ]);

            $subscribe = DB::table(StoreSubscriptions::$tableName)
                ->where(StoreSubscriptions::$id, '=', $insertedIdSubscribe)
                ->first();

            $storeConfig = null;
            if ($typeId == 1) {
                $storeReference = DB::table(table: Stores::$tableName)
                    ->join(
                        SharedableStores::$tableName,
                        SharedableStores::$tableName . '.' . SharedableStores::$storeId,
                        '=',
                        Stores::$tableName . '.' . Stores::$id
                    )
                    ->where(Stores::$tableName . '.' . Stores::$countryId, '=', $accessToken->countryId)
                    ->where(Stores::$tableName . '.' . Stores::$typeId, '=', 2)
                    ->where(Stores::$tableName . '.' . Stores::$mainCategoryId, '=', $mainCategoryId)
                    ->where(Stores::$tableName . '.' . Stores::$mainCategoryId, '=', $mainCategoryId)
                    ->first([
                        Stores::$tableName . '.' . Stores::$id
                    ]);
                if ($storeReference == null) {
                    throw new CustomException("ليس هناك متجر مشترك في الفئة المختارة", 0, 403);
                }
                // $sharedableStore = DB::table(table: SharedableStores::$tableName)
                //     ->where(SharedableStores::$tableName . '.' . SharedableStores::$storeId, '=', $storeReference->id)
                //     ->first();
                // if ($sharedableStore == null) {
                //     throw new CustomException("المتجر المختار ليس قابل للمشاركة", 0, 403);
                // }

                $insertedIdShared = DB::table(table: SharedStoresConfigs::$tableName)
                    ->insertGetId([
                        SharedStoresConfigs::$id => null,
                        SharedStoresConfigs::$products => json_encode([]),
                        SharedStoresConfigs::$categories => json_encode([]),
                        SharedStoresConfigs::$sections => json_encode([]),
                        SharedStoresConfigs::$nestedSections => json_encode([]),
                        SharedStoresConfigs::$storeId => $insertedId,
                        SharedStoresConfigs::$storeIdReference => 1,
                        SharedStoresConfigs::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                        SharedStoresConfigs::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
                $storeConfig = DB::table(SharedStoresConfigs::$tableName)
                    ->where(SharedStoresConfigs::$id, '=', $insertedIdShared)
                    ->first();
            }

            try {
                // print_r($logo);
                // print_r($cover);

                $pathLogo = Storage::disk('s3')->put('stores/logos/' . $logoName, fopen($logo, 'r+'));

                $logoUrl = Storage::disk('s3')->url($pathLogo);
                print_r("L: " . $logoUrl);
                $pathCover = Storage::disk('s3')->put('stores/covers/' . $coverName, fopen($cover, 'r+'));
                print_r("dsffdftttttt3");
                $logoUrl = Storage::disk('s3')->url($pathCover);

                // print_r("C: ".$logoUrl);


                // Check if the file was uploaded successfully
                if ($pathLogo && $pathCover) {
                    $addedRecord = DB::table(Stores::$tableName)
                        ->where(Stores::$id, '=', $insertedId)
                        ->first(
                            Stores::$tableName . '.' . Stores::$id,

                        );

                    // print_r("frgrg");
                    if ($storeConfig != null) {

                        $categories = json_decode($storeConfig->categories);
                        $sections = json_decode($storeConfig->sections);
                        $nestedSections = json_decode($storeConfig->nestedSections);
                        $products = json_decode($storeConfig->products);
                        // $stores[$index] = (array)$stores[$index];
                        $addedRecord->storeConfig = ['storeIdReference' => $storeConfig->storeIdReference, 'categories' => $categories, 'sections' => $sections, 'nestedSections' => $nestedSections, 'products' => $products];
                    } else {
                        $addedRecord->storeConfig = $storeConfig;
                    }
                    // print_r("34");


                    $addedRecord->app = null;
                    $addedRecord->subscription = $subscribe;

                    // print_r($addedRecord);
                    return response()->json($addedRecord);

                } else {
                    DB::rollBack();
                    // If the image is not valid, return a validation error response
                    return response()->json([
                        'error' => 'No valid image file uploaded.',
                    ], 400);

                }
            } catch (\Exception $e) {
                DB::rollBack();  // Manually trigger a rollback
                return response()->json([
                    'error' => 'An error occurred while uploading the image.',
                    'message' => $e->getMessage(),
                ], 500);
            }
        });
    }
    public function addDeliveryManToStore(Request $request)
    {
        $this->validRequestV1($request, [
            'phone' => 'required|string|max:9',
            'storeId' => 'required|string|max:9'
        ]);

        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: true, storePoints: 2);
        $store = $myData['store'];
        $accessToken = $myData['accessToken'];




        return DB::transaction(function () use ($request, $store, $accessToken) {

            $region = DB::table(table: Users::$tableName)
                ->join(
                    Countries::$tableName,
                    Countries::$tableName . '.' . Countries::$id,
                    '=',
                    Users::$tableName . '.' . Users::$countryId
                )->where(Users::$tableName . '.' . Users::$id, '=', $accessToken->userId)
                ->first(
                    [
                        Countries::$tableName . '.' . Countries::$id,
                    ]
                );
            if ($region == null) {
                throw new CustomException("Unkown region", 0, 403);
            }

            // print_r($region);

            $phone = $request->input('phone');
            $storeId = $request->input('storeId');

            $deliveryMan = DB::table(table: DeliveryMen::$tableName)
                ->join(
                    Users::$tableName,
                    Users::$tableName . '.' . Users::$id,
                    '=',
                    DeliveryMen::$tableName . '.' . DeliveryMen::$userId
                )
                ->where(Users::$tableName . '.' . Users::$phone, '=', $phone)
                ->where(Users::$tableName . '.' . Users::$countryId, '=', $region->id)

                ->first(
                    [
                        DeliveryMen::$tableName . '.' . DeliveryMen::$id,
                            // Users::$tableName . '.' . Users::$id . 'as userId',
                        Users::$tableName . '.' . Users::$firstName,
                        Users::$tableName . '.' . Users::$lastName,
                        Users::$tableName . '.' . Users::$phone,
                    ]
                );

            if ($deliveryMan == null) {
                throw new CustomException("قد يكون المستحدم غير موجود او الموصل غير مسجل في نظام التوصيل", 0, 403);
            }

            $deliveryManInStore = DB::table(table: StoreDeliveryMen::$tableName)
                ->where(StoreDeliveryMen::$tableName . '.' . StoreDeliveryMen::$deliveryManId, '=', $deliveryMan->id)
                ->where(StoreDeliveryMen::$tableName . '.' . StoreDeliveryMen::$storeId, '=', $store->id)
                ->first(
                    [
                        StoreDeliveryMen::$tableName . '.' . StoreDeliveryMen::$id,
                    ]
                );

            if ($deliveryManInStore != null) {
                throw new CustomException("تم اضافته مسبقا", 0, 403);
            }
            try {
                $insertedId = DB::table(table: StoreDeliveryMen::$tableName)
                    ->insertGetId([
                        StoreDeliveryMen::$id => null,
                        StoreDeliveryMen::$storeId => $storeId,
                        StoreDeliveryMen::$deliveryManId => $deliveryMan->id,
                        StoreDeliveryMen::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                        StoreDeliveryMen::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);

                return response()->json($deliveryMan);
            } catch (QueryException $e) {
                print_r($e);
                // Manually trigger a rollback
                return $this->queryEX($e);
            }
        });
    }
    public function addProductImage(Request $request)
    {
        if ($request->hasFile('image')) {
            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:png|max:300', // If you're uploading a file
            ]);

            if ($validator->fails()) {
                return response()->json(['message' => 'خطأ في الصورة', 'errors' => $validator->errors(), 'code' => 0], 400);
            }

            return DB::transaction(function () use ($request) {
                $image = $request->file('image');
                if ($image->isValid() == false) {
                    return response()->json(['error' => 'Invalid image file.'], 400);
                }
                $productId = $request->input('productId');
                $fileName = Str::random(10) . '_' . time() . '.' . $image->getClientOriginalExtension();
                $insertedId = DB::table(ProductImages::$tableName)
                    ->insertGetId([
                        ProductImages::$id => null,
                        ProductImages::$image => $fileName,
                        ProductImages::$productId => $productId,
                        ProductImages::$storeId => 1,
                        ProductImages::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                        ProductImages::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);


                DB::table(ProductImages::$tableName)
                    ->where(ProductImages::$id, '=', $insertedId)
                    ->first();

                try {
                    $path = Storage::disk('s3')->put('products/' . $fileName, fopen($image, 'r+'));

                    // Check if the file was uploaded successfully
                    if ($path) {

                        Storage::disk('s3')->url($fileName);

                        $addedRecord = DB::table(ProductImages::$tableName)
                            ->where(ProductImages::$id, '=', $insertedId)
                            ->first();

                        return response()->json($addedRecord);

                    } else {
                        DB::rollBack();
                        // If the image is not valid, return a validation error response
                        return response()->json([
                            'error' => 'No valid image file uploaded.',
                        ], 400);

                    }
                } catch (\Exception $e) {
                    DB::rollBack();  // Manually trigger a rollback
                    return response()->json([
                        'error' => 'An error occurred while uploading the image.',
                        'message' => $e->getMessage(),
                    ], 500);
                }
            });

        } else {
            return response()->json(['error' => 'Image Not Found'], 400);
        }
    }
    public function addAds(Request $request)
    {
        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: true, storePoints: 2);
        $store = $myData['store'];
        $this->validRequestV1($request, [
            'image' => 'required|image|mimes:jpg|max:300',
            'days' => 'required|string|max:1'

        ]);
        // print_r(Carbon::now()->addDays($days)->endOfDay()->format('Y-m-d H:i:s'));

        // $days = $request->input('days');

        // print_r($days);
        // return;

        return DB::transaction(function () use ($request, $store) {


            $image = $request->file('image');
            $days = $request->input('days');

            $productId = $request->input('productId');
            // $storeId = $request->input('storeId');

            if ($image->isValid() == false) {
                return response()->json(['error' => 'Invalid image file.'], 400);
            }


            $expireAt = Carbon::now();

            if ($days > 1) {
                $expireAt = $expireAt->addDays($days - 1)->endOfDay();
            }


            $fileName = Str::random(10) . '_' . time() . '.' . $image->getClientOriginalExtension();
            $insertedId = DB::table(StoreAds::$tableName)
                ->insertGetId([
                    StoreAds::$id => null,
                    StoreAds::$image => $fileName,
                    StoreAds::$productId => $productId,
                    StoreAds::$storeId => $store->id,
                    StoreAds::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                    StoreAds::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                    StoreAds::$expireAt => $expireAt->format('Y-m-d H:i:s'),
                ]);


            $addedRecord = DB::table(StoreAds::$tableName)
                ->where(StoreAds::$id, '=', $insertedId)
                ->first();

            try {
                $path = Storage::disk('s3')->put('stores/ads/' . $fileName, fopen($image, 'r+'));
                if ($path) {
                    return response()->json($addedRecord);
                } else {
                    DB::rollBack();
                    throw new CustomException("No valid image file uploaded.", 0, 400);
                }
            } catch (\Exception $e) {
                DB::rollBack();  // Manually trigger a rollback
                throw new CustomException('An error occurred while uploading the image.' . $e->getMessage(), 0, 500);
            }
        });
    }


    // protected 

    // public function __construct( $firebaseService)
    // {
    //     $this->firebaseService = $firebaseService;
    // }


    public function addNotification(Request $request)
    {
        $appId = $request->input('appId');
        $title = $request->input('title');
        $description = $request->input('description');
        $passwordService = $request->input('passwordService');


        $app = DB::table(Apps::$tableName)
            ->where(Apps::$id, '=', $appId)
            ->first();

        $serviceAccount = $app->serviceAccount;
        if ($serviceAccount == null) {
            return $this->responseError2("لايوجد اعدادات الخدمة", [], 0, 405);
        }


        if ($this->isValidJson($serviceAccount) == false) {
            return $this->responseError2("تم تخزين الملف بشكل خاطئ", [], 0, 405);
        }
        $json = $this->decryptData(json_decode($serviceAccount), $passwordService);
        if ($json != true) {
            return $this->responseError2("رمز غير صحيح", [], 0, 405);
        }

        try {
            $firebaseService = new FirebaseService($json);

            $response = $firebaseService->sendNotificationToTopic($app->id, $title, $description);
            // $response = $this->firebaseService->sendNotification("d37lmIWyReq0Gno0g6iPb7:APA91bFCb8RDk3niIpLpxjw2sF0Zh9zZni3jbdBBaSCuwFNx9YQTsBrCjigisCkpktKk7K_AatCqbOmuWC1LKjWqhHj844BUu0YU0MiWNmwnhM_jjOPLvnU", $title, $description);
            return response()->json($response);
        } catch (\Throwable $th) {
            return $this->responseError2($th->getMessage(), [], 0, 405);
        }

    }
    public function addCustomPrice(Request $request)
    {
        $storeId = $request->input('storeId');
        $storeProductId = $request->input('storeProductId');
        $price = $request->input('price');

        $insertedId = DB::table(table: CustomPrices::$tableName)
            ->insertGetId([
                CustomPrices::$id => null,
                CustomPrices::$storeId => $storeId,
                CustomPrices::$price => $price,
                CustomPrices::$storeProductId => $storeProductId,
                CustomPrices::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                CustomPrices::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

        return response()->json([]);
    }

}
