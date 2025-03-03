<?php
namespace App\Http\Controllers\Api\StoreManager;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Controller;
use App\Models\Apps;
use App\Models\AppStores;
use App\Models\Currencies;
use App\Models\CustomPrices;
use App\Models\GooglePurchases;
use App\Models\InAppProducts;
use App\Models\Options;
use App\Models\Orders;
use App\Models\OrdersAmounts;
use App\Models\OrdersDelivery;
use App\Models\OrdersProducts;
use App\Models\OrderStatus;
use App\Models\ProductImages;
use App\Models\Products;
use App\Models\Situations;
use App\Models\StoreAds;
use App\Models\Stores;
use App\Models\SharedStoresConfigs;
use App\Models\StoreProducts;
use App\Models\StoresTime;
use App\Models\StoreSubscriptions;
use App\Traits\AllShared;
use App\Traits\StoreManagerControllerShared;
use Carbon\Carbon;
use Exception;

use Google\Service\AndroidPublisher;
use Hash;
use Illuminate\Database\CustomException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class StoreManagerControllerUpdate extends Controller
{
    // private $appId = 1;

    use StoreManagerControllerShared;

    public function updateProductImage(Request $request)
    {
        if ($request->hasFile('image')) {


            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpg|max:80', // If you're uploading a file
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }




            return DB::transaction(function () use ($request) {
                $image = $request->file('image');
                if ($image->isValid() == false) {
                    return response()->json(['error' => 'Invalid image file.'], 400);
                }
                $id = $request->input('id');
                $previousRecord = DB::table(ProductImages::$tableName)
                    ->where(ProductImages::$id, '=', $id)
                    ->first();

                $fileName = Str::random(10) . '_' . time() . '.' . $image->getClientOriginalExtension();
                DB::table(ProductImages::$tableName)
                    ->where(ProductImages::$id, '=', $id)
                    ->update(
                        [ProductImages::$image => $fileName]
                    );

                try {
                    $path = Storage::disk('s3')->put('products/' . $fileName, fopen($image, 'r+'));

                    // Check if the file was uploaded successfully
                    if ($path) {

                        Storage::disk('s3')->url($fileName);

                        $updatedRecord = DB::table(ProductImages::$tableName)
                            ->where(ProductImages::$id, '=', $id)
                            ->first();
                        Storage::disk('s3')->delete('products/' . $previousRecord->image);
                        return response()->json($updatedRecord);

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
                // $path = Storage::disk('s3')->put('products/' . $fileName, fopen($image, 'r+'));


            });

            // print_r($fileName);

            // print_r("2");
            // print_r($fileName);
            // // Upload the file to S3
            // print_r("3");


            // print_r("5");


            // ->json(
            //     $url
            //     // [
            //     // 'message' => 'Image uploaded successfully',
            //     // 'url' => $url
            // // ], 
            // ,

            // 200);

            // Set up S3 client
            // $s3Client = new S3Client([
            //     'region' => env('AWS_DEFAULT_REGION'),
            //     'version' => 'latest',
            //     'credentials' => [
            //         'key' => env('AWS_ACCESS_KEY_ID'),
            //         'secret' => env('AWS_SECRET_ACCESS_KEY'),
            //     ],
            // ]);

            // // Prepare the S3 upload parameters
            // $bucket = env('AWS_BUCKET');
            // print_r("buket " . $bucket);
            // $fileName = "mustafa.jpg";
            // $expires = '+10 minutes'; // Expiry time for the URL

            // try {
            //     $command = $s3Client->getCommand('PutObject', [
            //         'Bucket' => $bucket,
            //         'Key' => $fileName, // File name in S3
            //         'ContentType' => 'image/jpeg', // Set the content type for the file
            //     ]);

            //     // Create a pre-signed URL with expiry time
            //     $request = $s3Client->createPresignedRequest($command, $expires);

            //     // Get the pre-signed URL as a string
            //     $url = (string) $request->getUri();

            //     // Return the pre-signed URL to the client
            //     return response()->json(['url' => $url]);
            // } catch (\Aws\Exception\AwsException $e) {
            //     Log::error('Error generating pre-signed URL', ['error' => $e->getMessage()]);
            //     return response()->json(['error' => 'Unable to generate pre-signed URL'], 500);
            // }



            // print_r();
        } else {
            return response()->json(['error' => 'Image Not Found'], 400);
            // print_r("no");
            // print_r($request->all());
        }

        // return response()->json(['error' => 'Image upload failed'], 400);
    }
    public function updateProductName(Request $request)
    {


        $this->validRequestV1($request, [
            'productId' => 'required|string|max:11',
            'productName' => 'required|string|max:30'
        ]);

        $productId = $request->input('productId');
        $productName = $request->input('productName');

        ///
        $myData = $this->getMyData(request: $request, appId: $this->appId);
        $store = $myData['store'];
        $this->checkIfProductInStore($productId, $store->id);
        ///
        DB::table(table: Products::$tableName)
            ->where(Products::$id, '=', $productId)
            ->update(
                [Products::$name => $productName]
            );

        return response()->json(['result' => $productName]);
    }
    public function updateProductDescription(Request $request)
    {
        $productId = $request->input('productId');
        $description = $request->input('description');
        ///
        $myData = $this->getMyData(request: $request, appId: $this->appId);
        $store = $myData['store'];
        $this->checkIfProductInStore($productId, $store->id);
        ///

        DB::table(table: Products::$tableName)
            ->where(Products::$id, '=', $productId)
            ->update(
                [Products::$description => $description]
            );

        return response()->json(['result' => $description]);
    }
    public function updateProductOptionName(Request $request)
    {
        $storeProductId = $request->input('storeProductId');
        $optionId = $request->input('optionId');



        $option = DB::table(table: Options::$tableName)
            ->where(Options::$id, '=', $optionId)->sole();


        DB::table(table: StoreProducts::$tableName)
            ->where(StoreProducts::$id, '=', $storeProductId)
            ->update(
                [StoreProducts::$optionId => $optionId]
            );
        return response()->json(['result' => $option->name]);
    }
    public function updateProductOptionPrice(Request $request)
    {
        $storeProductId = $request->input('storeProductId');
        $price = $request->input('price');

        DB::table(table: StoreProducts::$tableName)
            ->where(StoreProducts::$id, '=', $storeProductId)
            ->update(
                [StoreProducts::$price => $price]
            );

        return response()->json(['result' => $price]);
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
    public function updateStoreLocation(Request $request)
    {
        $storeId = $request->input('storeId');
        $latLng = $request->input('latLng');
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        DB::table(table: Stores::$tableName)
            ->where(Stores::$id, '=', $storeId)
            ->update(
                [
                    Stores::$latLng => $latLng,
                    Stores::$latLong => DB::raw("ST_GeomFromText('POINT($latitude $longitude)', 4326)"),
                ]
            );
        $store = DB::table(table: Stores::$tableName)
            ->where(Stores::$id, '=', $storeId)
            ->first(
                [Stores::$id]
            );
        return response()->json($store);
    }
    public function updateOrderDeliveryMan(Request $request)
    {
        $this->validRequestV1($request, [
            'deliveryManId' => 'required|string|max:100',
        ]);
        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: true, storePoints: 2);
        $store = $myData['store'];
        $myOrder = $this->getMyOrder($request, $store->id);
        if ($myOrder->situationId == Situations::$CENCELED || $myOrder->situationId == Situations::$COMPLETED) {
            throw new CustomException("الطلب تم انجازه", 0, 403);
        }
        return DB::transaction(function () use ($request, $myOrder) {



            $orderDelivery = DB::table(table: OrdersDelivery::$tableName)
                ->where(OrdersDelivery::$orderId, '=', $myOrder->id)
                ->first();

            if ($orderDelivery == null) {
                throw new CustomException("Order not have Delivery", 0, 403);
            }



            DB::table(table: Orders::$tableName)
                ->where(Orders::$id, '=', $myOrder->id)
                ->update(
                    [
                        Orders::$situationId => Situations::$ASSIGN_DELIVERY_MAN,
                        Orders::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                    ]
                );
            DB::table(OrderStatus::$tableName)
                ->insert([
                    OrderStatus::$id => null,
                    OrderStatus::$orderId => $myOrder->id,
                    OrderStatus::$situationId => Situations::$ASSIGN_DELIVERY_MAN,
                    OrderStatus::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                ]);


            // $orderId = $request->input('orderId');
            $deliveryManId = $request->input('deliveryManId');
            DB::table(table: OrdersDelivery::$tableName)
                ->where(OrdersDelivery::$orderId, '=', $myOrder->id)
                ->update(
                    [
                        OrdersDelivery::$deliveryManId => $deliveryManId,
                    ]
                );

            return response()->json($this->getOurOrderDelivery($request));

        });


    }
    public function cancelOrder(Request $request)
    {
        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: true, storePoints: 2);
        $store = $myData['store'];
        $myOrder = $this->getMyOrder($request, $store->id);

        return DB::transaction(function () use ($request, $myOrder) {
            if ($myOrder->situationId == Situations::$CENCELED || $myOrder->situationId == Situations::$COMPLETED) {
                throw new CustomException("الطلب تم انجازه", 0, 403);
            }

            DB::table(table: Orders::$tableName)
                ->where(Orders::$id, '=', $myOrder->id)
                ->update(
                    [
                        Orders::$situationId => Situations::$CENCELED,
                        Orders::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                    ]
                );
            DB::table(OrderStatus::$tableName)
                ->insert([
                    OrderStatus::$id => null,
                    OrderStatus::$orderId => $myOrder->id,
                    OrderStatus::$situationId => Situations::$CENCELED,
                    OrderStatus::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                ]);
            return response()->json([]);
        });


    }
    public function updateProductView(Request $request)
    {
        $storeId = $request->input('storeId');
        $productId = $request->input('productId');
        $productViewId = $request->input('productViewId');

        DB::table(table: StoreProducts::$tableName)
            ->where(StoreProducts::$storeId, '=', $storeId)
            ->where(StoreProducts::$productId, '=', $productId)

            ->update(
                [
                    StoreProducts::$productViewId => $productViewId,
                ]
            );

        return response()->json([]);
    }
    public function updateStoreProductOrder(Request $request)
    {
        $storeProductId = $request->input('storeProductId');
        $orderNo = $request->input('orderNo');

        DB::table(table: StoreProducts::$tableName)
            ->where(StoreProducts::$id, '=', $storeProductId)
            ->update(
                [
                    StoreProducts::$orderNo => $orderNo,
                    StoreProducts::$orderAt => Carbon::now()->format('Y-m-d H:i:s'),
                ]
            );

        return response()->json([]);
    }
    public function updateProductOrder(Request $request)
    {
        $productId = $request->input('productId');
        $orderNo = $request->input('orderNo');

        DB::table(table: Products::$tableName)
            ->where(Products::$id, '=', $productId)
            ->update(
                [
                    Products::$orderNo => $orderNo,
                    Products::$orderAt => Carbon::now()->format('Y-m-d H:i:s'),
                ]
            );

        return response()->json([]);
    }

    public function updateCurrency(Request $request)
    {
        $storeProductId = $request->input('storeProductId');
        $currencyId = $request->input('currencyId');

        DB::table(table: StoreProducts::$tableName)
            ->where(StoreProducts::$id, '=', $storeProductId)
            ->update(
                [
                    StoreProducts::$currencyId => $currencyId,
                    StoreProducts::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                ]
            );
        $data = DB::table(Currencies::$tableName)
            ->where(Currencies::$id, '=', $currencyId)
            ->first();

        return response()->json($data);
    }
    public function updateCustomPrice(Request $request)
    {
        $storeId = $request->input('storeId');

        $storeProductId = $request->input('storeProductId');
        $price = $request->input('price');

        DB::table(table: CustomPrices::$tableName)
            ->where(CustomPrices::$storeProductId, '=', $storeProductId)
            ->where(CustomPrices::$storeId, '=', $storeId)

            ->update(
                [
                    CustomPrices::$price => $price,
                    CustomPrices::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                ]
            );
        return response()->json([]);
    }


    public function updateOrderProductQuantity(Request $request)
    {

        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: true, storePoints: 2);
        $store = $myData['store'];
        $myOrder = $this->getMyOrder($request, $store->id);
        if ($myOrder->situationId == Situations::$CENCELED || $myOrder->situationId == Situations::$COMPLETED) {
            throw new CustomException("الطلب تم انجازه", 0, 403);
        }
        return DB::transaction(function () use ($request, $myOrder) {

            $id = $request->input('id');
            $qnt = $request->input('qnt');

            $orderProduct = DB::table(table: OrdersProducts::$tableName)
                ->where(OrdersProducts::$id, '=', $id)
                ->first();
            if ($orderProduct == null) {
                throw new CustomException("Order Product Not found", 0, 400);
            }
            if ($orderProduct->orderId != $myOrder->id) {
                throw new CustomException("Not Have Permission to controll this order", 0, 400);
            }

            DB::table(table: OrdersProducts::$tableName)
                ->where(OrdersProducts::$id, '=', $id)
                ->update(
                    [
                        OrdersProducts::$productQuantity => $qnt,
                    ]
                );

            $diff = $orderProduct->productQuantity - $qnt;
            $update = [];

            if ($orderProduct->productQuantity == $qnt) {
                return response()->json($orderProduct);
            } elseif ($qnt > $orderProduct->productQuantity) {
                $newAmount = ($qnt - $orderProduct->productQuantity) * $orderProduct->productPrice;
                // Increase the amount
                $update[OrdersAmounts::$amount] = DB::raw(OrdersAmounts::$amount . " + ($newAmount)");
            } else {
                $newAmount = ($orderProduct->productQuantity - $qnt) * $orderProduct->productPrice;
                // Decrease the amount
                $update[OrdersAmounts::$amount] = DB::raw(OrdersAmounts::$amount . " - ($newAmount)");
            }

            DB::table(table: OrdersAmounts::$tableName)
                ->where(OrdersAmounts::$orderId, '=', $orderProduct->orderId)
                ->where(OrdersAmounts::$currencyId, '=', $orderProduct->currencyId)
                ->update($update);



            $data = DB::table(table: OrdersProducts::$tableName)
                ->where(OrdersProducts::$tableName . '.' . OrdersProducts::$id, '=', $id)
                ->join(
                    Currencies::$tableName,
                    Currencies::$tableName . '.' . Currencies::$id,
                    '=',
                    OrdersProducts::$tableName . '.' . OrdersProducts::$currencyId
                )
                ->sole(
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
            return response()->json($data);
        });


    }

    public function updateStore(Request $request)
    {


        return DB::transaction(function () use ($request) {

            $myData = $this->getMyData(request: $request, appId: $this->appId);
            $accessToken = $myData['accessToken'];
            $store = $myData['app'];

            $this->validRequestV1($request, [
                'name' => 'required|string|max:9',
                'logo' => 'required|image|mimes:jpg|max:300'
            ]);

            $storeId = $store->id;
            $name = $request->input('name');
            $typeId = $request->input('typeId');
            $logo = $request->file('logo');
            $cover = $request->file('cover');

            // if ($logo->isValid() == false) {
            //     return response()->json(['error' => 'Invalid Logo file.'], 400);
            // }

            // if ($cover->isValid() == false) {
            //     return response()->json(['error' => 'Invalid Cover file.'], 400);
            // }
            $updatedData = [
                    // Stores::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                Stores::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ];

            $logoName = Str::random(10) . '_' . time() . '.jpg';
            if ($logo != null) {
                $updatedData[Stores::$logo] = $logoName;
            }

            $coverName = Str::random(10) . '_' . time() . '.jpg';

            if ($cover != null) {
                $updatedData[Stores::$cover] = $coverName;
            }

            if ($name != null && strlen($name) > 0) {
                $updatedData[Stores::$name] = $name;
            }

            if (count($updatedData) == 2) {
                return response()->json(['message' => "Cant update empty values", 'errors' => [], 'code' => 0], 400);
            }

            $previousRecord = DB::table(Stores::$tableName)
                ->where(Stores::$id, '=', $storeId)
                ->sole();

            DB::table(table: Stores::$tableName)
                ->where(Stores::$id, '=', $storeId)
                ->update(
                    $updatedData
                    //     [
                    //     Stores::$name => $name,
                    //     Stores::$logo => $logoName,
                    //     Stores::$cover => $coverName,
                    //     Stores::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                    //     Stores::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                    // ]

                );

            try {
                if ($logo != null) {
                    Storage::disk('s3')->delete('stores/logos/' . $previousRecord->logo);
                    $pathLogo = Storage::disk('s3')->put('stores/logos/' . $logoName, fopen($logo, 'r+'));
                    if ($pathLogo == false) {
                        DB::rollBack();
                        return $this->responseError2('No valid Logo uploaded.', [], 0, 400);
                    }
                }
                if ($cover != null) {
                    Storage::disk('s3')->delete('stores/covers/' . $previousRecord->cover);
                    $pathCover = Storage::disk('s3')->put('stores/covers/' . $coverName, fopen($cover, 'r+'));
                    if ($pathCover == false) {
                        DB::rollBack();
                        return $this->responseError2('No valid Caver uploaded.', [], 0, 400);
                    }
                }
                $updatedRecord = DB::table(Stores::$tableName)
                    ->where(Stores::$id, '=', $storeId)
                    ->first();

                $updatedRecord->storeConfig = null;


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

    public function logout(Request $request)
    {
        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: false);
        $accessToken = $myData['accessToken'];
        return $this->ourLogout($accessToken->userSessionId);
    }
    public function updateAds(Request $request)
    {

        $this->validRequestV1($request, [
            'adsId' => 'required|string|max:11'
        ]);
        $adsId = $request->input('adsId');
        ///
        $myData = $this->getMyData(request: $request, appId: $this->appId, storePoints: 2);
        $store = $myData['store'];

        ///
        $ads = DB::table(table: StoreAds::$tableName)
            ->where(StoreAds::$id, '=', $adsId)->first();

        if ($ads == null) {
            throw new CustomException("not found", 0, 403);
        }
        if ($ads->storeId != $store->id) {
            throw new CustomException("need permission", 0, 403);
        }

        $days = 1;
        $expireAt = Carbon::now();

        if ($days > 1) {
            $expireAt = $expireAt->addDays($days - 1)->endOfDay();
        }

        $expireAt->addDays($days - 1)->endOfDay();

        $expireAt = $expireAt->format('Y-m-d H:i:s');
        DB::table(table: StoreAds::$tableName)
            ->where(StoreAds::$id, '=', $adsId)
            ->update(
                [StoreAds::$expireAt => $expireAt]
            );

        return response()->json(['result' => $expireAt]);
    }


    public function updatePoints(Request $request)
    {
        $this->validRequestV1($request, [
            'productId' => 'required|string|max:50',
            'purchaseToken' => 'required|string|max:200|min:120',
        ]);

        $myData = $this->getMyData(request: $request, appId: $this->appId, withStore: true, storePoints: 2, );
        $store = $myData['store'];
        $app = $myData['app'];


        return DB::transaction(function () use ($request, $store, $app) {
            $productId = $request->input('productId');
            $purchaseToken = $request->input('purchaseToken');
            // throw new CustomException("Undefiend ProductId" . $productId, 0, 403);

            $inAppProduct = DB::table(InAppProducts::$tableName)
                ->where(InAppProducts::$productId, '=', $productId)
                ->first();
            if ($inAppProduct == null) {
                throw new CustomException("Undefiend ProductId" . $productId, 0, 403);
            }

            $googlePurchase = DB::table(GooglePurchases::$tableName)
                ->where(GooglePurchases::$purchaseToken, '=', $purchaseToken)
                ->first();
            if ($googlePurchase == null) {
                $insertedId = DB::table(table: GooglePurchases::$tableName)
                    ->insertGetId([
                        GooglePurchases::$id => null,
                        GooglePurchases::$storeId => $store->id,
                        GooglePurchases::$purchaseToken => $purchaseToken,
                        GooglePurchases::$isPending => 0,
                        GooglePurchases::$isAck => 0,
                        GooglePurchases::$isCounsumed => 0,
                        GooglePurchases::$isSubs => $inAppProduct->isSubs,
                        GooglePurchases::$productId => $productId,
                        GooglePurchases::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                        GooglePurchases::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
                $googlePurchase = DB::table(GooglePurchases::$tableName)
                    ->where(GooglePurchases::$id, '=', $insertedId)
                    ->first();
            } else {
                if ($googlePurchase->isGet == 1) {
                    throw new CustomException("تم الشراء مسبقا", 0, 403);
                } elseif ($googlePurchase->isPending == 1) {
                    throw new CustomException("عملية شراء تم الغاءها", 0, 403);
                }
            }
            $inAppProduct = $this->processPurchase($app, $store, $googlePurchase, $inAppProduct, $purchaseToken);
            return response()->json($inAppProduct);

        });




        // $inAppProduct = DB::table(InAppProducts::$tableName)
        //     ->where(InAppProducts::$productId, '=', $productIds[0])
        //     ->first();

        // $points = $inAppProduct->points;

        // DB::table(table: StoreSubscriptions::$tableName)
        //     ->where(StoreSubscriptions::$storeId, '=', $store->id)
        //     ->update(
        //         [StoreSubscriptions::$points => DB::raw(StoreSubscriptions::$points . " + ($points)")]
        //     );

        // $sub = DB::table(StoreSubscriptions::$tableName)
        //     ->where(StoreSubscriptions::$storeId, '=', $store->id)
        //     ->first();
        // return response()->json($sub);
    }

    public function updateStoreDeliveryPrice(Request $request)
    {

        $this->validRequestV1($request, [
            'deliveryPriceCurrency' => 'required|string|max:2',
            'price' => 'required|string|max:10'
        ]);
        $price = $request->input('price');
        $deliveryPriceCurrency = $request->input('deliveryPriceCurrency');

        ///
        $myData = $this->getMyData(request: $request, appId: $this->appId, storePoints: 2);
        $store = $myData['store'];

        DB::table(table: Stores::$tableName)
            ->where(Stores::$id, '=', $store->id)
            ->update(
                [
                    Stores::$deliveryPrice => $price,
                    Stores::$deliveryPriceCurrency => $deliveryPriceCurrency,
                ]
            );



        return response()->json([]);
    }


    public function updateStoreServiceAccount(Request $request)
    {
        $this->validRequestV1($request, [
            'jsonService' => 'required|file|mimes:json|max:50',
            'passwordService' => 'required|string|max:255'
        ]);
        $myData = $this->getMyData(request: $request, appId: $this->appId, storePoints: 2);
        $store = $myData['store'];

        $jsonFile = $request->file('jsonService');
        $passwordService = $request->input('passwordService');

        $jsonContent = file_get_contents($jsonFile->path());
        $app = DB::table(table: AppStores::$tableName)
            ->where(AppStores::$tableName . '.' . AppStores::$storeId, '=', $store->id)
            ->join(
                Apps::$tableName,
                Apps::$tableName . '.' . Apps::$id,
                '=',
                AppStores::$tableName . '.' . AppStores::$appId
            )->first();


        if ($app->password == null) {
            throw new CustomException("يتم اضافة رمز التحقق للتطبيق بعد", 0, 403);
        }
        if (Hash::check($passwordService, $app->password) == false) {
            throw new CustomException("رمز غير صحيح", 0, 403);
        }
        if ($this->isValidJson($jsonContent) == false) {
            return $this->responseError2(" الملف  خاطئ", [], 0, 405);
        }
        // $json = json_decode($jsonContent);

        // print_r($json->private_key);
        // if ($this->isValidJson($jsonContent) == false) {
        //     throw new CustomException("تنسيق الملف غير صحيح", 0, 403);
        //     // return $this->responseError2("تم تخزين الملف بشكل خاطئ", [], 0, 405);
        // }
        // print_r(($json));
        $dat = $this->encryptData($passwordService, $jsonContent);
        // $json->private_key = $dat;
        // print_r(($json));



        // print_r(strlen($dat) . " " . $dat);
        DB::table(table: Apps::$tableName)
            ->where(Apps::$id, '=', $app->id)
            ->update(
                [
                    Apps::$serviceAccount => $dat,
                ]
            );
        return response()->json([]);
        // $jsonData = json_encode($jsonContent, true);

        // throw new CustomException("Error " . $jsonContent, 0, 403);
    }
    public function updateStoreTime(Request $request)
    {
        $this->validRequestV1($request, [
            'day' => 'required|string|max:2'
        ]);
        $myData = $this->getMyData(request: $request, appId: $this->appId, storePoints: 2);
        $store = $myData['store'];

        $day = $request->input('day');
        $openAt = $request->input('openAt');
        $closeAt = $request->input('closeAt');
        $isOpen = $request->input('isOpen');


        $time = DB::table(table: StoresTime::$tableName)
            ->where(StoresTime::$tableName . '.' . StoresTime::$storeId, '=', $store->id)
            ->where(StoresTime::$tableName . '.' . StoresTime::$day, '=', $day)
            ->first();

        $data = [
            StoresTime::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
        ];
        if ($time == null) {
            $newData = [
                StoresTime::$id => null,
                StoresTime::$day => $day,
                StoresTime::$storeId => $store->id,
                StoresTime::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
            ];

            // Only add these fields if they're not null
            if ($openAt != null) {
                $newData[StoresTime::$openAt] = $openAt;
            }
            if ($closeAt != null) {
                $newData[StoresTime::$closeAt] = $closeAt;
            }
            if ($isOpen != null) {
                $newData[StoresTime::$isOpen] = $isOpen;
            }

            // Add the new row to the `$data` array
            $data = array_merge($data, $newData);
            // print_r($data);
            DB::table(StoresTime::$tableName)
                ->insert($data);

        } else {
            if ($openAt != null) {
                $data[StoresTime::$openAt] = $openAt;
            }
            if ($closeAt != null) {
                $data[StoresTime::$closeAt] = $closeAt;
            }
            if ($isOpen != null) {
                $data[StoresTime::$isOpen] = $isOpen;
            }

            if (count($data) > 1) {
                DB::table(table: StoresTime::$tableName)
                    ->where(StoresTime::$tableName . '.' . StoresTime::$storeId, '=', $store->id)
                    ->where(StoresTime::$tableName . '.' . StoresTime::$day, '=', $day)
                    ->update(
                        $data
                    );
            }

        }

        $time = DB::table(table: StoresTime::$tableName)
            ->where(StoresTime::$tableName . '.' . StoresTime::$storeId, '=', $store->id)
            ->where(StoresTime::$tableName . '.' . StoresTime::$day, '=', $day)
            ->first();

        $result = [
            "day" => $this->getDayName($day), // Get the day name (e.g., "Saturday")
            "storeTime" => $time, // Store time or null if not found
        ];

        return response()->json($result);
        // $jsonData = json_encode($jsonContent, true);

        // throw new CustomException("Error " . $jsonContent, 0, 403);
    }
}