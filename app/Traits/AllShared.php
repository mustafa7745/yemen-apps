<?php
namespace App\Traits;
use App\Http\Controllers\Api\LoginController;
use App\Models\AppStores;
use App\Models\Categories;
use App\Models\Currencies;
use App\Models\DeliveryMen;
use App\Models\DevicesSessions;
use App\Models\Locations;
use App\Models\MyResponse;
use App\Models\NestedSections;
use App\Models\Options;
use App\Models\Orders;
use App\Models\OrdersAmounts;
use App\Models\OrdersDelivery;
use App\Models\OrdersPayments;
use App\Models\OrdersProducts;
use App\Models\PaymentTypes;
use App\Models\ProductImages;
use App\Models\Products;
use App\Models\ProductViews;
use App\Models\Sections;
use App\Models\SharedStoresConfigs;
use App\Models\StoreCategories;
use App\Models\StoreNestedSections;
use App\Models\StorePaymentTypes;
use App\Models\StoreProducts;
use App\Models\Stores;
use App\Models\StoreSections;
use App\Models\Users;
use App\Models\UsersSessions;
use App\Services\FirebaseService;
use Carbon\Carbon;
use DB;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Storage;
use Str;
use Validator;

trait AllShared
{
    public function getOurPaymentTypes(Request $request)
    {
        // $storeId = 1;
        $storeId = $request->input('storeId');
        $data = DB::table(table: StorePaymentTypes::$tableName)
            ->join(
                PaymentTypes::$tableName,
                PaymentTypes::$tableName . '.' . PaymentTypes::$id,
                '=',
                StorePaymentTypes::$tableName . '.' . StorePaymentTypes::$paymentTypeId
            )
            ->where(StorePaymentTypes::$tableName . '.' . StorePaymentTypes::$storeId, '=', $storeId)
            ->get([
                PaymentTypes::$tableName . '.' . PaymentTypes::$id,
                PaymentTypes::$tableName . '.' . PaymentTypes::$name,
                PaymentTypes::$tableName . '.' . PaymentTypes::$image,
            ]);

        return response()->json($data);
    }
    public function ourLogout(Request $request, $appId)
    {
        $resultAccessToken = $this->getAccessToken($request, $appId);
        if ($resultAccessToken->isSuccess == false) {
            return $this->responseError($resultAccessToken);
        }
        $accessToken = $resultAccessToken->message;

        // print_r($accessToken);

        return DB::transaction(function () use ($accessToken) {
            DB::table(table: UsersSessions::$tableName)
                ->where(UsersSessions::$id, '=', $accessToken->userSessionId)
                ->update([
                    UsersSessions::$isLogin => 0,
                    UsersSessions::$logoutCount => DB::raw(UsersSessions::$logoutCount . ' + 1'),
                    UsersSessions::$lastLogoutAt => Carbon::now()->format('Y-m-d H:i:s'),
                    UsersSessions::$updatedAt => Carbon::now()->format('Y-m-d H:i:s')
                ]);
            return response()->json([]);
        });
    }
    public function getOurUserProfile(Request $request, $appId)
    {
        $resultAccessToken = $this->getAccessToken($request, $appId);
        if ($resultAccessToken->isSuccess == false) {
            return $this->responseError($resultAccessToken);
        }
        $accessToken = $resultAccessToken->message;

        $data = DB::table(table: Users::$tableName)
            ->where(Users::$tableName . '.' . Users::$id, '=', $accessToken->userId)
            ->first([
                Users::$tableName . '.' . Users::$id,
                Users::$tableName . '.' . Users::$firstName,
                Users::$tableName . '.' . Users::$secondName,
                Users::$tableName . '.' . Users::$thirdName,
                Users::$tableName . '.' . Users::$lastName,
                Users::$tableName . '.' . Users::$logo,
            ]);

        return response()->json($data);
    }
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

        $storeConfig = null;
        $categories = null;
        $sections = null;
        $nestedSections = null;
        $storeIdReference = null;
        if ($typeId == 1) {
            $storeConfig = DB::table(table: SharedStoresConfigs::$tableName)
                ->where(SharedStoresConfigs::$tableName . '.' . SharedStoresConfigs::$storeId, '=', $storeId)
                ->first();
            $categories = json_decode($storeConfig->categories);
            $sections = json_decode($storeConfig->sections);
            $nestedSections = json_decode($storeConfig->nestedSections);
            $storeIdReference = $storeConfig->storeIdReference;
        }



        $storeCategories = DB::table(table: StoreCategories::$tableName)
            ->when($typeId == 1, function ($query) use ($categories, $storeIdReference) {
                if (count($categories) > 0) {
                    $query->whereNotIn(StoreCategories::$tableName . '.' . StoreCategories::$id, $categories);
                }
                return $query->where(StoreCategories::$tableName . '.' . StoreCategories::$storeId, $storeIdReference);
            })
            ->when($typeId == 2, function ($query) use ($storeId) {
                return $query->where(StoreCategories::$tableName . '.' . StoreCategories::$storeId, $storeId);
            })

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
        $storeSections = DB::table(StoreSections::$tableName)
            ->whereIn(
                StoreSections::$tableName . '.' . StoreSections::$storeCategoryId,
                $storeCategoriesIds

            )
            ->when($typeId == 1 && count($sections) > 0, function ($query) use ($sections) {
                return $query->whereNotIn(StoreSections::$tableName . '.' . StoreSections::$id, $sections);
            })
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
        foreach ($storeSections as $storeCategorySection) {
            $storeCategoriesSectionsIds[] = $storeCategorySection->id;
        }

