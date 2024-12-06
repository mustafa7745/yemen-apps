<?php
namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\Options;
use App\Models\Post;
use App\Models\ProductImages;
use App\Models\Products;
use App\Models\StoreCategories;
use App\Models\StoreProducts;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    private $appId = 2;
    public function index()
    {
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
                        'productId' => $product->productId,
                        'productName' => $product->productName,
                        'productDescription' => $product->productDescription,
                        'options' => [],
                        'images' => []
                    ];
                    $images = [];
                    foreach ($productImages as $index => $image) {
                        if ($image->productId == $product->productId) {
                            $images[] = ['image' => $image->image];
                            unset($productImages[$index]);
                        }
                    }
                    $result[$product->productId]['images'] = $images;
                }



                if ($product->categoryId == $category->categoryId)
                    // Add the option to the options array
                    $result[$product->productId]['options'][] = ['storeProductId' => $product->storeProductId, 'name' => $product->optionName, 'price' => $product->price];



            }
            $value = ['category' => $category, 'products' => array_values($result)];
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
    // public function uploadImage(Request $request)
    // {
    //     if ($request->hasFile('image')) {
    //         // print_r("1");
    //         // print_r("yes hav file");
    //         // $file = $request->file('image');
    //         $image = $request->file('image');

    //         // print_r("1.1");

    //         // // // Generate a unique file name based on timestamp and original file name
    //         $fileName = 'images/' . Str::random(10) . '_' . time() . '.' . $image->getClientOriginalExtension();
    //         // print_r($fileName);

    //         // print_r("2");
    //         // print_r($fileName);
    //         // // Upload the file to S3
    //         // print_r("3");

    //         $path = Storage::disk('s3')->put($fileName, fopen($image, 'r+'));

    //         // print_r("4"); 

    //         $url = Storage::disk('s3')->url($fileName);
    //         // print_r("5");

    //         return response($url);
    //         // ->json(
    //         //     $url
    //         //     // [
    //         //     // 'message' => 'Image uploaded successfully',
    //         //     // 'url' => $url
    //         // // ], 
    //         // ,
            
    //         // 200);

    //         // Set up S3 client
    //         // $s3Client = new S3Client([
    //         //     'region' => env('AWS_DEFAULT_REGION'),
    //         //     'version' => 'latest',
    //         //     'credentials' => [
    //         //         'key' => env('AWS_ACCESS_KEY_ID'),
    //         //         'secret' => env('AWS_SECRET_ACCESS_KEY'),
    //         //     ],
    //         // ]);

    //         // // Prepare the S3 upload parameters
    //         // $bucket = env('AWS_BUCKET');
    //         // print_r("buket " . $bucket);
    //         // $fileName = "mustafa.jpg";
    //         // $expires = '+10 minutes'; // Expiry time for the URL

    //         // try {
    //         //     $command = $s3Client->getCommand('PutObject', [
    //         //         'Bucket' => $bucket,
    //         //         'Key' => $fileName, // File name in S3
    //         //         'ContentType' => 'image/jpeg', // Set the content type for the file
    //         //     ]);

    //         //     // Create a pre-signed URL with expiry time
    //         //     $request = $s3Client->createPresignedRequest($command, $expires);

    //         //     // Get the pre-signed URL as a string
    //         //     $url = (string) $request->getUri();

    //         //     // Return the pre-signed URL to the client
    //         //     return response()->json(['url' => $url]);
    //         // } catch (\Aws\Exception\AwsException $e) {
    //         //     Log::error('Error generating pre-signed URL', ['error' => $e->getMessage()]);
    //         //     return response()->json(['error' => 'Unable to generate pre-signed URL'], 500);
    //         // }



    //         // print_r();
    //     } else {
    //         print_r("no");
    //         print_r($request->all());
    //     }

    //     return response()->json(['error' => 'Image upload failed'], 400);
    // }


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
