<?php
namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Controller;
use App\Models\Locations;
use App\Models\SharedStoresConfigs;
use App\Models\StoreInfo;
use App\Models\StoreNestedSections;
use App\Models\Options;
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
        $storeNestedSectionId = $request->input('storeNestedSectionId');
        $storeId = 1;

        // 
        $storeProducts = DB::table(StoreProducts::$tableName)
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
            // ->join(
            //     StoreCategories::$tableName,
            //     StoreCategories::$tableName . '.' . StoreCategories::$id,
            //     '=',
            //     StoreProducts::$tableName . '.' . StoreProducts::$StoreNestedSectionsId
            // )
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
            ->where(StoreProducts::$tableName . '.' . StoreProducts::$storeId, '=', $storeId)
            ->where(StoreProducts::$tableName . '.' . StoreProducts::$storeNestedSectionId, '=', $storeNestedSectionId)
            ->select(
                StoreProducts::$tableName . '.' . StoreProducts::$id . ' as storeProductId',
                Products::$tableName . '.' . Products::$id . ' as productId',
                Products::$tableName . '.' . Products::$name . ' as productName',
                Products::$tableName . '.' . Products::$description . ' as productDescription',
                StoreProducts::$tableName . '.' . StoreProducts::$price . ' as price',
                    // 
                Options::$tableName . '.' . Options::$id . ' as optionId',
                Options::$tableName . '.' . Options::$name . ' as optionName',
                    //
                StoreNestedSections::$tableName . '.' . StoreNestedSections::$id . ' as storeNestedSectionId',



            )
            ->get();
        $productIds = [];
        foreach ($storeProducts as $product) {
            $productIds[] = $product->productId;
        }
        $productImages = DB::table(ProductImages::$tableName)
            ->whereIn(ProductImages::$productId, $productIds)
            ->select(
                ProductImages::$tableName . '.' . ProductImages::$productId,
                ProductImages::$tableName . '.' . ProductImages::$image,
            )
            ->get();



        $result = [];
        foreach ($storeProducts as $product) {
            if (!isset($result[$product->productId])) {

                $images = [];
                foreach ($productImages as $index => $image) {
                    if ($image->productId == $product->productId) {
                        $images[] = ['image' => $image->image];
                        unset($productImages[$index]);
                    }
                }
                $result[$product->productId] = [
                    'product' => ['productId' => $product->productId, 'productName' => $product->productName, 'productDescription' => $product->productDescription, 'images' => $images],
                    'storeNestedSectionId' => $product->storeNestedSectionId,
                    'options' => []
                ];

                // $result[$product->productId]['images'] = $images;
            }


            // Add the option to the options array
            $result[$product->productId]['options'][] = ['storeProductId' => $product->storeProductId, 'name' => $product->optionName, 'price' => $product->price];
        }


        return response()->json(array_values($result));
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

        $validator = Validator::make($request->all(), [
            'accessToken' => 'required|string|max:255',
            'deviceId' => 'required|string|max:255'
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            // Return a JSON response with validation errors
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'code' => 0
            ], 422);  // 422 Unprocessable Entity
        }


        $loginController = (new LoginController($this->appId));
        $token = $request->input('accessToken');
        $deviceId = $request->input('deviceId');

        // print_r($request->all());
        $myResult = $loginController->readAccessToken($token, $deviceId);
        if ($myResult->isSuccess == false) {
            return response()->json(['message' => $myResult->message, 'code' => $myResult->code], $myResult->responseCode);
        }

        $accessToken = $myResult->message;

        $data = DB::table(table: Locations::$tableName)
            ->where(Locations::$tableName . '.' . Locations::$userId, '=', $accessToken->userId)
            ->get(
                [
                    Locations::$tableName . '.' . Locations::$id,
                    Locations::$tableName . '.' . Locations::$street,
                ]
            );
        return response()->json($data);
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
        $token = $request->input('accessToken');
        $deviceId = $request->input('deviceId');

        $loginController = (new LoginController($this->appId));
        return $loginController->readAndRefreshAccessToken($token, $deviceId);
    }

}