        // $storeNestedSections = DB::table(StoreNestedSections::$tableName)
        //     ->join(
        //         NestedSections::$tableName,
        //         NestedSections::$tableName . '.' . NestedSections::$id,
        //         '=',
        //         StoreNestedSections::$tableName . '.' . StoreNestedSections::$nestedSectionId
        //     )

        //     ->whereIn(StoreNestedSections::$tableName . '.' . StoreNestedSections::$storeSectionId, $storeCategoriesSectionsIds)
        //     ->whereNotIn(StoreNestedSections::$tableName . '.' . StoreNestedSections::$id, $nestedSections)
        //     ->select(
        //         StoreNestedSections::$tableName . '.' . StoreNestedSections::$id . ' as id',
        //         StoreNestedSections::$tableName . '.' . StoreNestedSections::$storeSectionId . ' as storeSectionId',
        //         StoreNestedSections::$tableName . '.' . StoreNestedSections::$nestedSectionId . ' as nestedSectionId',
        //         NestedSections::$tableName . '.' . NestedSections::$name . ' as name',
        //     )
        //     ->get();
        $storeNestedSections = DB::table(StoreNestedSections::$tableName)
            ->join(
                NestedSections::$tableName,
                NestedSections::$tableName . '.' . NestedSections::$id,
                '=',
                StoreNestedSections::$tableName . '.' . StoreNestedSections::$nestedSectionId
            )

            ->whereIn(StoreNestedSections::$tableName . '.' . StoreNestedSections::$storeSectionId, $storeCategoriesSectionsIds)
            ->when($typeId == 1 && count($nestedSections) > 0, function ($query) use ($nestedSections) {
                // print_r()
                return $query->whereNotIn(StoreNestedSections::$tableName . '.' . StoreNestedSections::$id, $nestedSections);
            })
            ->select(
                StoreNestedSections::$tableName . '.' . StoreNestedSections::$id . ' as id',
                StoreNestedSections::$tableName . '.' . StoreNestedSections::$storeSectionId . ' as storeSectionId',
                StoreNestedSections::$tableName . '.' . StoreNestedSections::$nestedSectionId . ' as nestedSectionId',
                NestedSections::$tableName . '.' . NestedSections::$name . ' as nestedSectionName',
            )
            ->get();

        return response()->json(['storeCategories' => $storeCategories, 'storeSections' => $storeSections, 'storeNestedSections' => $storeNestedSections]);

        // return response()->json($storeCategories);
        // }
        // if ($typeId == 2) {
        //     $storeCategories = DB::table(table: StoreCategories::$tableName)
        //         ->where(StoreCategories::$tableName . '.' . StoreCategories::$storeId, $storeId)
        //         ->join(
        //             Categories::$tableName,
        //             Categories::$tableName . '.' . Categories::$id,
        //             '=',
        //             StoreCategories::$tableName . '.' . StoreCategories::$categoryId
        //         )
        //         ->get(
        //             [
        //                 StoreCategories::$tableName . '.' . StoreCategories::$id . ' as id',
        //                 Categories::$tableName . '.' . Categories::$id . ' as categoryId',
        //                 Categories::$tableName . '.' . Categories::$name . ' as categoryName'
        //             ]
        //         );

        //     $storeCategoriesIds = [];
        //     foreach ($storeCategories as $storeCategory) {
        //         $storeCategoriesIds[] = $storeCategory->id;
        //     }
        //     $storeSections = DB::table(StoreSections::$tableName)
        //         ->whereIn(
        //             StoreSections::$tableName . '.' . StoreSections::$storeCategoryId,
        //             $storeCategoriesIds

        //         )
        //         ->join(
        //             Sections::$tableName,
        //             Sections::$tableName . '.' . Sections::$id,
        //             '=',
        //             StoreSections::$tableName . '.' . StoreSections::$sectionId
        //         )
        //         ->select(
        //             StoreSections::$tableName . '.' . StoreSections::$id . ' as id',
        //             StoreSections::$tableName . '.' . StoreSections::$storeCategoryId . ' as storeCategoryId',
        //             Sections::$tableName . '.' . Sections::$name . ' as sectionName',
        //             Sections::$tableName . '.' . Sections::$id . ' as sectionId',
        //         )
        //         ->get()->toArray();

        //     $storeCategoriesSectionsIds = [];
        //     foreach ($storeSections as $storeCategorySection) {
        //         $storeCategoriesSectionsIds[] = $storeCategorySection->id;
        //     }

        //     $storeNestedSections = DB::table(StoreNestedSections::$tableName)
        //         ->join(
        //             NestedSections::$tableName,
        //             NestedSections::$tableName . '.' . NestedSections::$id,
        //             '=',
        //             StoreNestedSections::$tableName . '.' . StoreNestedSections::$nestedSectionId
        //         )

        //         ->whereIn(StoreNestedSections::$tableName . '.' . StoreNestedSections::$storeSectionId, $storeCategoriesSectionsIds)
        //         ->select(
        //             StoreNestedSections::$tableName . '.' . StoreNestedSections::$id . ' as id',
        //             StoreNestedSections::$tableName . '.' . StoreNestedSections::$storeSectionId . ' as storeSectionId',
        //             StoreNestedSections::$tableName . '.' . StoreNestedSections::$nestedSectionId . ' as nestedSectionId',
        //             NestedSections::$tableName . '.' . NestedSections::$name . ' as nestedSectionName',
        //         )
        //         ->get();

