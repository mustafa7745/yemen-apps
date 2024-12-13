<?php
namespace App\Http\Controllers\Api\StoreManager;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\NestedSections;
use App\Models\StoreSections;
use App\Models\StoreNestedSections;
use App\Models\Sections;
use App\Models\SharedStoresConfigs;
use App\Models\Stores;
use App\Models\StoreCategories;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Validator;

class StoreManagerController2 extends Controller
{
    private $appId = 1;
    public function getStores(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'accessToken' => 'required|string|max:255',
            'deviceId' => 'required|string|max:255',
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


        $stores = DB::table(Stores::$tableName)
            ->where(Stores::$tableName . '.' . Stores::$userId, '=', $accessToken->userId)

            ->get([
                Stores::$tableName . '.' . Stores::$id,
                Stores::$tableName . '.' . Stores::$name,
                Stores::$tableName . '.' . Stores::$typeId,
                // SharedStoresConfigs::$tableName . '.' . SharedStoresConfigs::$categories,
                // SharedStoresConfigs::$tableName . '.' . SharedStoresConfigs::$sections,
                // SharedStoresConfigs::$tableName . '.' . SharedStoresConfigs::$nestedSections,
                // SharedStoresConfigs::$tableName . '.' . SharedStoresConfigs::$products,
            ])->toArray();

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

    public function getCategories(Request $request)
    {
        $storeId = $request->input('storeId');
        $categories = DB::table(Categories::$tableName)
        ->where(Categories::$tableName . '.' . Categories::$id, '=', $storeId)
            ->get([
                Categories::$tableName . '.' . Categories::$id,
                Categories::$tableName . '.' . Categories::$name
            ])->toArray();
        return response()->json($categories);
    }
    public function getStoreCategories(Request $request)
    {
        $storeId = $request->input('storeId');
        $store = DB::table(Stores::$tableName)
            ->where(Stores::$tableName . '.' . Stores::$id, '=', $storeId)
            ->sole ([
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
                    NestedSections::$tableName . '.' . NestedSections::$name . ' as name',
                )
                ->get();

            return response()->json(['storeCategories' => $storeCategories, 'storeSections' => $storeCategoriesSections, 'storeNestedSections' => $csps]);
        } else {
            return response()->json(['message' => 'Undefiend Store type', 'code' => 0], 400);
        }

    }
    public function addStoreCategory(Request $request)
    {
        $storeId = 1;
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
    // 
    public function getSections(Request $request)
    {
        $category1Id = $request->input('category1Id');
        $storeId = 1;
        $categories = DB::table(Sections::$tableName)
            ->where(Sections::$tableName . '.' . Sections::$category1Id, '=', $category1Id)
            ->get([
                Sections::$tableName . '.' . Sections::$id,
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
    public function addSectionStoreCategory(Request $request)
    {
        $storeId = 1;
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
                    Sections::$tableName . '.' . Sections::$name . ' as sectionName'
                ]
            );

        return response()->json($storeCategory);
    }
    //
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
            ->join(
                Sections::$tableName,
                Sections::$tableName . '.' . Sections::$id,
                '=',
                NestedSections::$tableName . '.' . NestedSections::$sectionId
            )
            ->get(
                [
                    StoreNestedSections::$tableName . '.' . StoreNestedSections::$id . ' as id',
                    NestedSections::$tableName . '.' . NestedSections::$id . ' as nestedSectionId',
                    NestedSections::$tableName . '.' . NestedSections::$name . ' as category3Name',
                ]
            );

        return response()->json($storeCategories);
    }
    public function addStoreNestedSections(Request $request)
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
            ->join(
                Sections::$tableName,
                Sections::$tableName . '.' . Sections::$id,
                '=',
                NestedSections::$tableName . '.' . NestedSections::$sectionId
            )
            ->first(
                [
                    StoreNestedSections::$tableName . '.' . StoreNestedSections::$id . ' as id',
                    NestedSections::$tableName . '.' . NestedSections::$id . ' as nestedSectionId',
                    NestedSections::$tableName . '.' . NestedSections::$name . ' as category3Name',
                ]
            );

        return response()->json($storeCategory);
    }
    public function getCategories3(Request $request)
    {
        $sectionId = $request->input('sectionId');
        $categories = DB::table(NestedSections::$tableName)
            ->where(NestedSections::$tableName . '.' . NestedSections::$sectionId, '=', $sectionId)
            ->join(
                Sections::$tableName,
                Sections::$tableName . '.' . Sections::$id,
                '=',
                NestedSections::$tableName . '.' . NestedSections::$sectionId
            )
            ->get([
                NestedSections::$tableName . '.' . NestedSections::$id,
                NestedSections::$tableName . '.' . NestedSections::$name
            ])->toArray();
        return response()->json($categories);
    }



    public function updateStoreConfig(Request $request)
    {
        $storeId = $request->input('storeId');
        $products = $request->input('products');
        $nestedSections = $request->input('nestedSections');
        $sections = $request->input('sections');
        $categories = $request->input('categories');



        DB::table(table: SharedStoresConfigs::$tableName)
            ->where(SharedStoresConfigs::$storeId, '=', $storeId)
            ->update(
                [
                    SharedStoresConfigs::$categories => $categories,
                    SharedStoresConfigs::$sections => $sections,
                    SharedStoresConfigs::$nestedSections => $nestedSections,
                    SharedStoresConfigs::$products => $products,

                ]
            );
        $storeConfig = DB::table(table: SharedStoresConfigs::$tableName)
            ->where(SharedStoresConfigs::$storeId, '=', $storeId)
            ->first(
            );

        $categories = json_decode($storeConfig->categories);
        $sections = json_decode($storeConfig->sections);
        $nestedSections = json_decode($storeConfig->nestedSections);
        $products = json_decode($storeConfig->products);
        return response()->json(['storeIdReference' => $storeConfig->storeIdReference, 'categories' => $categories, 'sections' => $sections, 'nestedSections' => $nestedSections, 'products' => $products]);
    }

}
