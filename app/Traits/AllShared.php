<?php
namespace App\Traits;
use App\Http\Controllers\Api\LoginController;
use App\Models\Apps;
use App\Models\AppStores;
use App\Models\Categories;
use App\Models\Configuration;
use App\Models\Countries;
use App\Models\Currencies;
use App\Models\CustomPrices;
use App\Models\DeliveryMen;
use App\Models\DevicesSessions;
use App\Models\FailProcesses;
use App\Models\Languages;
use App\Models\Locations;
use App\Models\MyProcesses;
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
use App\Models\StoreAds;
use App\Models\StoreCategories;
use App\Models\StoreCurencies;
use App\Models\StoreNestedSections;
use App\Models\StorePaymentTypes;
use App\Models\StoreProducts;
use App\Models\Stores;
use App\Models\StoreSections;
use App\Models\StoresTime;
use App\Models\StoreSubscriptions;
use App\Models\Users;
use App\Models\UsersSessions;
use App\Models\WhatsappMessages;
use App\Models\YoutubeData;
use App\Services\FirebaseService;
use App\Services\WhatsappService;
use Carbon\Carbon;
use DB;
use Hash;
use Illuminate\Database\CustomException;
use Illuminate\Http\Request;
use libphonenumber\PhoneNumberUtil;
use Log;
use Storage;
use Str;
use Symfony\Component\Cache\Traits\Relay\NullableReturnTrait;
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
    public function ourLogout($userSessionId)
    {
        return DB::transaction(function () use ($userSessionId) {
            DB::table(table: UsersSessions::$tableName)
                ->where(UsersSessions::$id, '=', $userSessionId)
                ->update([
                    UsersSessions::$isLogin => 0,
                    UsersSessions::$logoutCount => DB::raw(UsersSessions::$logoutCount . ' + 1'),
                    UsersSessions::$lastLogoutAt => Carbon::now()->format('Y-m-d H:i:s'),
                    UsersSessions::$updatedAt => Carbon::now()->format('Y-m-d H:i:s')
                ]);
            return response()->json([]);
        });
    }
    public function getOurUserProfile($userId)
    {
        // $accessToken = (new LoginController($this->appId))->readAccessToken($request);

        $data = DB::table(table: Users::$tableName)
            ->where(Users::$tableName . '.' . Users::$id, '=', $userId)
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
    // public function getOurYoutubeData($storeId)
    // {
    //     // $accessToken = (new LoginController($this->appId))->readAccessToken($request);


    //     return response()->json($data);
    // }
    public function getOurHome($store)
    {
        $storeIdReference = null;
        if ($store->storeConfig != null) {
            $storeIdReference = $store->storeConfig['storeIdReference'];
        }



        $storeCategories = DB::table(table: StoreCategories::$tableName)
            ->when($store->typeId == 1, function ($query) use ($storeIdReference) {
                return $query->where(StoreCategories::$tableName . '.' . StoreCategories::$storeId, $storeIdReference);
            })
            ->when($store->typeId != 1, function ($query) use ($store) {
                return $query->where(StoreCategories::$tableName . '.' . StoreCategories::$storeId, $store->id);
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

        // print_r($storeCategories);

        $storeCategoriesIds = [];
        foreach ($storeCategories as $storeCategory) {
            $storeCategoriesIds[] = $storeCategory->id;
        }
        $storeSections = DB::table(StoreSections::$tableName)->whereIn(
            StoreSections::$tableName . '.' . StoreSections::$storeCategoryId,
            $storeCategoriesIds
        )
            // ->when($storeConfig != null && count($sections) > 0, function ($query) use ($sections) {
            //     return $query->whereNotIn(StoreSections::$tableName . '.' . StoreSections::$id, $sections);
            // })
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
            ->get();

        $storeCategoriesSectionsIds = [];
        foreach ($storeSections as $storeCategorySection) {
            $storeCategoriesSectionsIds[] = $storeCategorySection->id;
        }

        $storeNestedSections = DB::table(StoreNestedSections::$tableName)
            ->join(
                NestedSections::$tableName,
                NestedSections::$tableName . '.' . NestedSections::$id,
                '=',
                StoreNestedSections::$tableName . '.' . StoreNestedSections::$nestedSectionId
            )

            ->whereIn(StoreNestedSections::$tableName . '.' . StoreNestedSections::$storeSectionId, $storeCategoriesSectionsIds)
            // ->when($storeConfig != null && count($nestedSections) > 0, function ($query) use ($nestedSections) {
            //     // print_r()
            //     return $query->whereNotIn(StoreNestedSections::$tableName . '.' . StoreNestedSections::$id, $nestedSections);
            // })
            ->select(
                StoreNestedSections::$tableName . '.' . StoreNestedSections::$id . ' as id',
                StoreNestedSections::$tableName . '.' . StoreNestedSections::$storeSectionId . ' as storeSectionId',
                StoreNestedSections::$tableName . '.' . StoreNestedSections::$nestedSectionId . ' as nestedSectionId',
                NestedSections::$tableName . '.' . NestedSections::$name . ' as nestedSectionName',
            )
            ->get();

        $ads = DB::table(StoreAds::$tableName)
            ->where(StoreAds::$storeId, '=', $store->id)
            ->get();

        // [
        //     ['id' => 1, 'image' => 'https://couponswala.com/blog/wp-content/uploads/2022/09/Food-Combo-Offers.jpg', 'pid' => 43],
        //     ['id' => 1, 'image' => 'https://couponswala.com/blog/wp-content/uploads/2022/09/Food-Combo-Offers.jpg', 'pid' => 45],
        //     ['id' => 1, 'image' => 'https://couponswala.com/blog/wp-content/uploads/2022/09/Food-Combo-Offers.jpg', 'pid' => 3],
        //     ['id' => 1, 'image' => 'https://couponswala.com/blog/wp-content/uploads/2022/09/Food-Combo-Offers.jpg', 'pid' => 4],
        //     ['id' => 1, 'image' => 'https://couponswala.com/blog/wp-content/uploads/2022/09/Food-Combo-Offers.jpg', 'pid' => 5],

        // ];

        $currentDay = Carbon::now()->dayOfWeekIso; // 1 (Monday) to 7 (Sunday)

        // ملاحظة: إذا كنت تستخدم تنسيقك الخاص (1 = السبت، 2 = الأحد، ...)، عدّل هنا:
        $customDay = match ($currentDay) {
            6 => 1, // Saturday
            7 => 2, // Sunday
            1 => 3, // Monday
            2 => 4, // Tuesday
            3 => 5, // Wednesday
            4 => 6, // Thursday
            5 => 7, // Friday
        };

        $storeTime = DB::table(StoresTime::$tableName)
            ->where(StoresTime::$storeId, '=', $store->id)
            ->where(StoresTime::$day, '=', $customDay)
            ->first();


        $videoData = DB::table(YoutubeData::$tableName)
            ->where( YoutubeData::$tableName . '.' . YoutubeData::$storeId, '=', $store->id)
            ->get();

        return response()->json([
            'ads' => $ads,
            'storeCategories' => $storeCategories,
            'storeSections' => $storeSections,
            'storeNestedSections' => $storeNestedSections,
            'storeTime' => $storeTime,
            'videoData' => $videoData
        ]);
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
                Stores::$tableName . '.' . Stores::$latLng,

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
        $ads = [
            ['id' => 1, 'image' => 'https://couponswala.com/blog/wp-content/uploads/2022/09/Food-Combo-Offers.jpg']
        ];

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

        foreach ($data as $index => $store) {
            $res = [];
            foreach ($storeCurrencies as $key => $storeCurrency) {
                if ($store->id == $storeCurrency->storeId) {
                    $res[] = $storeCurrency;
                }
            }
            $data[$index]->storeCurrencies = $res;
        }


        // }

        return $data;
    }
    function checkStoreOpen($storeId)
    {
        $now = Carbon::now();

        // تحويل اليوم الحالي إلى تنسيقك (1 = السبت)
        $dayMap = [
            6 => 1, // Saturday
            0 => 2, // Sunday
            1 => 3, // Monday
            2 => 4, // Tuesday
            3 => 5, // Wednesday
            4 => 6, // Thursday
            5 => 7, // Friday
        ];
        $customDay = $dayMap[$now->dayOfWeek];

        // جلب وقت العمل من الجدول
        $storeTime = DB::table(StoresTime::$tableName)
            ->where(StoresTime::$storeId, '=', $storeId)
            ->where(StoresTime::$day, '=', $customDay)
            ->first();

        if (!$storeTime || $storeTime->isOpen != 1) {
            throw new CustomException("Store in this day Closed", 0, 442);
        }

        // وقت الفتح
        $openAt = Carbon::createFromTimeString($storeTime->openAt);

        // وقت الإغلاق (يدعم ما بعد منتصف الليل)
        $closeParts = explode(':', $storeTime->closeAt);
        $closeHour = (int) $closeParts[0];
        $closeMinute = (int) $closeParts[1];
        $closeSecond = (int) $closeParts[2];

        $closeAt = $now->copy()->setTime($closeHour % 24, $closeMinute, $closeSecond);
        if ($closeHour >= 24) {
            $closeAt->addDay();
        }

        // ضبط وقت الفتح من نفس اليوم
        $openAt = $now->copy()->setTimeFromTimeString($storeTime->openAt);

        $isOpen = $now->between($openAt, $closeAt);

        if (!$isOpen) {
            throw new CustomException("Store Closed", 0, 442);
        }
    }

    public function getOurProducts2(Request $request)
    {
        $storeNestedSectionId = $request->input('storeNestedSectionId');
        $storeId = $request->input('storeId');
        $shared = $request->input('shared');
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
            $result[$product->productId]['options'][] = ['optionId' => $product->optionId, 'storeProductId' => $product->storeProductId, 'name' => $product->optionName, 'price' => $product->price, 'currency' => $currency, 'storeProductOrder' => $product->storeProductOrder, 'isCustomPrice' => false];
        }

        $productViews = DB::table(ProductViews::$tableName)->get(
            [
                ProductViews::$tableName . '.' . ProductViews::$id,
                ProductViews::$tableName . '.' . ProductViews::$name,
            ]
        );


        $customPrices = DB::table(CustomPrices::$tableName)
            ->where(CustomPrices::$storeId, $storeId)
            ->get();

        $data = [];

        foreach ($productViews as $key => $productView) {
            $products = [];
            foreach ($result as $storeProductIndex => $storeProduct) {
                // foreach ($storeProduct['options'] as $optionIndex => $option) {
                //     foreach ($customPrices as $key => $customPrice) {



                //         if ($option['storeProductId'] == $customPrice->storeProductId) {
                //             // print_r($option['storeProductId']);
                //             // print_r($customPrice->storeProductId);

                //             print_r($result['product']);
                //             $result[$storeProductIndex]['options'][$optionIndex]['isCustomPrice'] = true;
                //             $result[$storeProductIndex]['options'][$optionIndex]['price'] = $customPrice->price;
                //         }
                //     }
                // }
                ///
                if ($productView->id == $storeProduct['product']['productViewId']) {
                    $products[] = $storeProduct;
                }
            }
            foreach ($products as $storeProductIndex => $storeProduct) {
                // print_r($storeProduct);
                foreach ($storeProduct['options'] as $optionIndex => $option) {
                    foreach ($customPrices as $key => $customPrice) {
                        if ($option['storeProductId'] == $customPrice->storeProductId) {
                            // print_r($option['storeProductId']);
                            // print_r($customPrice->storeProductId);

                            // print_r($result['product']);
                            $products[$storeProductIndex]['options'][$optionIndex]['isCustomPrice'] = true;
                            $products[$storeProductIndex]['options'][$optionIndex]['price'] = $customPrice->price;
                        }
                    }
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
    public function getOurLocations($userId, $storeId)
    {







        // print_r($request->all());


        $data = DB::table(table: Locations::$tableName)
            ->where(Locations::$tableName . '.' . Locations::$userId, '=', $userId)
            ->get(
                [
                    Locations::$tableName . '.' . Locations::$id,
                    Locations::$tableName . '.' . Locations::$street,
                    Locations::$tableName . '.' . Locations::$latLng,
                ]
            );

        $store = DB::table(table: Stores::$tableName)
            ->join(
                Currencies::$tableName,
                Currencies::$tableName . '.' . Currencies::$id,
                '=',
                Stores::$tableName . '.' . Stores::$deliveryPriceCurrency
            )
            ->where(Stores::$tableName . '.' . Stores::$id, '=', $storeId)->first(
                [
                    Currencies::$tableName . '.' . Currencies::$id . ' as currencyId',
                    Currencies::$tableName . '.' . Currencies::$name . ' as currencyName',
                    Stores::$tableName . '.' . Stores::$deliveryPrice,
                    Stores::$tableName . '.' . Stores::$latLng,
                ]
            );


        foreach ($data as $key => $location) {
            $parts = explode(",", $store->latLng);

            // Extract the latitude and longitude
            $latitude1 = (float) $parts[0];
            $longitude1 = (float) $parts[1];
            //
            $parts = explode(",", $location->latLng);

            // Extract the latitude and longitude
            $latitude2 = (float) $parts[0];
            $longitude2 = (float) $parts[1];

            $distance = $this->getDistance($latitude1, $longitude1, $latitude2, $longitude2);
            // print_r($store->deliveryPrice);
            // print_r($distance);

            $deliveryPrice = 50 * round(num: ($distance * $store->deliveryPrice) / 50);
            $data[$key]->deliveryPrice = ['deliveryPrice' => $deliveryPrice, 'currencyId' => $store->currencyId, 'currencyName' => $store->currencyName,];
        }
        return response()->json($data);
    }

    public function getOurLocationsV1($storeId, $userId)
    {
        $data = DB::table(table: Locations::$tableName)
            ->where(Locations::$tableName . '.' . Locations::$userId, '=', $userId)
            ->get(
                [
                    Locations::$tableName . '.' . Locations::$id,
                    Locations::$tableName . '.' . Locations::$street,
                    Locations::$tableName . '.' . Locations::$latLng,
                ]
            );

        $store = DB::table(table: Stores::$tableName)
            ->join(
                Currencies::$tableName,
                Currencies::$tableName . '.' . Currencies::$id,
                '=',
                Stores::$tableName . '.' . Stores::$deliveryPriceCurrency
            )
            ->where(Stores::$tableName . '.' . Stores::$id, '=', $storeId)->first(
                [
                    Currencies::$tableName . '.' . Currencies::$id . ' as currencyId',
                    Currencies::$tableName . '.' . Currencies::$name . ' as currencyName',
                    Stores::$tableName . '.' . Stores::$deliveryPrice,
                    Stores::$tableName . '.' . Stores::$latLng,
                ]
            );


        foreach ($data as $key => $location) {
            $parts = explode(",", $store->latLng);

            // Extract the latitude and longitude
            $latitude1 = (float) $parts[0];
            $longitude1 = (float) $parts[1];
            //
            $parts = explode(",", $location->latLng);

            // Extract the latitude and longitude
            $latitude2 = (float) $parts[0];
            $longitude2 = (float) $parts[1];

            $distance = $this->getDistance($latitude1, $longitude1, $latitude2, $longitude2);
            // print_r($store->deliveryPrice);
            // print_r($distance);

            $deliveryPrice = 50 * round(num: ($distance * $store->deliveryPrice) / 50);
            $data[$key]->deliveryPrice = ['deliveryPrice' => $deliveryPrice, 'currencyId' => $store->currencyId, 'currencyName' => $store->currencyName,];
        }
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
            ->limit(10)
            ->orderByDesc(Orders::$tableName . '.' . Orders::$createdAt)
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
                    Currencies::$tableName . '.' . Currencies::$name . ' as currencyName',
                    Currencies::$tableName . '.' . Currencies::$id . ' as currencyId'

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
            )
            ->join(
                Currencies::$tableName,
                Currencies::$tableName . '.' . Currencies::$id,
                '=',
                OrdersDelivery::$tableName . '.' . OrdersDelivery::$deliveryPriceCurrency
            )
            ->first(
                columns: [
                    OrdersDelivery::$tableName . '.' . OrdersDelivery::$id . ' as id',
                    Locations::$tableName . '.' . Locations::$latLng . ' as latLng',
                    Locations::$tableName . '.' . Locations::$street . ' as street',
                    OrdersDelivery::$tableName . '.' . OrdersDelivery::$deliveryManId,
                    OrdersDelivery::$tableName . '.' . OrdersDelivery::$deliveryPrice,
                    Currencies::$tableName . '.' . Currencies::$id . ' as currencyId',
                    Currencies::$tableName . '.' . Currencies::$name . ' as currencyName',
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
                    Currencies::$tableName . '.' . Currencies::$id . ' as currencyId',
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
    public function addOurLocation(Request $request, $userId)
    {
        $this->validRequest($request, [
            'latLng' => 'required|string|max:100',
            'street' => 'required|string|max:100',
        ]);
        // $resultAccessToken = $this->getAccessToken($request, $appId);
        // if ($resultAccessToken->isSuccess == false) {
        //     return $this->responseError($resultAccessToken);
        // }

        // $accessToken = $resultAccessToken->message;

        // $validation = $this->validRequest($request, [
        //     'latLng' => 'required|string|max:100',
        //     'street' => 'required|string|max:100',
        // ]);
        // if ($validation != null) {
        //     return $this->responseError($validation);
        // }
        // // 
        $latLng = $request->input('latLng');
        $street = $request->input('street');
        $insertedId = DB::table(table: Locations::$tableName)
            ->insertGetId([
                Locations::$id => null,
                Locations::$userId => $userId,
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
        return (new LoginController($appId))->readAndRefreshAccessToken($request);
        // return $loginController;
    }
    public function confirmOurOrder(Request $request, $userId, $storeId)
    {
        $this->validRequest($request, [
            'paid' => 'required|string|max:100',
            'orderProducts' => 'required|string|max:200',
        ]);
        // if ($validation != null) {
        //     return $this->responseError($validation);
        // }

        // $resultAccessToken = $this->getAccessToken($request, $appId);
        // if ($resultAccessToken->isSuccess == false) {
        //     return $this->responseError($resultAccessToken);
        // }
        // $accessToken = $resultAccessToken->message;
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

        return DB::transaction(function () use ($request, $userId, $storeId, $storeProducts, $orderProducts, $locationId, $paid) {

            $orderData = [
                Orders::$id => null,
                Orders::$storeId => $storeId,
                Orders::$userId => $userId,
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
                $store = DB::table(table: Stores::$tableName)
                    ->join(
                        Currencies::$tableName,
                        Currencies::$tableName . '.' . Currencies::$id,
                        '=',
                        Stores::$tableName . '.' . Stores::$deliveryPriceCurrency
                    )
                    ->where(Stores::$tableName . '.' . Stores::$id, '=', $storeId)->first(
                        [
                            Currencies::$tableName . '.' . Currencies::$id . ' as currencyId',
                            Currencies::$tableName . '.' . Currencies::$name . ' as currencyName',
                            Stores::$tableName . '.' . Stores::$deliveryPrice,
                            Stores::$tableName . '.' . Stores::$latLng,
                        ]
                    );

                $location = DB::table(table: Locations::$tableName)->where(Locations::$tableName . '.' . Locations::$id, '=', $locationId)->first([Locations::$tableName . '.' . Locations::$latLng]);
                $parts = explode(",", $store->latLng);

                // Extract the latitude and longitude
                $latitude1 = (float) $parts[0];
                $longitude1 = (float) $parts[1];
                //
                $parts = explode(",", $location->latLng);

                // Extract the latitude and longitude
                $latitude2 = (float) $parts[0];
                $longitude2 = (float) $parts[1];

                $distance = $this->getDistance($latitude1, $longitude1, $latitude2, $longitude2);
                // print_r($store->deliveryPrice);
                // print_r($distance);

                $deliveryPrice = 50 * round(num: ($distance * $store->deliveryPrice) / 50);

                DB::table(OrdersDelivery::$tableName)
                    ->insert([
                        OrdersDelivery::$id => null,
                        OrdersDelivery::$orderId => $orderId,
                        OrdersDelivery::$deliveryPrice => $deliveryPrice,
                        OrdersDelivery::$deliveryPriceCurrency => $store->currencyId,
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
            if ($userSession)
                (new FirebaseService(storage_path('Fire/yemen-stores-firebase-adminsdk-7qbeo-d9befb63dc.json')))->sendNotification($userSession->appToken, "طلب جديد", "رقم الطلب هو : " . $order->id);

            // DB::rollBack();
            return response()->json($order);
        });
    }
    public function updateOurProfile($request, $userId)
    {

        return DB::transaction(function () use ($request, $userId) {

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
                    ->where(Users::$id, '=', $userId)
                    ->sole();
            }


            DB::table(table: Users::$tableName)
                ->where(Users::$id, '=', $userId)
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
                    ->where(Users::$id, '=', $userId)
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
        $this->validRequesV1t($request, [
            'accessToken' => 'required|string|max:255',
            'deviceId' => 'required|string|max:255'

        ]);




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

    public function getCustomPrices(Request $request)
    {
        $storeId = $request->input('storeId');
        $products = DB::table(CustomPrices::$tableName)
            ->where(CustomPrices::$tableName . '.' . CustomPrices::$storeId, '=', $storeId)
            ->get(
                [
                    CustomPrices::$tableName . '.' . CustomPrices::$id,
                    CustomPrices::$tableName . '.' . CustomPrices::$storeProductId,
                    CustomPrices::$tableName . '.' . CustomPrices::$price,
                ]
            );


        return response()->json($products);
    }

    // protected 

    // public function __construct(WhatsappService $whatsapp)
    // {
    //     $this->whatsapp = $whatsapp;
    // }
    // public function whatsapp_webhook(Request $request)
    // {
    //     $whatsapp = new WhatsappService();
    //     // $verifyToken = '774519161'; // Replace with your verify token
    //     // $challenge = $request->query('hub_challenge');
    //     // $token = $request->query('hub_verify_token');

    //     // if ($token === $verifyToken) {
    //     //     return response($challenge, 200);
    //     // }

    //     // return response()->json(['error' => 'Invalid verify token'], 403);

    //     // $hub_verify_token = "774519161";
    //     // if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['hub_challenge']) && isset($_GET['hub_verify_token']) && $_GET["hub_verify_token"] === $hub_verify_token) {
    //     //     echo $_GET['hub_challenge'];
    //     //     exit;
    //     // }

    //     // Log::info('WhatsApp Webhook Payload:', $request->all());


    //     // $input = file_get_contents('php://input');
    //     // $input = json_decode($input, true);
    //     // $message = $input['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'];
    //     // $phone_number = $input['entry'][0]['changes'][0]['value']['contacts'][0]['wa_id'];
    //     $phoneNumber = $request->input('entry.0.changes.0.value.messages.0.from');
    //     $message = $request->input('entry.0.changes.0.value.messages.0.text.body');
    //     // $countryCode = substr($phoneNumber, 0, 3);
    //     // $phone = substr($phoneNumber, 3);
    //     //
    //     $phoneUtil = PhoneNumberUtil::getInstance();
    //     // try {
    //     //     $swissNumberProto = $phoneUtil->parse($swissNumberStr, "CH");
    //     //     var_dump($swissNumberProto);
    //     // } catch (\libphonenumber\NumberParseException $e) {
    //     //     var_dump($e);
    //     // }


    //     // Get the region code (e.g., 'GB' for United Kingdom)
    //     $number = $phoneUtil->parse("+" . $phoneNumber, null);
    //     // Get the country code
    //     $countryCode = $number->getCountryCode();
    //     $regionCode = $phoneUtil->getRegionCodeForNumber($number);
    //     $nationalNumber = $number->getNationalNumber();
    //     $user = DB::table(Users::$tableName)
    // ->join(
    //     Countries::$tableName,
    //     Countries::$tableName . '.' . Countries::$id,
    //     '=',
    //     Users::$tableName . '.' . Users::$countryId
    // )
    // ->where(Users::$tableName . '.' . Users::$phone, '=', $nationalNumber)
    // ->where(Countries::$tableName . '.' . Countries::$code, '=', $countryCode)
    // ->where(Countries::$tableName . '.' . Countries::$region, '=', $regionCode)
    //         ->first(
    //             [
    // Users::$tableName . '.' . Users::$id,
    // Users::$tableName . '.' . Users::$firstName,
    // Users::$tableName . '.' . Users::$lastName,
    //             ]
    //         );
    //     if ($message == "اشتراك") {
    //         if ($user == null) {
    //             $country = DB::table(Countries::$tableName)
    //                 // ->where(Users::$tableName . '.' . Users::$countryCode, '=', $countryCode)
    //                 ->where(Countries::$tableName . '.' . Countries::$code, '=', $countryCode)
    //                 ->where(Countries::$tableName . '.' . Countries::$region, '=', $regionCode)

    //                 ->first();

    //             $countryId = null;
    //             if ($country == null) {
    //                 $countryId = DB::table(table: Countries::$tableName)
    //                     ->insertGetId([
    //                         Countries::$id => null,
    //                         Countries::$code => $countryCode,
    //                         Countries::$region => $regionCode,
    //                         Countries::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
    //                         Countries::$updatedAt => Carbon::now()->format('Y-m-d H:i:s')
    //                     ]);
    //             } else {
    //                 $countryId = $country->id;
    //             }

    //             $name = $request->input('entry.0.changes.0.value.contacts.0.profile.name');
    //             $password = $this->generateRandomPassword();
    //             $hashedPassword = Hash::make($password);
    //             $insertedId = DB::table(table: Users::$tableName)
    //                 ->insertGetId([
    //                     Users::$id => null,
    //                     Users::$firstName => $name,
    //                     Users::$lastName => $name,
    //                     Users::$phone => $nationalNumber,
    //                     Users::$password => $hashedPassword,
    //                     Users::$countryId => $countryId,
    //                     Users::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
    //                     Users::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
    //                 ]);
    //             $message = "تم اضافة هذا المستخدم بنجاح";
    //             $message = $message . "\n";
    //             $message = $message . "معلومات الدخول: ";
    //             $message = $message . "\n";
    //             $message = $message . "المنطقة: " . ' ' . $regionCode;
    //             $message = $message . "\n";
    //             $message = $message . "رقم الهاتف هو: ";
    //             $message = $message . "\n";
    //             $message = $message . "+" . $countryCode . $nationalNumber;
    //             $message = $message . "\n";

    //             $message = $message . "الرقم السري هو: ";
    //             $whatsapp->sendMessageText($phoneNumber, $message);
    //             $whatsapp->sendMessageText($phoneNumber, $password);
    //         } else {
    //             $message = "هذا المستخدم لديه حساب مسبق";
    //             $whatsapp->sendMessageText($phoneNumber, $message);
    //         }
    //     } elseif ($message == "نسيت كلمة المرور") {
    //         if ($user == null) {
    //             $message = "يجب الاشتراك اولا";
    //             $whatsapp->sendMessageText($phoneNumber, $message);
    //         } else {
    //             $password = $this->generateRandomPassword();
    //             $hashedPassword = Hash::make($password);
    //             ///
    //             DB::table(table: Users::$tableName)
    //                 ->where(Users::$id, '=', $user->id)
    //                 ->update(
    //                     [
    //                         Users::$password => $hashedPassword,
    //                         Users::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
    //                     ]
    //                 );
    //             $message = "الرقم السري الجديد هو: ";
    //             $whatsapp->sendMessageText($phoneNumber, $message);
    //             $whatsapp->sendMessageText($phoneNumber, $password);
    //             // $this->whatsapp->sendMessageText($phoneNumber, $message);
    //         }

    //     } elseif (preg_match('/^رمز التطبيق \{.*\}$/u', $message)) {
    //         // if ($user != null) {
    //         //     $whatsappMessage = DB::table(WhatsappMessages::$tableName)
    //         //         ->where(WhatsappMessages::$tableName . '.' . WhatsappMessages::$userId, '=', $user->id)
    //         //         ->first(
    //         //             [
    //         //                 Users::$tableName . '.' . Users::$id,
    //         //                 Users::$tableName . '.' . Users::$firstName,
    //         //                 Users::$tableName . '.' . Users::$lastName,
    //         //             ]
    //         //         );
    //         //     if ($whatsappMessage->sevicesMode == 1) {
    //         //         if ($message == 1) {
    //         //         }

    //         //     }
    //         // }


    //         // $storeId = null;
    //         preg_match('/\{(.*?)\}/', $message, $matches);
    //         $storeId = $matches[1] ?? null;
    //         // if (preg_match('/\{(\d+)\}/', $message, $matches)) {
    //         //     $storeId = $matches[1]; // الرقم المستخرج
    //         //     // echo $number; // الناتج: 10
    //         // } else {
    //         //     $whatsapp->sendMessageText($phoneNumber, "uncorrect format");
    //         //     return response()->json(['success' => true]);
    //         // }

    //         if ($user == null) {
    //             $message = "يجب الاشتراك اولا";
    //             $whatsapp->sendMessageText($phoneNumber, $message);
    //         } else {
    //             $password = $this->generateRandomPassword();
    //             $hashedPassword = Hash::make($password);
    //             ///
    //             $whatsapp->sendMessageText($phoneNumber, $storeId);
    //             $whatsapp->sendMessageText($phoneNumber, $user->id);

    // $app = DB::table(table: AppStores::$tableName)
    //     ->where(AppStores::$tableName . '.' . AppStores::$storeId, '=', $storeId)
    //     ->where(Stores::$tableName . '.' . Stores::$userId, '=', $user->id)

    //     ->join(
    //         Apps::$tableName,
    //         Apps::$tableName . '.' . Apps::$id,
    //         '=',
    //         AppStores::$tableName . '.' . AppStores::$appId
    //     )
    //     ->join(
    //         Stores::$tableName,
    //         Stores::$tableName . '.' . Stores::$id,
    //         '=',
    //         AppStores::$tableName . '.' . AppStores::$storeId
    //     )
    //     ->join(
    //         Users::$tableName,
    //         Users::$tableName . '.' . Users::$id,
    //         '=',
    //         Stores::$tableName . '.' . Stores::$userId
    //     )
    //     ->first(
    //         [Apps::$tableName . '.' . Apps::$id . ' as id']
    //     );

    //             if ($app == null) {
    //                 $whatsapp->sendMessageText($phoneNumber, "app not found");
    //                 return response()->json(['success' => true]);
    //             }

    //             // $whatsapp->sendMessageText($phoneNumber, json_encode($app) . " ID");

    //             DB::table(table: Apps::$tableName)
    //                 ->where(Apps::$id, '=', $app->id)
    //                 ->update(
    //                     [
    //                         Apps::$password => $hashedPassword,
    //                         Apps::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
    //                     ]
    //                 );
    //             $message = "الرقم السري الجديد للتطبيق هو: ";
    //             $whatsapp->sendMessageText($phoneNumber, $message);
    //             $whatsapp->sendMessageText($phoneNumber, $password);
    //             // $this->whatsapp->sendMessageText($phoneNumber, $message);
    //         }
    //     } elseif (preg_match('/^تسجيل الخروج من \{.*\}$/u', $message)) {
    //         preg_match('/\{(.*?)\}/', $message, $matches); 
    //         $value_inside_braces = $matches[1] ?? null;
    //         $whatsapp->sendMessageText($phoneNumber, $value_inside_braces);

    // $userSession = DB::table(UsersSessions::$tableName)
    //     ->join(
    //         DevicesSessions::$tableName,
    //         DevicesSessions::$tableName . '.' . DevicesSessions::$id,
    //         '=',
    //         UsersSessions::$tableName . '.' . UsersSessions::$deviceSessionId
    //     )
    //     ->where(Users::$tableName . '.' . Users::$id, '=', $user->id)
    //     ->where(DevicesSessions::$tableName . '.' . DevicesSessions::$appId, '=', $value_inside_braces)
    //     ->first(
    //         [
    //             UsersSessions::$tableName . '.' . UsersSessions::$id
    //         ]
    //     );

    //         if ($userSession != null) {
    //             $whatsapp->sendMessageText($phoneNumber, $userSession->id."MOO");
    //             return response()->json(['success' => true]);
    //         } else {
    //             $whatsapp->sendMessageText($phoneNumber, "ثمة خطأ");
    //         }
    //     }


    //     // exit;
    //     return response()->json(['success' => true]);

    //     // Log the entire incoming request payload
    //     // Log::info('WhatsApp Webhook Payload:', $request->all());

    //     // // Process the webhook data
    //     // $payload = $request->all();

    //     // if (isset($payload['entry'][0]['changes'][0]['value']['messages'][0])) {
    //     //     $message = $payload['entry'][0]['changes'][0]['value']['messages'][0];
    //     //     $from = $message['from']; // Sender's phone number
    //     //     $text = $message['text']['body']; // Message content

    //     //     // Log the message details
    //     //     Log::info("Received message from $from: $text");

    //     //     // You can also save the message to the database or trigger other actions here
    //     // }

    //     // // Return a 200 OK response to acknowledge receipt of the webhook
    //     // return response()->json(['success' => true]);
    // }

    function generateRandomPassword($length = 8)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
        $charactersLength = strlen($characters);
        $randomPassword = '';
        for ($i = 0; $i < $length; $i++) {
            $randomPassword .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomPassword;
    }
    public function processPoints($storeId, $points = 1)
    {
        $subdcription = DB::table(StoreSubscriptions::$tableName)
            ->where(StoreSubscriptions::$tableName . '.' . StoreSubscriptions::$storeId, '=', $storeId)
            ->first();

        if ($subdcription == null) {
            throw new CustomException("This Store Not Have Subscription", 0, 442);
        }

        if ($points > $subdcription->points) {
            throw new CustomException("ليس لديك رصيد نقاط كافي للقراءة", 25, 442);
        }

        DB::table(StoreSubscriptions::$tableName)
            ->where(StoreSubscriptions::$tableName . '.' . StoreSubscriptions::$storeId, '=', $storeId)
            ->update([
                StoreSubscriptions::$points => DB::raw(StoreSubscriptions::$points . "- $points"),
                StoreSubscriptions::$updatedAt => Carbon::now()->format('Y-m-d H:i:s')
            ]);
    }
    // function checkProcess($processName, $deviceId, $userId)
    // {

    //     // print_r("dffdfrfrgfr");
    //     $myProcess = DB::table(table: MyProcesses::$tableName)
    //         ->where(MyProcesses::$tableName . '.' . MyProcesses::$name, '=', $processName)
    //         ->first();
    //     if ($myProcess == null) {
    //         // print_r("dffdf");
    //         $res = new MyResponse(false, "Process not in log", 422, 0);
    //         return $res;
    //     }
    //     $now = Carbon::now();

    //     $failProcesses = DB::table(table: FailProcesses::$tableName)
    //         ->where(FailProcesses::$tableName . '.' . FailProcesses::$deviceId, '=', $deviceId)
    //         ->whereBetween(FailProcesses::$tableName . '.' . FailProcesses::$createdAt, [Carbon::now()->subMinutes(5), Carbon::now()])
    //         ->when($userId != null, function ($query) use ($userId) {
    //             return $query->where(FailProcesses::$tableName . '.' . FailProcesses::$userId, '=', $userId);
    //         })
    //         ->get([FailProcesses::$tableName . '.' . FailProcesses::$id]);

    //     // print_r($failProcesses);
    //     if (count($failProcesses) >= $myProcess->countFail5m) {
    //         $res = new MyResponse(false, "Blocked", 302, 0);
    //         return $res;
    //     }
    //     $res = new MyResponse(true, $myProcess, 200, 0);
    //     return $res;
    // }

    function getDistance($lat1, $lon1, $lat2, $lon2)
    {
        $radius = 6371; // Earth's radius in kilometers

        // Calculate the differences in latitude and longitude
        $delta_lat = $lat2 - $lat1;
        $delta_lon = $lon2 - $lon1;

        // Calculate the central angles between the two points
        $alpha = $delta_lat / 2;
        $beta = $delta_lon / 2;

        // Use the Haversine formula to calculate the distance
        $a = sin(deg2rad($alpha)) * sin(deg2rad($alpha)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin(deg2rad($beta)) * sin(deg2rad($beta));
        $c = asin(min(1, sqrt($a)));
        $distance = 2 * $radius * $c;

        // Round the distance to four decimal places
        $distance = round($distance, 4);

        return $distance;
    }
    function checkProcessV1($processName, $deviceId, $userId)
    {
        $myProcess = DB::table(table: MyProcesses::$tableName)
            ->where(MyProcesses::$tableName . '.' . MyProcesses::$name, '=', $processName)
            ->first();
        if ($myProcess == null) {
            throw new CustomException("Process not in log", 0, 442);
        }

        $failProcesses = DB::table(table: FailProcesses::$tableName)
            ->where(FailProcesses::$tableName . '.' . FailProcesses::$deviceId, '=', $deviceId)
            ->whereBetween(FailProcesses::$tableName . '.' . FailProcesses::$createdAt, [Carbon::now()->subMinutes(5)->toDateTimeString(), Carbon::now()->toDateTimeString()])
            ->when($userId != null, function ($query) use ($userId) {
                return $query->where(FailProcesses::$tableName . '.' . FailProcesses::$userId, '=', $userId);
            })
            ->get([FailProcesses::$tableName . '.' . FailProcesses::$id]);

        if (count($failProcesses) >= $myProcess->countFail5m) {
            throw new CustomException("Blocked", 0, 442);
        }
        return $myProcess;
    }
    function checkProcess($processConfig, $deviceId, $userId)
    {
        $myProcess = DB::table(table: MyProcesses::$tableName)
            ->where(MyProcesses::$tableName . '.' . MyProcesses::$name, '=', $processConfig->processName)
            ->first();
        if ($myProcess == null) {
            throw new CustomException("Process not in log", 0, 442);
        }

        $failProcesses = DB::table(table: FailProcesses::$tableName)
            ->where(FailProcesses::$tableName . '.' . FailProcesses::$deviceId, '=', $deviceId)
            ->whereBetween(FailProcesses::$tableName . '.' . FailProcesses::$createdAt, [Carbon::now()->subMinutes($processConfig->minute)->toDateTimeString(), Carbon::now()->toDateTimeString()])
            ->when($userId != null, function ($query) use ($userId) {
                return $query->where(FailProcesses::$tableName . '.' . FailProcesses::$userId, '=', $userId);
            })
            ->get([FailProcesses::$tableName . '.' . FailProcesses::$id]);

        if (count($failProcesses) >= $$processConfig->countFail) {
            throw new CustomException("Blocked", 0, 442);
        }
        return $myProcess;
    }
    public function validRequestV1($request, $rule)
    {
        $validator = Validator::make($request->all(), $rule);

        // Check if validation fails
        if ($validator->fails()) {
            $message = 'Validation failed';
            $errors = $validator->errors()->all();
            throw new CustomException($message, 0, 442, $errors);
        }
    }
    public function getLanguage($request)
    {
        $language = $request->input('language');
        if ($language == null) {
            return 'en';
        }
        // 
        $data = DB::table(Languages::$tableName)
            ->where(Languages::$code, '=', $language)
            ->first();
        if ($data == null) {
            return 'en';
        }
        return $language;
    }
    public function getMyApp(Request $request, $appId = null)
    {
        $sha = $request->input('sha');
        $packageName = $request->input('packageName');

        if ($sha == null) {
            $sha = 001;
        }
        if ($packageName == null) {
            $packageName = 002;
        }
        // dump($request);
        // print_r($packageName);

        // 
        $app = DB::table(Apps::$tableName)
            ->where(Apps::$sha, '=', $sha)
            ->where(Apps::$packageName, '=', $packageName)
            ->first([
                Apps::$tableName . '.' . Apps::$id,
                Apps::$tableName . '.' . Apps::$packageName
            ]);
        if ($app == null) {
            throw new CustomException("Error App", 0, 443);
        }
        if ($appId != null && $appId != $app->id) {
            throw new CustomException("Error App2", 0, 443);
        }
        return $app;
    }
    public function getMyStore(Request $request, $userId)
    {
        $this->validRequestV1($request, [
            'storeId' => 'required|string|max:9'
        ]);


        $storeId = $request->input('storeId');
        // print_r($storeId);
        $store = DB::table(Stores::$tableName)
            ->when($userId != null, function ($query) use ($userId) {
                return $query->where(Stores::$userId, '=', $userId);
            })

            ->where(Stores::$id, $storeId)
            ->first();
        // print_r($store);

        if ($userId != null && $store == null) {
            throw new CustomException("This Store not for you", 0, 443);
        }

        $storeConfigs = DB::table(table: SharedStoresConfigs::$tableName)
            ->where(SharedStoresConfigs::$tableName . '.' . SharedStoresConfigs::$storeId, $store->id)
            ->get();


        if ($store->typeId == 1) {
            foreach ($storeConfigs as $storeConfig) {
                if ($storeConfig->storeId == $store->id) {
                    $categories = json_decode($storeConfig->categories);
                    $sections = json_decode($storeConfig->sections);
                    $nestedSections = json_decode($storeConfig->nestedSections);
                    $products = json_decode($storeConfig->products);
                    // $stores[$index] = (array)$stores[$index];
                    $store->storeConfig = ['storeIdReference' => $storeConfig->storeIdReference, 'categories' => $categories, 'sections' => $sections, 'nestedSections' => $nestedSections, 'products' => $products];
                }
            }
        } else {
            $store->storeConfig = null;
        }

        return $store;
    }

    function checkConfig($request)
    {

        $this->validRequestV1($request, [
            'remoteConfigVersion' => 'required|string|max:100',
        ]);

        $remoteConfigVersion = $request->input('remoteConfigVersion');

        $config = DB::table(Configuration::$tableName)
            ->first([
                Configuration::$tableName . '.' . Configuration::$remoteConfigVersion
            ]);
        if ($config->remoteConfigVersion != $remoteConfigVersion) {
            throw new CustomException("Remote Config You Must Update To Latest", 3000, 442);
        }
    }

    function getAppStore($request, $appId)
    {
        $this->validRequestV1($request, [
            'storeId' => 'required|string|max:100',
        ]);
        $storeId = $request->input('storeId');

        $appStore = DB::table(AppStores::$tableName)
            ->where(AppStores::$appId, '=', $appId)
            ->where(AppStores::$storeId, '=', $storeId)
            ->first([
                AppStores::$tableName . '.' . AppStores::$storeId
            ]);

        if ($appStore == null) {
            throw new CustomException("not related store", 0, 442);
        }
        return $appStore;
    }
    public function getMyData($request, $appId = null, $withStore = true, $storePoints = null, $withUser = true, $myProcessName = null, )
    {
        $app = $this->getMyApp($request, $appId);
        $this->checkConfig($request);

        $accessToken = null;
        if ($withUser === true) {
            $accessToken = (new LoginController($app->id))->readAccessToken($request);
        }

        $store = null;
        if ($withStore === true) {
            $store = $this->getMyStore($request, $accessToken->userId);
            if ($storePoints != null) {
                $this->processPoints($store->id, $storePoints);
            }
        }
        $myProcess = null;
        if ($myProcessName != null) {
            if ($withUser === true) {
                $myProcess = $this->checkProcessV1($myProcessName, $accessToken->deviceId, $accessToken->userId);
            } else {
                $this->validRequestV1($request, [
                    'deviceId' => 'required|string|max:100',
                ]);
                $deviceId = $request->input('deviceId');
                $myProcess = $this->checkProcessV1($myProcessName, $deviceId, null);
            }
        }
        return ['app' => $app, 'accessToken' => $accessToken, 'store' => $store, 'myProcess' => $myProcess];
    }

    public function checkIfProductInStore($productId, $storeId)
    {
        $data = DB::table(table: Products::$tableName)
            ->where(Products::$tableName . '.' . Products::$storeId, '=', $storeId)
            ->where(Products::$tableName . '.' . Products::$id, '=', $productId)
            ->first(
                [Products::$tableName . '.' . Products::$id]
            );

        if ($data == null) {
            throw new CustomException("Not have permission to update this product ", 0, 403);
        }
    }

    public function getMyOrder($request, $storeId)
    {
        $this->validRequestV1($request, [
            'orderId' => 'required|string|max:100',
        ]);

        $orderId = $request->input('orderId');

        $data = DB::table(table: Orders::$tableName)
            ->where(Orders::$tableName . '.' . Orders::$storeId, '=', $storeId)
            ->where(Orders::$tableName . '.' . Orders::$id, '=', $orderId)
            ->first(

            );

        if ($data == null) {
            throw new CustomException("Not have permission to Controll this order ", 0, 403);
        }
        return $data;
    }

}