        //     return response()->json(['storeCategories' => $storeCategories, 'storeSections' => $storeSections, 'storeNestedSections' => $storeNestedSections]);
        // } else {
        //     return response()->json(['message' => 'Undefiend Store type', 'code' => 0], 400);
        // }

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
    public function getOurProducts2(Request $request)
    {
        $storeNestedSectionId = $request->input('storeNestedSectionId');
        $storeId = $request->input('storeId');
        // 
        $store = DB::table(Stores::$tableName)
            ->where(Stores::$tableName . '.' . Stores::$id, '=', $storeId)
            ->first([
                Stores::$tableName . '.' . Stores::$id,

                Stores::$tableName . '.' . Stores::$typeId
            ]);

        // print_r($store);


        $storeProducts = DB::table(StoreProducts::$tableName)

            // ->when($store->typeId == 1, function ($query) use ($store) {
            //     $storeConfig = DB::table(table: SharedStoresConfigs::$tableName)
            //         ->where(SharedStoresConfigs::$tableName . '.' . SharedStoresConfigs::$storeId, '=', $store->id)
            //         ->first();

            //     $productIds = json_decode($storeConfig->products);
            //     // print_r($productIds);fe

            //     return $query->whereNotIn(StoreProducts::$tableName . '.' . StoreProducts::$id, $productIds);
            // })
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
                Currencies::$tableName,
                Currencies::$tableName . '.' . Currencies::$id,
                '=',
                StoreProducts::$tableName . '.' . StoreProducts::$currencyId
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
            ->where(StoreProducts::$tableName . '.' . StoreProducts::$storeId, '=', $storeId)
            // ->join(
            //     Categories::$tableName,
            //     Categories::$tableName . '.' . Categories::$id,
            //     '=',
            //     StoreCategories::$tableName . '.' . StoreCategories::$categoryId
            // )
            //////
            // ->when($store->typeId == 1, function ($query) use ($store) {
            //     $storeConfig = DB::table(table: SharedStoresConfigs::$tableName)
            //         ->where(SharedStoresConfigs::$tableName . '.' . SharedStoresConfigs::$storeId, '=', $store->id)
            //         ->first();

            //     $productIds = json_decode($storeConfig->products);
            //     // print_r($productIds);

            //     return $query->where(StoreProducts::$tableName . '.' . StoreProducts::$storeId, '=', $storeConfig->storeIdReference)
            //         ->whereNotIn(StoreProducts::$tableName . '.' . StoreProducts::$id, $productIds);
            // })
            // ->when($store->typeId == 2, function ($query) use ($storeId) {
            //     return $query->where(StoreProducts::$tableName . '.' . StoreProducts::$storeId, '=', $storeId);
            // })

            ->where(StoreProducts::$tableName . '.' . StoreProducts::$storeNestedSectionId, '=', $storeNestedSectionId)
            ->select(
                StoreProducts::$tableName . '.' . StoreProducts::$id . ' as storeProductId',
                Products::$tableName . '.' . Products::$id . ' as productId',
                Products::$tableName . '.' . Products::$name . ' as productName',
                Products::$tableName . '.' . Products::$description . ' as productDescription',
                    //
                Products::$tableName . '.' . Products::$orderNo . ' as productOrder',
                Products::$tableName . '.' . Products::$orderAt . ' as productOrderAt',
                    //
                StoreProducts::$tableName . '.' . StoreProducts::$price . ' as price',
                StoreProducts::$tableName . '.' . StoreProducts::$productViewId . ' as productViewId',
                StoreProducts::$tableName . '.' . StoreProducts::$orderNo . ' as storeProductOrder',
                    // 
                Options::$tableName . '.' . Options::$id . ' as optionId',
                Options::$tableName . '.' . Options::$name . ' as optionName',
                    //
                Currencies::$tableName . '.' . Currencies::$id . ' as currencyId',
                Currencies::$tableName . '.' . Currencies::$name . ' as currencyName',
                Currencies::$tableName . '.' . Currencies::$sign . ' as currencySign',
                    //
                StoreNestedSections::$tableName . '.' . StoreNestedSections::$id . ' as storeNestedSectionId',



            )
            ->orderBy(Products::$tableName . '.' . Products::$orderNo, )
            ->orderBy(Products::$tableName . '.' . Products::$orderAt, 'desc')
            ->orderBy(StoreProducts::$tableName . '.' . StoreProducts::$orderNo)
            ->orderBy(StoreProducts::$tableName . '.' . StoreProducts::$orderAt, 'desc')
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
                ProductImages::$tableName . '.' . ProductImages::$id,

            )
            ->get();



        $result = [];
        foreach ($storeProducts as $product) {
            if (!isset($result[$product->productId])) {

                $images = [];
                foreach ($productImages as $index => $image) {
                    if ($image->productId == $product->productId) {
                        $images[] = ['id' => $image->id, 'image' => $image->image];
                        unset($productImages[$index]);
                    }
                }
                $result[$product->productId] = [
                    'product' => ['productId' => $product->productId, 'productName' => $product->productName, 'productDescription' => $product->productDescription, 'images' => $images, 'productViewId' => $product->productViewId],
                    'storeNestedSectionId' => $product->storeNestedSectionId,
                    'options' => []
                ];

                // $result[$product->productId]['images'] = $images;
            }

            $currency = ['id' => $product->currencyId, 'name' => $product->currencyName, 'sign' => $product->currencyName];

            // Add the option to the options array
            $result[$product->productId]['options'][] = ['optionId' => $product->optionId, 'storeProductId' => $product->storeProductId, 'name' => $product->optionName, 'price' => $product->price, 'currency' => $currency, 'storeProductOrder' => $product->storeProductOrder];
        }

