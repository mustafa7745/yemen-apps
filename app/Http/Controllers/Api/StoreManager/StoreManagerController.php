<?php
namespace App\Http\Controllers\Api\StoreManager;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Controller;
use App\Models\Apps;
use App\Models\Categories;
use App\Models\Devices;
use App\Models\DevicesSessions;
use App\Models\Options;
use App\Models\Post;
use App\Models\ProductImages;
use App\Models\Products;
use App\Models\StoreCategories;
use App\Models\StoreProducts;
use App\Models\Users;
use App\Models\UsersSessions;
use Dotenv\Exception\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class StoreManagerController extends Controller
{
    private $appId = 1;
    public function readMain(Request $request)
    {
        // Define validation rules
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

        $storeId = 1;
        $categories = DB::table(StoreCategories::$tableName)
            ->join(
                Categories::$tableName,
                Categories::$tableName . '.' . Categories::$id,
                '=',
                StoreCategories::$tableName . '.' . StoreCategories::$categoryId
            )
            ->where(
                StoreCategories::$tableName . '.' . StoreCategories::$storeId,
                '=',
                $storeId
            )
            ->select(
                StoreCategories::$tableName . '.' . StoreCategories::$id . ' as storeCategoryId',
                Categories::$tableName . '.' . Categories::$id . ' as categoryId',
                Categories::$tableName . '.' . Categories::$name . ' as categoryName'
            )
            ->get()->toArray();
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
            ->join(
                StoreCategories::$tableName,
                StoreCategories::$tableName . '.' . StoreCategories::$id,
                '=',
                StoreProducts::$tableName . '.' . StoreProducts::$storeCategoryId
            )
            ->join(
                Categories::$tableName,
                Categories::$tableName . '.' . Categories::$id,
                '=',
                StoreCategories::$tableName . '.' . StoreCategories::$categoryId
            )
            ->where(StoreProducts::$tableName . '.' . StoreProducts::$storeId, '=', $storeId)
            ->select(
                StoreProducts::$tableName . '.' . StoreProducts::$id . ' as storeProductId',
                StoreProducts::$tableName . '.' . StoreProducts::$storeCategoryId . ' as storeCategoryId',
                Products::$tableName . '.' . Products::$id . ' as productId',
                Products::$tableName . '.' . Products::$name . ' as productName',
                Products::$tableName . '.' . Products::$description . ' as productDescription',
                StoreProducts::$tableName . '.' . StoreProducts::$price . ' as price',
                    // 
                Options::$tableName . '.' . Options::$id . ' as optionId',
                Options::$tableName . '.' . Options::$name . ' as optionName',
                    // 

                Categories::$tableName . '.' . Categories::$id . ' as categoryId',
                Categories::$tableName . '.' . Categories::$name . ' as categoryName',

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

        foreach ($categories as $category) {

            $result = [];

            foreach ($storeProducts as $product) {

                if (!isset($result[$product->productId]) && $product->categoryId == $category->categoryId) {
                    $result[$product->productId] = [
                        'storeProductId' => $product->storeProductId,
                        'storeCategoryId' => $product->storeCategoryId,
                        'productId' => $product->productId,
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



                if ($product->categoryId == $category->categoryId)
                    // Add the option to the options array
                    $result[$product->productId]['options'][] = ['optionId' => $product->optionId, 'storeProductId' => $product->storeProductId, 'name' => $product->optionName, 'price' => $product->price];



            }
            $value = ['storeCategory' => $category, 'storeProducts' => array_values($result)];
            array_push($final, $value);
        }

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
        return response()->json($final);
        // return new JsonResponse([
        //     'data' => 88888
        // ]);

        // return Post::all();
    }
    // public function getStoreCategories()
    // {
    //     $storeId = 1;
    //     $categories = DB::table(StoreCategories::$tableName)
    //         ->join(
    //             Categories::$tableName,
    //             Categories::$tableName . '.' . Categories::$id,
    //             '=',
    //             StoreCategories::$tableName . '.' . StoreCategories::$categoryId
    //         )
    //         ->where(
    //             StoreCategories::$tableName . '.' . StoreCategories::$storeId,
    //             '=',
    //             $storeId
    //         )
    //         ->select(
    //             StoreCategories::$tableName . '.' . StoreCategories::$id . ' as storeCategoryId',
    //             Categories::$tableName . '.' . Categories::$id . ' as categoryId',
    //             Categories::$tableName . '.' . Categories::$name . ' as categoryName'
    //         )
    //         ->get()->toArray();
    //     return response()->json($categories);
    // }

    public function getMyCategories()
    {
        $storeId = 1;
        $categories = DB::table(Categories::$tableName)
            ->where(Categories::$tableName . '.' . Categories::$storeId, '=', $storeId)
            ->get()->toArray();
        return response()->json($categories);
    }
    public function getMyProducts()
    {
        $storeId = 1;
        $products = DB::table(Products::$tableName)
            ->where(Products::$tableName . '.' . Products::$storeId, '=', $storeId)
            ->get()->toArray();

        $productIds = [];
        foreach ($products as $product) {
            $productIds[] = $product->id;
        }
        $productImages = DB::table(ProductImages::$tableName)
            ->whereIn(ProductImages::$productId, $productIds)
            ->select(
                ProductImages::$tableName . '.' . ProductImages::$id,

                ProductImages::$tableName . '.' . ProductImages::$productId,
                ProductImages::$tableName . '.' . ProductImages::$image,
            )
            ->get();

        // Initialize the final result array
        $final = [];

        foreach ($products as $product) {
            $images = []; // Initialize an empty images array for each product

            foreach ($productImages as $index => $image) {
                // Match the image with the product based on product ID
                if ($image->productId == $product->id) {
                    $images[] = ['image' => $image->image, 'id' => $image->id];
                    unset($productImages[$index]); // Remove matched image
                }
            }

            // Add the images to the final array using the product's ID as the key
            $imagesWithit = ['images' => $images];
            $final[$product->id] = array_merge((array) $product, $imagesWithit);
        }

        return response()->json(array_values($final));
    }
    public function getCategories()
    {
        $storeId = 1;
        $categories = DB::table(Categories::$tableName)
            ->whereIn(Categories::$tableName . '.' . Categories::$storeId, [$storeId, 1])
            ->get([
                Categories::$tableName . '.' . Categories::$id,
                Categories::$tableName . '.' . Categories::$name
            ])->toArray();
        return response()->json($categories);
    }
    public function getProducts()
    {
        $storeId = 1;
        $products = DB::table(Products::$tableName)
            ->whereIn(Products::$tableName . '.' . Products::$storeId, [$storeId, 1])
            ->get(
                [
                    Products::$tableName . '.' . Products::$id,
                    Products::$tableName . '.' . Products::$name
                ]
            )->toArray();

        return response()->json(array_values($products));
    }
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
    public function addProductImage(Request $request)
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
    public function deleteProductImage(Request $request)
    {

        return DB::transaction(function () use ($request) {
            $id = $request->input('id');
            $previousRecord = DB::table(ProductImages::$tableName)
                ->where(ProductImages::$id, '=', $id)
                ->first();
            Storage::disk('s3')->delete('products/' . $previousRecord->image);
            DB::table(ProductImages::$tableName)
                ->where(ProductImages::$id, '=', $id)
                ->delete();
            return response()->json(["success" => "yes"]);
        });
    }

    // 
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

    public function addProductOption(Request $request)
    {
        $productId = $request->input('productId');
        $optionId = $request->input('optionId');
        $price = $request->input('price');
        $storeCategoryId = $request->input('storeCategoryId');


        $insertedId = DB::table(table: StoreProducts::$tableName)
            ->insertGetId([
                StoreProducts::$id => null,
                StoreProducts::$optionId => $optionId,
                StoreProducts::$productId => $productId,
                StoreProducts::$price => $price,
                StoreProducts::$storeCategoryId => $storeCategoryId,
                StoreProducts::$storeId => 1,
                StoreProducts::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                StoreProducts::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
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
            'price' => $productOption->price
        ]);
    }
    public function deleteProductOptions(Request $request)
    {

        return DB::transaction(function () use ($request) {
            $ids = $request->input('ids');
            $ids = json_decode($ids);
            $count = DB::table(table: StoreProducts::$tableName)
                ->whereIn(StoreProducts::$id, $ids)
                ->delete();
            // print_r($ids);
            // print_r($count);
            // print_r(DB::table(ProductImages::$tableName)
            // ->whereIn(ProductImages::$id, $ids)
            // ->toSql());
            if ($count > 0) {
                return response()->json(["success" => "yes"]);
            } else {
                return response()->json("Error Remove", 400);
            }



        });
    }
    public function readOptions()
    {
        $options = DB::table(table: Options::$tableName)->get();
        return response()->json($options);
    }
    public function readStoreCategories()
    {


        $result = DB::table(table: StoreCategories::$tableName)
            ->where(StoreCategories::$tableName . '.' . StoreCategories::$storeId, '=', 1)
            ->join(
                Categories::$tableName,
                Categories::$tableName . '.' . Categories::$id,
                '=',
                StoreCategories::$tableName . '.' . StoreCategories::$categoryId
            )
            ->get([
                StoreCategories::$tableName . '.' . StoreCategories::$id . ' as storeCategoryId',
                Categories::$tableName . '.' . Categories::$id . ' as categoryId',
                Categories::$tableName . '.' . Categories::$name . ' as categoryName',
            ]);
        return response()->json($result);
    }

    // 
    public function addMyCategory(Request $request)
    {
        $name = $request->input('name');

        $insertedId = DB::table(table: Categories::$tableName)
            ->insertGetId([
                Categories::$id => null,
                Categories::$name => $name,
                Categories::$storeId => 1,
                Categories::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                Categories::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        $category = DB::table(table: Categories::$tableName)->where(Categories::$tableName . '.' . Categories::$id, '=', $insertedId)
            ->first(
            );

        return response()->json($category);
    }
    public function addMyProduct(Request $request)
    {
        $name = $request->input('name');
        $categoryId = $request->input('categoryId');

        $insertedId = DB::table(table: Products::$tableName)
            ->insertGetId([
                Products::$id => null,
                Products::$name => $name,
                Products::$categoryId => $categoryId,
                Products::$storeId => 1,
                Products::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                Products::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        $product = DB::table(table: Products::$tableName)->where(Products::$tableName . '.' . Products::$id, '=', $insertedId)
            ->first(
            );
        $product->images = [];

        return response()->json($product);
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
