<?php
namespace App\Http\Controllers\Api\StoreManager;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Controller;
use App\Models\Apps;
use App\Models\AppStores;
use App\Models\Categories;
use App\Models\Countries;
use App\Models\Currencies;
use App\Models\DeliveryMen;
use App\Models\GooglePurchases;
use App\Models\InAppProducts;
use App\Models\Languages;
use App\Models\MainCategories;
use App\Models\NestedSections;
use App\Models\Orders;
use App\Models\OrdersAmounts;
use App\Models\OrderSituations;
use App\Models\OrdersProducts;
use App\Models\OrderStatus;
use App\Models\ProductViews;
use App\Models\Sections;
use App\Models\Situations;
use App\Models\StoreDeliveryMen;
use App\Models\StoreInfo;
use App\Models\StoreNestedSections;
use App\Models\Options;
use App\Models\ProductImages;
use App\Models\Products;
use App\Models\SharedStoresConfigs;
use App\Models\StoreCategories;
use App\Models\StoreProducts;
use App\Models\Stores;
use App\Models\StoreSections;
use App\Models\StoresTime;
use App\Models\StoreSubscriptions;
use App\Models\Users;
use App\Traits\AllShared;
use App\Traits\StoreManagerControllerShared;
use Carbon\Carbon;
use Illuminate\Database\CustomException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StoreManagerControllerGet extends Controller
{
    use StoreManagerControllerShared;
    use AllShared;
    public function getMain(Request $request)
    {
        return $this->getOurProducts2($request);

        // Define validation rules
        $validator = Validator::make($request->all(), [
            'accessToken' => 'required|string|max:255',
            'deviceId' => 'required|string|max:255',
            'storeId' => 'required|integer|max:2147483647',
            'storeNestedSectionId' => 'required|integer|max:2147483647',

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
        $storeId = $request->input('storeId');
        $storeNestedSectionId = $request->input('storeNestedSectionId');

        // print_r($request->all());
        $myResult = $loginController->readAccessToken($token, $deviceId);
        if ($myResult->isSuccess == false) {
            return response()->json(['message' => $myResult->message, 'code' => $myResult->code], $myResult->responseCode);
        }
        $accessToken = $myResult->message;

        $store = DB::table(Stores::$tableName)
            ->where(Stores::$tableName . '.' . Stores::$userId, '=', $accessToken->userId)
            ->where(Stores::$tableName . '.' . Stores::$id, '=', $storeId)
            ->first([
                Stores::$tableName . '.' . Stores::$id,
                Stores::$tableName . '.' . Stores::$typeId

            ]);
        if ($store == null) {
            return response()->json(['message' => "متجر غير مخول", 'code' => 0], 403);
        }
        $storeProductsIds = [];
        if ($store->typeId == 1) {
            $storeConfig = DB::table(table: SharedStoresConfigs::$tableName)
                ->where(SharedStoresConfigs::$tableName . '.' . SharedStoresConfigs::$storeId, '=', $storeId)
                ->first();
            $storeProductsIds = json_decode($storeConfig->products);

            $storeId = $storeConfig->storeIdReference;
        }




        // $categories = DB::table(StoreCategories::$tableName)
        //     ->join(
        //         Categories::$tableName,
        //         Categories::$tableName . '.' . Categories::$id,
        //         '=',
        //         StoreCategories::$tableName . '.' . StoreCategories::$categoryId
        //     )
        //     ->where(
        //         StoreCategories::$tableName . '.' . StoreCategories::$storeId,
        //         '=',
        //         $storeId
        //     )
        //     ->select(
        //         StoreNestedSections::$tableName . '.' . StoreNestedSections::$id . ' as StoreNestedSectionsId',
        //         Categories::$tableName . '.' . Categories::$id . ' as categoryId',
        //         Categories::$tableName . '.' . Categories::$name . ' as categoryName'
        //     )
        //     ->get()->toArray();
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
                ProductViews::$tableName,
                ProductViews::$tableName . '.' . ProductViews::$id,
                '=',
                StoreProducts::$tableName . '.' . StoreProducts::$productViewId
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
            ->where(StoreProducts::$tableName . '.' . StoreProducts::$storeId, '=', $storeId)
            ->where(StoreProducts::$tableName . '.' . StoreProducts::$storeNestedSectionId, '=', $storeNestedSectionId)
            ->whereNotIn(StoreProducts::$tableName . '.' . StoreProducts::$id, $storeProductsIds)
            ->select(
                StoreProducts::$tableName . '.' . StoreProducts::$id . ' as storeProductId',
                StoreProducts::$tableName . '.' . StoreProducts::$storeNestedSectionId . ' as storeNestedSectionId',
                Products::$tableName . '.' . Products::$id . ' as productId',
                Products::$tableName . '.' . Products::$name . ' as productName',
                Products::$tableName . '.' . Products::$description . ' as productDescription',
                StoreProducts::$tableName . '.' . StoreProducts::$price . ' as price',
                    // 
                ProductViews::$tableName . '.' . ProductViews::$id . ' as productViewId',
                ProductViews::$tableName . '.' . ProductViews::$name . ' as productViewName',


                    //
                Options::$tableName . '.' . Options::$id . ' as optionId',
                Options::$tableName . '.' . Options::$name . ' as optionName',
                // 

                // Categories::$tableName . '.' . Categories::$id . ' as categoryId',
                // Categories::$tableName . '.' . Categories::$name . ' as categoryName',

            )
            ->get();
        $productIds = [];
        foreach ($storeProducts as $product) {
            $productIds[] = $product->productId;
        }
        $productImages = DB::table(ProductImages::$tableName)
            ->whereIn(ProductImages::$productId, $productIds)
            ->select(
                ProductImages::$tableName . '.' . ProductImages::$id,

                ProductImages::$tableName . '.' . ProductImages::$productId,
                ProductImages::$tableName . '.' . ProductImages::$image,
            )
            ->get();
        // 
        // print_r($categories);
        $final = [];
        // for ($i = 0; $i < count($categories); $i++) {
        //     print_r($categories[$i]->id);
        //     print_r($categories[$i]->name);
        // }

        // foreach ($categories as $category) {

        $result = [];

        foreach ($storeProducts as $product) {

            if (!isset($result[$product->productId])) {
                $result[$product->productId] = [
                    'productId' => $product->productId,
                    'productViewName' => $product->productViewName,
                    'productViewId' => $product->productViewId,
                    'storeNestedSectionId' => $product->storeNestedSectionId,
                    'productName' => $product->productName,
                    'productDescription' => $product->productDescription,
                    'options' => [],
                    'images' => []
                ];
                $images = [];
                foreach ($productImages as $index => $image) {
                    if ($image->productId == $product->productId) {
                        $images[] = ['image' => $image->image, 'id' => $image->id];
                        // unset($productImages[$index]);
                    }
                }
                $result[$product->productId]['images'] = $images;
            }



            // if ($product->categoryId == $category->categoryId)
            // Add the option to the options array
            $result[$product->productId]['options'][] = ['optionId' => $product->optionId, 'storeProductId' => $product->storeProductId, 'name' => $product->optionName, 'price' => $product->price];



        }
        // $value =  array_values($result);
        // array_push($final, $value);
        // }

        // $result = [];

        // foreach ($storeProducts as $product) {

        //     $options = [];
        //     if (!isset($result[$product->productId])) {
        //         $result[$product->productId] = [
        //             'productId' => $product->productId,
        //             'productName' => $product->productName,
        //             'options' => []
        //         ];
        //     }

        //     // Add the option to the options array
        //     $result[$product->productId]['options'][] = ['price' => $product->price, 'name' => $product->optionName];
        // }
        return response()->json(array_values($result));
        // return new JsonResponse([
        //     'data' => 88888
        // ]);

        // return Post::all();
    }
    public function getProducts(Request $request)
    {
        $storeId = $request->input('storeId');



        $nestedSectionId = $request->input('nestedSectionId');
        $products = DB::table(Products::$tableName)
            ->whereIn(Products::$tableName . '.' . Products::$storeId, [$storeId, 1])
            ->where(Products::$tableName . '.' . Products::$nestedSectionId, $nestedSectionId)
            ->get(
                [
                    Products::$tableName . '.' . Products::$id,
                    Products::$tableName . '.' . Products::$name,
                    Products::$tableName . '.' . Products::$acceptedStatus,

                ]
            );


        return response()->json(array_values($products));
    }

    public function getOptions()
    {
        $options = DB::table(table: Options::$tableName)->get();
        return response()->json($options);
    }
    public function getProductViews()
    {
        $options = DB::table(table: ProductViews::$tableName)->get();
        return response()->json($options);
    }
    public function getStores(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'accessToken' => 'required|string|max:255',
        //     'deviceId' => 'required|string|max:255',
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


        // // print_r($request->all());
        // $accessToken = (new LoginController($this->appId))->readAccessToken($request);

        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: false);
        $accessToken = $myData['accessToken'];
        // print_r($accessToken);

        $data = DB::table(Stores::$tableName)
            ->where(Stores::$tableName . '.' . Stores::$userId, '=', $accessToken->userId)
            ->join(
                Currencies::$tableName,
                Currencies::$tableName . '.' . Currencies::$id,
                '=',
                Stores::$tableName . '.' . Stores::$deliveryPriceCurrency
            )

            ->get([
                Stores::$tableName . '.' . Stores::$id,
                Stores::$tableName . '.' . Stores::$name,
                Stores::$tableName . '.' . Stores::$typeId,
                Stores::$tableName . '.' . Stores::$logo,
                Stores::$tableName . '.' . Stores::$cover,
                DB::raw("CONCAT(ROUND(ST_Y(" . Stores::$tableName . "." . Stores::$latLong . "), 4), ',', ROUND(ST_X(" . Stores::$tableName . "." . Stores::$latLong . "), 4)) AS formatted_point"),
                // Stores::$tableName . '.' . Stores::$latLong,
                Stores::$tableName . '.' . Stores::$deliveryPrice,
                Currencies::$tableName . '.' . Currencies::$id . ' as currencyId',
                Currencies::$tableName . '.' . Currencies::$name . ' as currencyName',
                // SharedStoresConfigs::$tableName . '.' . SharedStoresConfigs::$categories,
                // SharedStoresConfigs::$tableName . '.' . SharedStoresConfigs::$sections,
                // SharedStoresConfigs::$tableName . '.' . SharedStoresConfigs::$nestedSections,
                // SharedStoresConfigs::$tableName . '.' . SharedStoresConfigs::$products,
            ])->toArray();

        // print_r($data);
        $storeIds = [];
        foreach ($data as $store) {
            // if ($store->typeId == 1) {
            $storeIds[] = $store->id;
            // }
        }

        $storeConfigs = DB::table(table: SharedStoresConfigs::$tableName)
            ->whereIn(SharedStoresConfigs::$tableName . '.' . SharedStoresConfigs::$storeId, $storeIds)
            ->get();

        // print_r($storeConfigs);





        foreach ($data as $index => $store) {
            if ($store->typeId == 1) {
                foreach ($storeConfigs as $storeConfig) {
                    if ($storeConfig->storeId == $store->id) {
                        $categories = json_decode($storeConfig->categories);
                        $sections = json_decode($storeConfig->sections);
                        $nestedSections = json_decode($storeConfig->nestedSections);
                        $products = json_decode($storeConfig->products);
                        // $stores[$index] = (array)$stores[$index];
                        $data[$index]->storeConfig = ['storeIdReference' => $storeConfig->storeIdReference, 'categories' => $categories, 'sections' => $sections, 'nestedSections' => $nestedSections, 'products' => $products];
                    }
                }
            } else {
                $data[$index]->storeConfig = null;
            }
        }

        $storeIds = [];
        foreach ($data as $key => $store) {
            $storeIds[] = $store->id;
        }
        $apps = DB::table(table: AppStores::$tableName)
            ->join(
                Apps::$tableName,
                Apps::$tableName . '.' . Apps::$id,
                '=',
                AppStores::$tableName . '.' . AppStores::$appId
            )
            ->whereIn(AppStores::$tableName . '.' . AppStores::$storeId, $storeIds)
            ->get([
                Apps::$tableName . '.' . Apps::$serviceAccount,
                AppStores::$tableName . '.' . AppStores::$appId,
                AppStores::$tableName . '.' . AppStores::$storeId

            ]);

        // print_r($apps);
        foreach ($data as $index => $store) {
            $myapp = null;
            foreach ($apps as $key => $app) {
                // print_r($app);
                if ($store->id == $app->storeId) {

                    $hasServiceAcount = false;
                    if ($app->serviceAccount != null) {
                        $hasServiceAcount = true;
                    }

                    $myapp = ['id' => $app->appId, 'hasServiceAccount' => $hasServiceAcount];
                    break;
                }
            }
            $data[$index]->app = $myapp;
        }

        $subscriptions = DB::table(table: StoreSubscriptions::$tableName)
            ->whereIn(StoreSubscriptions::$tableName . '.' . StoreSubscriptions::$storeId, $storeIds)
            ->get();

        foreach ($data as $index => $store) {
            $myapp = null;
            foreach ($subscriptions as $key => $app) {
                // print_r($app);
                if ($store->id == $app->storeId) {
                    $myapp = $app;
                    break;
                }
            }
            $data[$index]->subscription = $myapp;
        }

        return response()->json($data);
    }
    public function getCategories(Request $request)
    {
        $storeId = $request->input('storeId');
        $categories = DB::table(Categories::$tableName)
            ->where(Categories::$tableName . '.' . Categories::$storeId, '=', $storeId)
            ->get([
                Categories::$tableName . '.' . Categories::$id,
                Categories::$tableName . '.' . Categories::$name,
                Categories::$tableName . '.' . Categories::$acceptedStatus,
            ])->toArray();
        return response()->json($categories);
    }
    public function getNestedSections(Request $request)
    {
        $sectionId = $request->input('sectionId');
        $storeId = $request->input('storeId');


        $categories = DB::table(NestedSections::$tableName)
            ->where(NestedSections::$tableName . '.' . NestedSections::$sectionId, '=', $sectionId)
            ->where(NestedSections::$tableName . '.' . NestedSections::$storeId, '=', $storeId)

            ->join(
                Sections::$tableName,
                Sections::$tableName . '.' . Sections::$id,
                '=',
                NestedSections::$tableName . '.' . NestedSections::$sectionId
            )
            ->get([
                NestedSections::$tableName . '.' . NestedSections::$id,
                NestedSections::$tableName . '.' . NestedSections::$name,
                NestedSections::$tableName . '.' . NestedSections::$acceptedStatus,
            ])->toArray();
        return response()->json($categories);
    }

    public function getStoreCategories(Request $request)
    {

        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: true, storePoints: 2);
        $store = $myData['store'];
        return $this->getOurHome($store);

        // $storeId = $store->id;
        // if ($store->typeId == 1) {
        //     if ($store->storeConfig == null) {
        //         throw new CustomException("This Store Confilct", 0, 443);
        //     }
        //     $storeId = $store->storeConfig->storeIdReference;

        // } else {
        //     $storeId = $store->id;
        // }



        // $storeId = $request->input('storeId');
        $store = DB::table(Stores::$tableName)
            ->where(Stores::$tableName . '.' . Stores::$id, '=', $storeId)
            ->sole([
                Stores::$tableName . '.' . Stores::$id,
                Stores::$tableName . '.' . Stores::$typeId,
            ]);

        $typeId = $store->typeId;

        if ($typeId == 1) {
            $storeConfig = DB::table(table: SharedStoresConfigs::$tableName)
                ->where(SharedStoresConfigs::$tableName . '.' . SharedStoresConfigs::$storeId, '=', $storeId)
                ->first();
            $categories = json_decode($storeConfig->categories);
            $sections = json_decode($storeConfig->sections);
            $nestedSections = json_decode($storeConfig->nestedSections);
            $products = json_decode($storeConfig->products);
            // print_r($categories);
            // print_r($sections);
            // print_r($nestedSections);
            // print_r($products);

            $storeCategories = DB::table(table: StoreCategories::$tableName)
                ->whereNotIn(StoreCategories::$tableName . '.' . StoreCategories::$id, $categories)
                ->where(StoreCategories::$tableName . '.' . StoreCategories::$storeId, 1)
                ->join(
                    Categories::$tableName,
                    Categories::$tableName . '.' . Categories::$id,
                    '=',
                    StoreCategories::$tableName . '.' . StoreCategories::$categoryId
                )
                ->get(
                    [
                        StoreCategories::$tableName . '.' . StoreCategories::$id . ' as id',
                        Categories::$tableName . '.' . Categories::$id . ' as categoryId',
                        Categories::$tableName . '.' . Categories::$name . ' as categoryName'
                    ]
                );

            $storeCategoriesIds = [];
            foreach ($storeCategories as $storeCategory) {
                $storeCategoriesIds[] = $storeCategory->id;
            }
            $storeCategoriesSections = DB::table(StoreSections::$tableName)
                ->whereIn(
                    StoreSections::$tableName . '.' . StoreSections::$storeCategoryId,
                    $storeCategoriesIds

                )
                ->whereNotIn(StoreSections::$tableName . '.' . StoreSections::$id, $sections)
                ->join(
                    Sections::$tableName,
                    Sections::$tableName . '.' . Sections::$id,
                    '=',
                    StoreSections::$tableName . '.' . StoreSections::$sectionId
                )
                ->select(
                    StoreSections::$tableName . '.' . StoreSections::$id . ' as id',
                    StoreSections::$tableName . '.' . StoreSections::$storeCategoryId . ' as storeCategoryId',
                    Sections::$tableName . '.' . Sections::$name . ' as sectionName',
                    Sections::$tableName . '.' . Sections::$id . ' as sectionId',
                )
                ->get()->toArray();

            $storeCategoriesSectionsIds = [];
            foreach ($storeCategoriesSections as $storeCategorySection) {
                $storeCategoriesSectionsIds[] = $storeCategorySection->id;
            }

            $csps = DB::table(StoreNestedSections::$tableName)
                ->join(
                    NestedSections::$tableName,
                    NestedSections::$tableName . '.' . NestedSections::$id,
                    '=',
                    StoreNestedSections::$tableName . '.' . StoreNestedSections::$nestedSectionId
                )

                ->whereIn(StoreNestedSections::$tableName . '.' . StoreNestedSections::$storeSectionId, $storeCategoriesSectionsIds)
                ->whereNotIn(StoreNestedSections::$tableName . '.' . StoreNestedSections::$id, $nestedSections)
                ->select(
                    StoreNestedSections::$tableName . '.' . StoreNestedSections::$id . ' as id',
                    StoreNestedSections::$tableName . '.' . StoreNestedSections::$storeSectionId . ' as storeSectionId',
                    StoreNestedSections::$tableName . '.' . StoreNestedSections::$nestedSectionId . ' as nestedSectionId',
                    NestedSections::$tableName . '.' . NestedSections::$name . ' as name',
                )
                ->get();

            return response()->json(['storeCategories' => $storeCategories, 'storeCategoriesSections' => $storeCategoriesSections, 'csps' => $csps]);

            // return response()->json($storeCategories);
        }
        if ($typeId == 2) {
            $storeCategories = DB::table(table: StoreCategories::$tableName)
                ->where(StoreCategories::$tableName . '.' . StoreCategories::$storeId, $storeId)
                ->join(
                    Categories::$tableName,
                    Categories::$tableName . '.' . Categories::$id,
                    '=',
                    StoreCategories::$tableName . '.' . StoreCategories::$categoryId
                )
                ->get(
                    [
                        StoreCategories::$tableName . '.' . StoreCategories::$id . ' as id',
                        Categories::$tableName . '.' . Categories::$id . ' as categoryId',
                        Categories::$tableName . '.' . Categories::$name . ' as categoryName'
                    ]
                );

            $storeCategoriesIds = [];
            foreach ($storeCategories as $storeCategory) {
                $storeCategoriesIds[] = $storeCategory->id;
            }
            $storeCategoriesSections = DB::table(StoreSections::$tableName)
                ->whereIn(
                    StoreSections::$tableName . '.' . StoreSections::$storeCategoryId,
                    $storeCategoriesIds

                )
                ->join(
                    Sections::$tableName,
                    Sections::$tableName . '.' . Sections::$id,
                    '=',
                    StoreSections::$tableName . '.' . StoreSections::$sectionId
                )
                ->select(
                    StoreSections::$tableName . '.' . StoreSections::$id . ' as id',
                    StoreSections::$tableName . '.' . StoreSections::$storeCategoryId . ' as storeCategoryId',
                    Sections::$tableName . '.' . Sections::$name . ' as sectionName',
                    Sections::$tableName . '.' . Sections::$id . ' as sectionId',
                )
                ->get()->toArray();

            $storeCategoriesSectionsIds = [];
            foreach ($storeCategoriesSections as $storeCategorySection) {
                $storeCategoriesSectionsIds[] = $storeCategorySection->id;
            }

            $csps = DB::table(StoreNestedSections::$tableName)
                ->join(
                    NestedSections::$tableName,
                    NestedSections::$tableName . '.' . NestedSections::$id,
                    '=',
                    StoreNestedSections::$tableName . '.' . StoreNestedSections::$nestedSectionId
                )

                ->whereIn(StoreNestedSections::$tableName . '.' . StoreNestedSections::$storeSectionId, $storeCategoriesSectionsIds)
                ->select(
                    StoreNestedSections::$tableName . '.' . StoreNestedSections::$id . ' as id',
                    StoreNestedSections::$tableName . '.' . StoreNestedSections::$storeSectionId . ' as storeSectionId',
                    StoreNestedSections::$tableName . '.' . StoreNestedSections::$nestedSectionId . ' as nestedSectionId',
                    NestedSections::$tableName . '.' . NestedSections::$name . ' as nestedSectionName',
                )
                ->get();

            return response()->json(['storeCategories' => $storeCategories, 'storeSections' => $storeCategoriesSections, 'storeNestedSections' => $csps]);
        } else {
            return response()->json(['message' => 'Undefiend Store type', 'code' => 0], 400);
        }

    }
    public function getSections(Request $request)
    {
        $categoryId = $request->input('categoryId');
        $storeId = $request->input('storeId');
        $categories = DB::table(Sections::$tableName)
            ->where(Sections::$tableName . '.' . Sections::$categoryId, '=', $categoryId)
            ->where(Sections::$tableName . '.' . Sections::$storeId, '=', $storeId)
            ->get([
                Sections::$tableName . '.' . Sections::$id,
                Sections::$tableName . '.' . Sections::$acceptedStatus,
                Sections::$tableName . '.' . Sections::$name
            ])->toArray();
        return response()->json($categories);
    }
    public function getSecionsStoreCategories(Request $request)
    {
        // $storeId = 1;
        $storeCategory1Id = $request->input('storeCategory1Id');
        $storeCategories = DB::table(table: StoreSections::$tableName)
            ->where(StoreSections::$tableName . '.' . StoreSections::$storeCategoryId, '=', $storeCategory1Id)
            ->join(
                Sections::$tableName,
                Sections::$tableName . '.' . Sections::$id,
                '=',
                StoreSections::$tableName . '.' . StoreSections::$sectionId
            )
            ->get(
                [
                    StoreSections::$tableName . '.' . StoreSections::$id . ' as id',
                    Sections::$tableName . '.' . Sections::$id . ' as sectionId',
                    Sections::$tableName . '.' . Sections::$name . ' as sectionName'
                ]
            );

        return response()->json($storeCategories);
    }
    public function getStoreNestedSections(Request $request)
    {
        // $storeId = 1;
        $storeSectionId = $request->input('storeSectionId');
        $storeCategories = DB::table(table: StoreNestedSections::$tableName)
            ->where(StoreNestedSections::$tableName . '.' . StoreNestedSections::$storeSectionId, '=', $storeSectionId)
            ->join(
                NestedSections::$tableName,
                NestedSections::$tableName . '.' . NestedSections::$id,
                '=',
                StoreNestedSections::$tableName . '.' . StoreNestedSections::$nestedSectionId
            )
            ->get(
                [
                    StoreNestedSections::$tableName . '.' . StoreNestedSections::$id . ' as id',
                    StoreNestedSections::$tableName . '.' . StoreNestedSections::$storeSectionId . ' as storeSectionId',
                    NestedSections::$tableName . '.' . NestedSections::$id . ' as nestedSectionId',
                    NestedSections::$tableName . '.' . NestedSections::$name . ' as nestedSectionName',
                ]
            );

        return response()->json($storeCategories);
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
    public function getOrders(Request $request)
    {
        return $this->getMyOrders($request);

        // return $this->getOurOrders($request);
    }
    public function getOrderProducts(Request $request)
    {
        return DB::transaction(function () use ($request) {

            $orderDelivery = $this->getOurOrderDelivery($request);
            $orderProducts = $this->getOurOrderProducts($request);
            $orderPayment = $this->getOurOrderPayment($request);
            $orderDetail = $this->getOurOrderDetail($request);

            $orderId = $request->input('orderId');

            $order = DB::table(Orders::$tableName)
                ->where(Orders::$tableName . '.' . Orders::$id, '=', $orderId)
                ->where(Orders::$tableName . '.' . Orders::$situationId, '=', Situations::$NEW)
                ->first();

            if ($order != null) {
                DB::table(table: Orders::$tableName)
                    ->where(Orders::$id, '=', $order->id)
                    ->update(
                        [
                            Orders::$situationId => Situations::$VIEWD,
                            Orders::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                        ]
                    );
                $insertedId = DB::table(OrderStatus::$tableName)
                    ->insert([
                        OrderStatus::$id => null,
                        OrderStatus::$orderId => $order->id,
                        OrderStatus::$situationId => Situations::$VIEWD,
                        OrderStatus::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
            }
            return response()->json(['orderDelivery' => $orderDelivery, 'orderProducts' => $orderProducts, 'orderPayment' => $orderPayment, 'orderDetail' => $orderDetail]);

        });


    }
    public function getDeliveryMen(Request $request)
    {

        $storeId = $request->input('storeId');
        $deliveryManId = $request->input('deliveryManId');

        $data = DB::table(table: StoreDeliveryMen::$tableName)
            ->join(
                DeliveryMen::$tableName,
                DeliveryMen::$tableName . '.' . DeliveryMen::$id,
                '=',
                StoreDeliveryMen::$tableName . '.' . StoreDeliveryMen::$deliveryManId
            )
            ->join(
                Users::$tableName,
                Users::$tableName . '.' . Users::$id,
                '=',
                DeliveryMen::$tableName . '.' . DeliveryMen::$userId
            )
            ->where(StoreDeliveryMen::$tableName . '.' . StoreDeliveryMen::$storeId, '=', $storeId)
            ->when($deliveryManId != null, function ($query) use ($deliveryManId) {
                return $query->where(StoreDeliveryMen::$tableName . '.' . StoreDeliveryMen::$deliveryManId, '<>', $deliveryManId);
            })
            ->get(
                [
                    DeliveryMen::$tableName . '.' . DeliveryMen::$id,
                    Users::$tableName . '.' . Users::$firstName,
                    Users::$tableName . '.' . Users::$lastName,
                    Users::$tableName . '.' . Users::$phone,
                ]
            );

        return response()->json($data);
    }

    public function login(Request $request)
    {
        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: false, withUser: false, myProcessName: 'login');
        $app = $myData['app'];
        return (new LoginController($app->id))->login($request);
    }
    public function refreshToken(Request $request)
    {
        return $this->refreshOurToken($request, $this->appId);
    }
    public function getUserProfile(Request $request)
    {
        return $this->getOurUserProfile($request, $this->appId);
    }
    public function getCurrencies(Request $request)
    {
        // $nestedSectionId = $request->input('nestedSectionId');
        $data = DB::table(Currencies::$tableName)
            ->get();

        return response()->json($data);
    }

    public function getOrderSituations(Request $request)
    {
        return $this->getMyOrders($request, true);
    }

    public function getMyOrders(Request $request, $withSituations = false)
    {


        $this->validRequestV1($request, [
            'from' => 'required|string|max:5',
            'fromDate' => 'required|date_format:Y-d-m',
            'toDate' => 'required|date_format:Y-d-m',
        ]);

        $from = $request->input('from');
        $fromDate = $request->input('fromDate');
        $toDate = Carbon::parse($request->input('toDate'))->endOfDay();
        //
        // $fromDate = Carbon::parse($fromDate)->startOfDay(); // Parse fromDate to Carbon instance
        // $toDate = Carbon::parse($toDate)->endOfDay(); // Set toDate to end of the day (23:59:59)

        // print_r($fromDate->dat);
        // print_r($toDate);


        // $to = $request->input('to');


        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: true, storePoints: 2);
        $store = $myData['store'];
        // $situation = null;

        // if ($withSituations === true) {
        $situation = DB::table(OrderSituations::$tableName)
            ->get();
        // }


        $orders = DB::table(Orders::$tableName)
            ->where(Orders::$tableName . '.' . Orders::$storeId, '=', $store->id)
            ->when($withSituations === true, function ($query) use ($situation) {
                return $query->where(Orders::$tableName . '.' . Orders::$situationId, '=', $situation[0]->id);
            })
            ->when($withSituations === false, function ($query) use ($request, $situation) {

                $this->validRequestV1($request, [
                    'situationId' => 'required|string|max:100',
                ]);
                $situationId = $request->input('situationId');
                $situationIds = json_decode($situationId);
                // print_r($situationId);
    
                return $query->whereIn(Orders::$tableName . '.' . Orders::$situationId, $situationIds);
            })
            ->join(
                Users::$tableName,
                Users::$tableName . '.' . Users::$id,
                '=',
                Orders::$tableName . '.' . Orders::$userId
            )
            ->join(
                OrderSituations::$tableName,
                OrderSituations::$tableName . '.' . OrderSituations::$id,
                '=',
                Orders::$tableName . '.' . Orders::$situationId
            )
            ->limit(7)
            ->offset($from)
            ->whereBetween(Orders::$tableName . '.' . Orders::$createdAt, [$fromDate, $toDate])
            // ->whereBetween(Orders::$tableName . '.' . Orders::$createdAt, ['2025-01-01', '2025-02-10'])

            ->orderByDesc(Orders::$tableName . '.' . Orders::$createdAt)
            ->get([
                OrderSituations::$tableName . '.' . OrderSituations::$name . ' as situation',
                OrderSituations::$tableName . '.' . OrderSituations::$id . ' as situationId',
                Users::$tableName . '.' . Users::$firstName . ' as userName',
                Users::$tableName . '.' . Users::$phone . ' as userPhone',
                Orders::$tableName . '.' . Orders::$id . ' as id',
                Orders::$tableName . '.' . Orders::$createdAt,

            ]);

        $orderIds = [];
        foreach ($orders as $key => $order) {
            $orderIds[] = $order->id;
        }

        $dataOrderAmounts = DB::table(table: OrdersAmounts::$tableName)
            ->whereIn(OrdersAmounts::$tableName . '.' . OrdersAmounts::$orderId, $orderIds)
            ->join(
                Currencies::$tableName,
                Currencies::$tableName . '.' . Currencies::$id,
                '=',
                OrdersAmounts::$tableName . '.' . OrdersAmounts::$currencyId
            )
            ->get(
                [
                    OrdersAmounts::$tableName . '.' . OrdersAmounts::$id . ' as id',
                    OrdersAmounts::$tableName . '.' . OrdersAmounts::$amount . ' as amount',
                    OrdersAmounts::$tableName . '.' . OrdersAmounts::$orderId . ' as orderId',
                    Currencies::$tableName . '.' . Currencies::$name . ' as currencyName',
                    Currencies::$tableName . '.' . Currencies::$id . ' as currencyId'

                ]
            );

        foreach ($orders as $key1 => $order) {
            $amounts = [];
            foreach ($dataOrderAmounts as $key2 => $amount) {
                if ($order->id == $amount->orderId) {
                    $amounts[] = $amount;
                }
            }
            $orders[$key1]->amounts = $amounts;
        }




        if ($withSituations === true) {
            return response()->json(['situations' => $situation, 'orders' => $orders]);
        }
        return response()->json($orders);
    }

    public function getInAppProducts(Request $request)
    {


        $this->validRequestV1($request, [
            'isSubs' => 'required|string|max:1'
        ]);

        $isSubs = $request->input('isSubs');

        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: true, storePoints: 2);
        $store = $myData['store'];
        $app = $myData['app'];

        // if ($withSituations === true) {
        $inAppProducts = DB::table(InAppProducts::$tableName)
            ->where(InAppProducts::$tableName . '.' . InAppProducts::$isSubs, '=', $isSubs)
            ->get();
        $googlePurchases = DB::table(GooglePurchases::$tableName)
            ->where(GooglePurchases::$tableName . '.' . GooglePurchases::$isSubs, '=', $isSubs)
            ->whereIn(GooglePurchases::$isPending, [1, 2])
            // ->where(GooglePurchases::$isPending, '=', 2)

            ->where(GooglePurchases::$storeId, '=', $store->id)
            ->get();

        foreach ($inAppProducts as $key => $inAppProduct) {
            $isPending = false;
            foreach ($googlePurchases as $key2 => $googlePurchase) {
                if ($googlePurchase->productId == $inAppProduct->productId) {
                    $inAppProducts[$key] = $this->processPurchase($app, $store, $googlePurchase, $inAppProduct, $googlePurchase->purchaseToken);
                    $isPending = $inAppProducts[$key]->isPending;
                    // if ($inAppProducts[$key]->isPending === true) {
                    //     $isPending = true;
                    // }
                }
            }
            $inAppProducts[$key]->isPending = $isPending;
            // if ($isPending === false) {
            //     $inAppProducts[$key]->isPending = false;
            // }else{

            // }
        }
        return response()->json($inAppProducts);
    }

    public function getStoreTime(Request $request)
    {
        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: true, storePoints: 2);
        $store = $myData['store'];
        // $app = $myData['app'];

        // if ($withSituations === true) {
        $times = DB::table(StoresTime::$tableName)
            ->where(StoresTime::$tableName . '.' . StoresTime::$storeId, '=', $store->id)
            ->get();

        for ($day = 1; $day <= 7; $day++) {
            // Find the store time for the current day
            $storeTime = $times->firstWhere('day', $day);

            // Add the day and store time to the result array
            $result[] = [
                "day" => $this->getDayName($day), // Get the day name (e.g., "Saturday")
                "storeTime" => $storeTime ?: null, // Store time or null if not found
            ];
        }

        return response()->json($result);
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

        return response()->json(['languages' => $languages, 'countries' => $countries]);
    }

    public function getMainCategories(Request $request)
    {
        $mainCatgories = DB::table(table: MainCategories::$tableName)
            ->get();
        return response()->json($mainCatgories);
    }


}