        $productViews = DB::table(ProductViews::$tableName)->get(
            [
                ProductViews::$tableName . '.' . ProductViews::$id,
                ProductViews::$tableName . '.' . ProductViews::$name,
            ]
        );

        $data = [];

        foreach ($productViews as $key => $productView) {
            $products = [];
            foreach ($result as $key2 => $storeProduct) {
                if ($productView->id == $storeProduct['product']['productViewId']) {
                    $products[] = $storeProduct;
                }
            }
            $data[] = ['id' => $productView->id, 'name' => $productView->name, 'products' => $products];
        }


        return response()->json(array_values($data));
    }
    public function getOurProducts(Request $request)
    {
        $storeNestedSectionId = $request->input('storeNestedSectionId');
        $storeId = $request->input('storeId');
        // 
        $store = DB::table(Stores::$tableName)
            ->where(Stores::$tableName . '.' . Stores::$id, '=', $storeId)
            ->first([
                Stores::$tableName . '.' . Stores::$id,

                Stores::$tableName . '.' . Stores::$typeId
            ]);

        // print_r($store);


        $storeProducts = DB::table(StoreProducts::$tableName)

            // ->when($store->typeId == 1, function ($query) use ($store) {
            //     $storeConfig = DB::table(table: SharedStoresConfigs::$tableName)
            //         ->where(SharedStoresConfigs::$tableName . '.' . SharedStoresConfigs::$storeId, '=', $store->id)
            //         ->first();

            //     $productIds = json_decode($storeConfig->products);
            //     // print_r($productIds);fe

            //     return $query->whereNotIn(StoreProducts::$tableName . '.' . StoreProducts::$id, $productIds);
            // })
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
                Currencies::$tableName,
                Currencies::$tableName . '.' . Currencies::$id,
                '=',
                StoreProducts::$tableName . '.' . StoreProducts::$currencyId
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
            ->when($store->typeId == 1, function ($query) use ($store) {
                $storeConfig = DB::table(table: SharedStoresConfigs::$tableName)
                    ->where(SharedStoresConfigs::$tableName . '.' . SharedStoresConfigs::$storeId, '=', $store->id)
                    ->first();

                $productIds = json_decode($storeConfig->products);
                // print_r($productIds);
    
                return $query->where(StoreProducts::$tableName . '.' . StoreProducts::$storeId, '=', $storeConfig->storeIdReference)
                    ->whereNotIn(StoreProducts::$tableName . '.' . StoreProducts::$id, $productIds);
            })
            ->when($store->typeId == 2, function ($query) use ($storeId) {
                return $query->where(StoreProducts::$tableName . '.' . StoreProducts::$storeId, '=', $storeId);
            })

            ->where(StoreProducts::$tableName . '.' . StoreProducts::$storeNestedSectionId, '=', $storeNestedSectionId)
            ->select(
                StoreProducts::$tableName . '.' . StoreProducts::$id . ' as storeProductId',
                Products::$tableName . '.' . Products::$id . ' as productId',
                Products::$tableName . '.' . Products::$name . ' as productName',
                Products::$tableName . '.' . Products::$description . ' as productDescription',
                StoreProducts::$tableName . '.' . StoreProducts::$price . ' as price',
                StoreProducts::$tableName . '.' . StoreProducts::$productViewId . ' as productViewId',

                    // 
                Options::$tableName . '.' . Options::$id . ' as optionId',
                Options::$tableName . '.' . Options::$name . ' as optionName',
                    //
                Currencies::$tableName . '.' . Currencies::$id . ' as currencyId',
                Currencies::$tableName . '.' . Currencies::$name . ' as currencyName',
                Currencies::$tableName . '.' . Currencies::$sign . ' as currencySign',
                    //
                StoreNestedSections::$tableName . '.' . StoreNestedSections::$id . ' as storeNestedSectionId',



            )
            // ->orderBy(StoreProducts::$orderNo)   // Sort by orderNo column
            // ->orderBy(StoreProducts::$orderAt, 'desc')
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
                ProductImages::$tableName . '.' . ProductImages::$id,

            )
            ->get();



        $result = [];
        foreach ($storeProducts as $product) {
            if (!isset($result[$product->productId])) {

                $images = [];
                foreach ($productImages as $index => $image) {
                    if ($image->productId == $product->productId) {
                        $images[] = ['id' => $image->id, 'image' => $image->image];
                        unset($productImages[$index]);
                    }
                }
                $result[$product->productId] = [
                    'product' => ['productId' => $product->productId, 'productName' => $product->productName, 'productDescription' => $product->productDescription, 'images' => $images, 'productViewId' => $product->productViewId],
                    'storeNestedSectionId' => $product->storeNestedSectionId,
                    'options' => []
                ];

                // $result[$product->productId]['images'] = $images;
            }

            $currency = ['id' => $product->currencyId, 'name' => $product->currencyName, 'sign' => $product->currencyName];

            // Add the option to the options array
            $result[$product->productId]['options'][] = ['optionId' => $product->optionId, 'storeProductId' => $product->storeProductId, 'name' => $product->optionName, 'price' => $product->price, 'currency' => $currency];
        }

        $productViews = DB::table(ProductViews::$tableName)->get(
            [
                ProductViews::$tableName . '.' . ProductViews::$id,
                ProductViews::$tableName . '.' . ProductViews::$name,
            ]
        );

        $data = [];

        foreach ($productViews as $key => $productView) {
            $products = [];
            foreach ($result as $key2 => $storeProduct) {
                if ($productView->id == $storeProduct['product']['productViewId']) {
                    $products[] = $storeProduct;
                }
            }
            $data[] = ['id' => $productView->id, 'name' => $productView->name, 'products' => $products];
        }


        return response()->json(array_values($data));
    }
    public function searchOurProducts(Request $request)
    {
        $validation = $this->validRequest($request, [
            'storeId' => 'required|string|max:100',
            'search' => 'required|string|min:1',
        ]);
        if ($validation != null) {
            return $this->responseError($validation);
        }

        $search = $request->input('search');
        $storeId = $request->input('storeId');
        // 
        // print_r("storeId" . $storeId);
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
            ->join(
                Currencies::$tableName,
                Currencies::$tableName . '.' . Currencies::$id,
                '=',
                StoreProducts::$tableName . '.' . StoreProducts::$currencyId
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
            ->where(Products::$tableName . '.' . Products::$name, 'LIKE', '%' . $search . '%')
            ->orWhere(Options::$tableName . '.' . Options::$name, 'LIKE', '%' . $search . '%')

            ->select(
                StoreProducts::$tableName . '.' . StoreProducts::$id . ' as storeProductId',
                Products::$tableName . '.' . Products::$id . ' as productId',
                Products::$tableName . '.' . Products::$name . ' as productName',
                Products::$tableName . '.' . Products::$description . ' as productDescription',
                StoreProducts::$tableName . '.' . StoreProducts::$price . ' as price',
                StoreProducts::$tableName . '.' . StoreProducts::$productViewId . ' as productViewId',

                    // 
                Options::$tableName . '.' . Options::$id . ' as optionId',
                Options::$tableName . '.' . Options::$name . ' as optionName',
                    //
                Currencies::$tableName . '.' . Currencies::$id . ' as currencyId',
                Currencies::$tableName . '.' . Currencies::$name . ' as currencyName',
                Currencies::$tableName . '.' . Currencies::$sign . ' as currencySign',
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
                    'product' => ['productId' => $product->productId, 'productName' => $product->productName, 'productDescription' => $product->productDescription, 'images' => $images, 'productViewId' => $product->productViewId],
                    'storeNestedSectionId' => $product->storeNestedSectionId,
                    'options' => []
                ];

                // $result[$product->productId]['images'] = $images;
            }

            $currency = ['id' => $product->currencyId, 'name' => $product->currencyName, 'sign' => $product->currencyName];

            // Add the option to the options array
            $result[$product->productId]['options'][] = ['storeProductId' => $product->storeProductId, 'name' => $product->optionName, 'price' => $product->price, 'currency' => $currency];
        }

        $productViews = DB::table(ProductViews::$tableName)->get(
            [
                ProductViews::$tableName . '.' . ProductViews::$id,
                ProductViews::$tableName . '.' . ProductViews::$name,
            ]
        );

        $data = [];

        foreach ($productViews as $key => $productView) {
            $products = [];
            foreach ($result as $key2 => $storeProduct) {
                if ($productView->id == $storeProduct['product']['productViewId']) {
                    $products[] = $storeProduct;
                }
            }
            $data[] = ['id' => $productView->id, 'name' => $productView->name, 'products' => $products];
        }


        return response()->json(array_values($data));
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
            return response()->json(['message' => $myResult->message, 'code' => $myResult->code, 'errors' => []], $myResult->responseCode);
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
    public function getOurOrders(Request $request, $userId = null)
    {
        $storeId = $request->input('storeId');
        $dataOrders = DB::table(Orders::$tableName)
            ->where(Orders::$tableName . '.' . Orders::$storeId, '=', $storeId)
            ->when($userId != null, function ($query) use ($userId) {
                return $query->where(Orders::$tableName . '.' . Orders::$userId, '=', $userId);
            })
            ->join(
                Users::$tableName,
                Users::$tableName . '.' . Users::$id,
                '=',
                Orders::$tableName . '.' . Orders::$userId
            )
            ->get([
                Users::$tableName . '.' . Users::$firstName . ' as userName',
                Users::$tableName . '.' . Users::$phone . ' as userPhone',
                Orders::$tableName . '.' . Orders::$id . ' as id',
            ]);


        $orderIds = [];
        foreach ($dataOrders as $key => $order) {
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
                    Currencies::$tableName . '.' . Currencies::$name . ' as currencyName'
                ]
            );

        foreach ($dataOrders as $key1 => $order) {
            $amounts = [];
            foreach ($dataOrderAmounts as $key2 => $amount) {
                if ($order->id == $amount->orderId) {
                    $amounts[] = $amount;
                }
            }
            $dataOrders[$key1]->amounts = $amounts;
        }

        return response()->json($dataOrders);
    }

    public function getOurOrderDelivery(Request $request)
    {
        $orderId = $request->input('orderId');

        $data = DB::table(table: OrdersDelivery::$tableName)
            ->where(OrdersDelivery::$tableName . '.' . OrdersDelivery::$orderId, '=', $orderId)
            ->join(
                Locations::$tableName,
                Locations::$tableName . '.' . Locations::$id,
                '=',
                OrdersDelivery::$tableName . '.' . OrdersDelivery::$locationId
            )->first(
                columns: [
                    OrdersDelivery::$tableName . '.' . OrdersDelivery::$id . ' as id',
                    Locations::$tableName . '.' . Locations::$latLng . ' as latLng',
                    Locations::$tableName . '.' . Locations::$street . ' as street',
                    OrdersDelivery::$tableName . '.' . OrdersDelivery::$deliveryManId,
                    OrdersDelivery::$tableName . '.' . OrdersDelivery::$createdAt,
                    OrdersDelivery::$tableName . '.' . OrdersDelivery::$updatedAt
                ]
            );
        if ($data != null) {
            if ($data->deliveryManId != null) {
                $deliveryMan = DB::table(table: DeliveryMen::$tableName)
                    ->where(DeliveryMen::$tableName . '.' . DeliveryMen::$id, '=', $data->deliveryManId)
                    ->join(
                        Users::$tableName,
                        Users::$tableName . '.' . Users::$id,
                        '=',
                        DeliveryMen::$tableName . '.' . DeliveryMen::$userId
                    )->sole(
                        columns: [
                            DeliveryMen::$tableName . '.' . DeliveryMen::$id,
                            Users::$tableName . '.' . Users::$firstName,
                            Users::$tableName . '.' . Users::$lastName,
                            Users::$tableName . '.' . Users::$phone,
                        ]
                    );
                $data->deliveryMan = $deliveryMan;
            } else {
                $data->deliveryMan = null;
            }
        }

        return $data;
    }
    public function getOurOrderProducts(Request $request)
    {
        $orderId = $request->input('orderId');

        $dataOrderProducts = DB::table(table: OrdersProducts::$tableName)
            ->where(OrdersProducts::$tableName . '.' . OrdersProducts::$orderId, '=', $orderId)
            ->join(
                Currencies::$tableName,
                Currencies::$tableName . '.' . Currencies::$id,
                '=',
                OrdersProducts::$tableName . '.' . OrdersProducts::$currencyId
            )
            ->get(
                [
                    Currencies::$tableName . '.' . Currencies::$name . ' as currencyName',
                    OrdersProducts::$tableName . '.' . OrdersProducts::$productName . ' as productName',
                    OrdersProducts::$tableName . '.' . OrdersProducts::$storeProductId . ' as storeProductId',
                    OrdersProducts::$tableName . '.' . OrdersProducts::$productPrice . ' as price',
                    OrdersProducts::$tableName . '.' . OrdersProducts::$productQuantity . ' as quantity',
                    OrdersProducts::$tableName . '.' . OrdersProducts::$optionName,
                    OrdersProducts::$tableName . '.' . OrdersProducts::$id,
                ]
            );
        return $dataOrderProducts;
    }
    public function getOurOrderPayment(Request $request)
    {
        $orderId = $request->input('orderId');

        $dataOrderProducts = DB::table(table: OrdersPayments::$tableName)
            ->where(OrdersPayments::$tableName . '.' . OrdersPayments::$orderId, '=', $orderId)

            ->join(
                PaymentTypes::$tableName,
                PaymentTypes::$tableName . '.' . PaymentTypes::$id,
                '=',
                OrdersPayments::$tableName . '.' . OrdersPayments::$paymentId
            )
            ->first(
                [
                    OrdersPayments::$tableName . '.' . OrdersPayments::$id,
                    PaymentTypes::$tableName . '.' . PaymentTypes::$id . ' as paymentId',
                    PaymentTypes::$tableName . '.' . PaymentTypes::$name . ' as paymentName',
                    PaymentTypes::$tableName . '.' . PaymentTypes::$image . ' as paymentImage',
                    OrdersPayments::$tableName . '.' . OrdersPayments::$createdAt,
                    OrdersPayments::$tableName . '.' . OrdersPayments::$updatedAt,
                ]
            );
        return $dataOrderProducts;
    }
    public function getOurOrderDetail(Request $request)
    {
        $orderId = $request->input('orderId');

        $dataOrderProducts = DB::table(table: Orders::$tableName)
            ->where(Orders::$tableName . '.' . Orders::$id, '=', $orderId)
            ->sole();
        return $dataOrderProducts;
    }
    public function addOurLocation(Request $request, $appId)
    {
        $resultAccessToken = $this->getAccessToken($request, $appId);
        if ($resultAccessToken->isSuccess == false) {
            return $this->responseError($resultAccessToken);
        }

        $accessToken = $resultAccessToken->message;

        $validation = $this->validRequest($request, [
            'latLng' => 'required|string|max:100',
            'street' => 'required|string|max:100',
        ]);
        if ($validation != null) {
            return $this->responseError($validation);
        }
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
    public function confirmOurOrder(Request $request, $appId)
    {
        $validation = $this->validRequest($request, [
            'storeId' => 'required|string|max:100',
            'paid' => 'required|string|max:100',
            'orderProducts' => 'required|string|max:200',
        ]);
        if ($validation != null) {
            return $this->responseError($validation);
        }

        $resultAccessToken = $this->getAccessToken($request, $appId);
        if ($resultAccessToken->isSuccess == false) {
            return $this->responseError($resultAccessToken);
        }
        $accessToken = $resultAccessToken->message;

        $storeId = $request->input('storeId');
        $orderProducts = $request->input('orderProducts');
        $locationId = $request->input('locationId');
        $paid = $request->input('paid');


        $orderProducts = json_decode($orderProducts);

        $ids = [];

        foreach ($orderProducts as $orderProduct) {
            array_push($ids, $orderProduct->id);
        }

        $storeProducts = DB::table(StoreProducts::$tableName)
            ->whereIn(StoreProducts::$tableName . '.' . StoreProducts::$id, $ids)
            ->join(
                Products::$tableName,
                Products::$tableName . '.' . Products::$id,
                '=',
                StoreProducts::$tableName . '.' . StoreProducts::$productId
            )
            ->join(
                Currencies::$tableName,
                Currencies::$tableName . '.' . Currencies::$id,
                '=',
                StoreProducts::$tableName . '.' . StoreProducts::$currencyId
            )
            ->join(
                Options::$tableName,
                Options::$tableName . '.' . Options::$id,
                '=',
                StoreProducts::$tableName . '.' . StoreProducts::$optionId
            )
            ->get([
                StoreProducts::$tableName . '.' . StoreProducts::$id,
                StoreProducts::$tableName . '.' . StoreProducts::$price,
                Currencies::$tableName . '.' . Currencies::$id . ' as currencyId',
                Products::$tableName . '.' . Products::$name . ' as productName',
                Options::$tableName . '.' . Options::$name . ' as optionName',
            ]);


        if (count($storeProducts) != count($orderProducts)) {
            return "error";
        }

        return DB::transaction(function () use ($request, $accessToken, $storeId, $storeProducts, $orderProducts, $locationId, $paid) {

            $orderData = [
                Orders::$id => null,
                Orders::$storeId => $storeId,
                Orders::$userId => $accessToken->userId,
                Orders::$situationId => 1,
                Orders::$paid => $paid,
                Orders::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                Orders::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ];

            if ($locationId != null) {
                $orderData[Orders::$inStore] = $locationId; // 
            }

            $orderId = DB::table(Orders::$tableName)
                ->insertGetId($orderData);

            if ($paid != 0) {
                $paidCode = $request->input('paidCode');
                if ($paidCode == '123456') {
                    DB::table(OrdersPayments::$tableName)
                        ->insert(
                            [
                                OrdersPayments::$id => null,
                                OrdersPayments::$orderId => $orderId,
                                OrdersPayments::$paymentId => $paid,
                                OrdersPayments::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                                OrdersPayments::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                            ]
                        );
                }
            }

            if ($locationId != null) {
                DB::table(OrdersDelivery::$tableName)
                    ->insert([
                        OrdersDelivery::$id => null,
                        OrdersDelivery::$orderId => $orderId,
                        OrdersDelivery::$locationId => $locationId,
                        OrdersDelivery::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                        OrdersDelivery::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
            }


            // Initialize an empty array to hold the insert data
            $insertData = [];

            $orderProductAmountCurrencies = [];
            foreach ($storeProducts as $storeProduct) {
                $orderProductAmountSum = 0.0;
                foreach ($orderProducts as $orderProduct) {
                    if ($orderProduct->id == $storeProduct->id) {
                        $orderProductAmountSum += $storeProduct->price * $orderProduct->qnt;

                        $currencyId = $storeProduct->currencyId;

                        if (isset($orderProductAmountCurrencies[$currencyId])) {
                            $orderProductAmountCurrencies[$currencyId]['amount'] += $orderProductAmountSum;
                        } else {
                            // Otherwise, add the new currency entry
                            $orderProductAmountCurrencies[$currencyId] = [
                                'id' => $currencyId,
                                'amount' => $orderProductAmountSum
                            ];
                        }
                        break; // Exit the loop once we find the matching product
                    }
                }
            }



            $insertOrderAmount = [];
            foreach ($orderProductAmountCurrencies as $key => $item) {
                $insertOrderAmount[] = [
                    OrdersAmounts::$id => null, // Assuming auto-incremented ID
                    OrdersAmounts::$amount => $item['amount'],
                    OrdersAmounts::$currencyId => $item['id'],
                    OrdersAmounts::$orderId => $orderId,
                    OrdersAmounts::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                    OrdersAmounts::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                ];
            }

            if (!empty($insertOrderAmount)) {
                DB::table(OrdersAmounts::$tableName)->insert($insertOrderAmount);
            }


            foreach ($storeProducts as $storeProduct) {
                // Initialize productQuantity to a default value, e.g., 0
                $productQuantity = 0;

                // Find the quantity from the orderProducts
                foreach ($orderProducts as $orderProduct) {
                    if ($orderProduct->id == $storeProduct->id) {
                        $orderProductAmountSum += $storeProduct->price;
                        $productQuantity = $orderProduct->qnt;
                        break; // Exit the loop once we find the matching product
                    }
                }

                // Add the product to the insert array
                $insertData[] = [
                    OrdersProducts::$id => null, // Assuming auto-incremented ID
                    OrdersProducts::$productName => $storeProduct->productName,
                    OrdersProducts::$storeProductId => $storeProduct->id,
                    OrdersProducts::$productPrice => $storeProduct->price,
                    OrdersProducts::$productQuantity => $productQuantity,
                    OrdersProducts::$orderId => $orderId,
                    OrdersProducts::$optionName => $storeProduct->optionName,
                    OrdersProducts::$currencyId => $storeProduct->currencyId,
                    OrdersProducts::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                    OrdersProducts::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),

                ];
            }

            // Perform the bulk insert if there is any data to insert
            if (!empty($insertData)) {
                DB::table(OrdersProducts::$tableName)->insert($insertData);
            }

            $order = DB::table(Orders::$tableName)
                ->where(Orders::$id, $orderId)
                ->first();


            $user = DB::table(Stores::$tableName)
                ->where(Stores::$id, '=', $storeId)
                ->first([Stores::$userId]);
            $userSession = DB::table(UsersSessions::$tableName)
                ->join(
                    DevicesSessions::$tableName,
                    DevicesSessions::$tableName . '.' . DevicesSessions::$id,
                    '=',
                    UsersSessions::$tableName . '.' . UsersSessions::$deviceSessionId
                )
                ->where(UsersSessions::$tableName . '.' . UsersSessions::$userId, '=', $user->userId)
                ->where(UsersSessions::$tableName . '.' . UsersSessions::$isLogin, '=', 1)

                ->where(DevicesSessions::$tableName . '.' . DevicesSessions::$appId, '=', 1)
                ->first([DevicesSessions::$tableName . '.' . DevicesSessions::$appToken]);
            // ->where(UsersSessions::$tableName . '.' . UsersSessions::$userId ,'=',$user->id )


            // print_r($userSession);
            if ($userSession->appToken)
                (new FirebaseService())->sendNotification($userSession->appToken, "طلب جديد", $order->id . "رقم الطلب هو : ");

            DB::rollBack();
            return response()->json($order);
        });
    }
    public function updateOurProfile(Request $request, $appId)
    {
        $resultAccessToken = $this->getAccessToken($request, $appId);
        if ($resultAccessToken->isSuccess == false) {
            return $this->responseError($resultAccessToken);
        }
        $accessToken = $resultAccessToken->message;





        return DB::transaction(function () use ($request, $accessToken) {

            $firstName = $request->input('firstName');
            $secondName = $request->input('secondName');
            $thirdName = $request->input('thirdName');
            $lastName = $request->input('lastName');

            $logo = $request->file('logo');
            $cover = $request->file('cover');

            // if ($logo->isValid() == false) {
            //     return response()->json(['error' => 'Invalid Logo file.'], 400);
            // }

            // if ($cover->isValid() == false) {
            //     return response()->json(['error' => 'Invalid Cover file.'], 400);
            // }
            $updatedData = [
                Users::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                Users::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ];

            $logoName = Str::random(10) . '_' . time() . '.jpg';
            if ($logo != null) {
                $updatedData[Users::$logo] = $logoName;
            }

            // $coverName = Str::random(10) . '_' . time() . '.jpg';

            // if ($cover != null) {
            //     $updatedData[Stores::$cover] = $coverName;
            // }

            if ($firstName != null && strlen($firstName) > 0) {
                $updatedData[Users::$firstName] = $firstName;
            }
            if ($lastName != null && strlen($lastName) > 0) {
                $updatedData[Users::$lastName] = $lastName;
            }
            if ($thirdName != null && strlen($thirdName) > 0) {
                $updatedData[Users::$thirdName] = $thirdName;
            }
            if ($secondName != null && strlen($secondName) > 0) {
                $updatedData[Users::$secondName] = $secondName;
            }

            if (count($updatedData) == 2) {
                return response()->json(['message' => "Cant update empty values", 'errors' => [], 'code' => 0], 400);
            }

            $previousRecord = null;
            if ($logo != null) {
                $previousRecord = DB::table(Users::$tableName)
                    ->where(Users::$id, '=', $accessToken->userId)
                    ->sole();
            }


            DB::table(table: Users::$tableName)
                ->where(Users::$id, '=', $accessToken->userId)
                ->update(
                    $updatedData
                );

            try {
                if ($logo != null) {
                    Storage::disk('s3')->delete('users/logos/' . $previousRecord->logo);
                    $pathLogo = Storage::disk('s3')->put('users/logos/' . $logoName, fopen($logo, 'r+'));
                    if ($pathLogo == false) {
                        DB::rollBack();
                        return $this->responseError2('No valid Logo uploaded.', [], 0, 400);
                    }
                }
                // if ($cover != null) {
                //     Storage::disk('s3')->delete('stores/covers/' . $previousRecord->cover);
                //     $pathCover = Storage::disk('s3')->put('stores/covers/' . $coverName, fopen($cover, 'r+'));
                //     if ($pathCover == false) {
                //         DB::rollBack();
                //         return $this->responseError2('No valid Caver uploaded.', [], 0, 400);
                //     }
                // }
                $updatedRecord = DB::table(Users::$tableName)
                    ->where(Users::$id, '=', $accessToken->userId)
                    ->first();

                // $updatedRecord->storeConfig = null;


                return response()->json($updatedRecord);
            } catch (\Exception $e) {
                DB::rollBack();  // Manually trigger a rollback
                return response()->json([
                    'error' => 'An error occurred while uploading the image.',
                    'message' => $e->getMessage(),
                ], 500);
            }
        });
    }
    /////
    public function getAccessToken(Request $request, $appId)
    {
        $validation = $this->validRequest($request, [
            'accessToken' => 'required|string|max:255',
            'deviceId' => 'required|string|max:255'

        ]);
        if ($validation != null) {
            return $validation;
        }



        $loginController = (new LoginController($appId));
        $token = $request->input('accessToken');
        $deviceId = $request->input('deviceId');

        // print_r($request->all());
        // $myResult = 
        return $loginController->readAccessToken($token, $deviceId);
        // if ($myResult->isSuccess == false) {
        //     return response()->json(['message' => $myResult->message, 'code' => $myResult->code], $myResult->responseCode);
        // }
        // $accessToken = $myResult->message;

        // return $accessToken;
    }
    public function validRequest(Request $request, $rule)
    {
        $validator = Validator::make($request->all(), $rule);

        // Check if validation fails
        if ($validator->fails()) {
            $message = 'Validation failed';
            $errors = $validator->errors()->all();
            ;
            //
            $res = new MyResponse(false, $message, 422, 0);
            $res->errors = $errors;
            return $res;
        }
    }
    function responseError($response)
    {
        return response()->json(['message' => $response->message, 'errors' => $response->errors, 'code' => $response->code], $response->responseCode);
    }
    function responseError2($message, $errors, $messageCode, $responseCode)
    {
        return response()->json(['message' => $message, 'errors' => $errors, 'code' => $messageCode], $responseCode);
    }
    ///


}