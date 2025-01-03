<?php
namespace App\Traits;
use App\Http\Controllers\Api\LoginController;
use App\Models\AppStores;
use App\Models\Categories;
use App\Models\Locations;
use App\Models\NestedSections;
use App\Models\Options;
use App\Models\ProductImages;
use App\Models\Products;
use App\Models\Sections;
use App\Models\SharedStoresConfigs;
use App\Models\StoreCategories;
use App\Models\StoreNestedSections;
use App\Models\StoreProducts;
use App\Models\Stores;
use App\Models\StoreSections;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;

trait AllShared
{
    public function getOurHome(Request $request)
    {
        $storeId = $request->input('storeId');
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

    public function getOurStores($appId)
    {
        // $app = $this->getMyApp($request);
        // 
        $data = DB::table(AppStores::$tableName)
            ->join(
                Stores::$tableName,
                Stores::$tableName . '.' . Stores::$id,
                '=',
                AppStores::$tableName . '.' . AppStores::$storeId
            )

            ->where(AppStores::$appId, $appId)
            ->get([
                Stores::$tableName . '.' . Stores::$id,
                Stores::$tableName . '.' . Stores::$name,
                Stores::$tableName . '.' . Stores::$logo,
                Stores::$tableName . '.' . Stores::$cover,
                Stores::$tableName . '.' . Stores::$typeId,
                Stores::$tableName . '.' . Stores::$likes,
                Stores::$tableName . '.' . Stores::$subscriptions,
                Stores::$tableName . '.' . Stores::$stars,
                Stores::$tableName . '.' . Stores::$reviews,
            ]);

        $storeIds = [];
        foreach ($data as $store) {
            $storeIds[] = $store->id;
        }

        $storeConfigs = DB::table(table: SharedStoresConfigs::$tableName)
            ->whereIn(SharedStoresConfigs::$tableName . '.' . SharedStoresConfigs::$storeId, $storeIds)
            ->get();


        // 
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
        // }

        return $data;
    }

    public function getOurProducts(Request $request)
    {
        $storeNestedSectionId = $request->input('storeNestedSectionId');
        $storeId = $request->input('storeId');
        ;

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

    public function getOurLocations(Request $request, $appId)
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


        $loginController = (new LoginController($appId));
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
    public function addOurLocation(Request $request, $appId)
    {
        $validator = Validator::make($request->all(), [
            'accessToken' => 'required|string|max:255',
            'deviceId' => 'required|string|max:255',
            'latLng' => 'required|string|max:100',
            'street' => 'required|string|max:100',
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
        // 
        $latLng = $request->input('latLng');
        $street = $request->input('street');
        $insertedId = DB::table(table: Locations::$tableName)
            ->insertGetId([
                Locations::$id => null,
                Locations::$userId => $accessToken->userId,
                Locations::$latLng => $latLng,
                Locations::$street => $street,
                Locations::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                Locations::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

        $category = DB::table(Locations::$tableName)
            ->where(Locations::$tableName . '.' . Sections::$id, '=', $insertedId)
            ->sole([
                Locations::$tableName . '.' . Locations::$id,
                Locations::$tableName . '.' . Locations::$street,
            ]);
        return response()->json($category);
    }
    public function refreshOurToken(Request $request, $appId)
    {
        $token = $request->input('accessToken');
        $deviceId = $request->input('deviceId');

        $loginController = (new LoginController($appId));
        return $loginController->readAndRefreshAccessToken($token, $deviceId);
    }

}