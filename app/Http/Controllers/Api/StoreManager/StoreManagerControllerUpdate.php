<?php
namespace App\Http\Controllers\Api\StoreManager;

use App\Http\Controllers\Controller;
use App\Models\Options;
use App\Models\ProductImages;
use App\Models\Products;
use App\Models\SharedStoresConfigs;
use App\Models\StoreProducts;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class StoreManagerControllerUpdate extends Controller
{
    private $appId = 1;

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
}