<?php
namespace App\Http\Controllers\Api\StoreManager;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Controller;
use App\Models\Currencies;
use App\Models\Options;
use App\Models\OrdersAmounts;
use App\Models\OrdersDelivery;
use App\Models\OrdersProducts;
use App\Models\ProductImages;
use App\Models\Products;
use App\Models\Stores;
use App\Models\SharedStoresConfigs;
use App\Models\StoreProducts;
use App\Traits\AllShared;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class StoreManagerControllerUpdate extends Controller
{
    private $appId = 1;

    use AllShared;

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
        $productId = $request->input('productId');
        $productName = $request->input('productName');

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
        DB::table(table: Stores::$tableName)
            ->where(Stores::$id, '=', $storeId)
            ->update(
                [
                    Stores::$latLng => $latLng,
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
        $orderId = $request->input('orderId');
        $deliveryManId = $request->input('deliveryManId');
        DB::table(table: OrdersDelivery::$tableName)
            ->where(OrdersDelivery::$orderId, '=', $orderId)
            ->update(
                [
                    OrdersDelivery::$deliveryManId => $deliveryManId,
                ]
            );

        return response()->json($this->getOurOrderDelivery($request));
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


    public function updateOrderProductQuantity(Request $request)
    {


        return DB::transaction(function () use ($request) {

            $id = $request->input('id');
            $qnt = $request->input('qnt');

            $orderProduct = DB::table(table: OrdersProducts::$tableName)
                ->where(OrdersProducts::$id, '=', $id)
                ->sole();

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
        $validator = Validator::make($request->all(), [
            'storeId' => 'required|string|max:2',
            'accessToken' => 'required|string|max:255',
            'deviceId' => 'required|string|max:255',
            // 'logo' => 'required|image|max:200',
            // 'name' => 'required|string|max:100',
            // 'typeId' => 'required|string|max:1',
            // 'cover' => 'required|image|max:200',
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







        return DB::transaction(function () use ($request, $accessToken) {

            $storeId = $request->input('storeId');
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
                Stores::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                Stores::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ];

            $logoName = Str::random(10) . '_' . time() . '.jpg';
            if ($logo != null) {
                $updatedData[Stores::$logo] = $logoName;
            }

            $coverName = Str::random(10) . '_' . time() . '.jpg';

            if ($cover != null) {
                $updatedData[Stores::$cover] = $cover;
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

                $updatedData['storeConfig'] = null;


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
        return $this->ourLogout($request, $this->appId);
    }
}