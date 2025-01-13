<?php
namespace App\Http\Controllers\Api\StoreManager;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\DeliveryMen;
use App\Models\NestedSections;
use App\Models\Options;
use App\Models\ProductImages;
use App\Models\Products;
use App\Models\StoreDeliveryMen;
use App\Models\StoreProducts;
use App\Models\StoreSections;
use App\Models\StoreNestedSections;
use App\Models\Sections;
use App\Models\Stores;
use App\Models\StoreCategories;
use App\Models\Users;
use App\Traits\AllShared;
use App\Traits\StoreManagerControllerShared;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Storage;
use Str;
use Validator;

class StoreManagerControllerAdd extends Controller
{

    use StoreManagerControllerShared;
    use AllShared;
    public function addCategory(Request $request)
    {
        $storeId = $request->input('storeId');
        $name = $request->input('name');
        $insertedId = DB::table(table: Categories::$tableName)
            ->insertGetId([
                Categories::$id => null,
                Categories::$storeId => $storeId,
                Categories::$name => $name,
                Categories::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                Categories::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

        $category = DB::table(Categories::$tableName)
            ->where(Categories::$tableName . '.' . Categories::$id, '=', $insertedId)
            ->sole([
                Categories::$tableName . '.' . Categories::$id,
                Categories::$tableName . '.' . Categories::$name,
                Categories::$tableName . '.' . Categories::$acceptedStatus,
            ]);
        return response()->json($category);
    }
    public function addSection(Request $request)
    {
        $storeId = $request->input('storeId');
        $categoryId = $request->input('categoryId');
        $name = $request->input('name');
        $insertedId = DB::table(table: Sections::$tableName)
            ->insertGetId([
                Sections::$id => null,
                Sections::$storeId => $storeId,
                Sections::$categoryId => $categoryId,
                Sections::$name => $name,
                Sections::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                Sections::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

        $category = DB::table(Sections::$tableName)
            ->where(Sections::$tableName . '.' . Sections::$id, '=', $insertedId)
            ->sole([
                Sections::$tableName . '.' . Sections::$id,
                Sections::$tableName . '.' . Sections::$name,
                Sections::$tableName . '.' . Sections::$acceptedStatus,
            ]);
        return response()->json($category);
    }
    public function addNestedSection(Request $request)
    {
        $storeId = $request->input('storeId');
        $sectionId = $request->input('sectionId');
        $name = $request->input('name');
        $insertedId = DB::table(table: NestedSections::$tableName)
            ->insertGetId([
                NestedSections::$id => null,
                NestedSections::$storeId => $storeId,
                NestedSections::$sectionId => $sectionId,
                NestedSections::$name => $name,
                NestedSections::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                NestedSections::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

        $category = DB::table(NestedSections::$tableName)
            ->where(NestedSections::$tableName . '.' . NestedSections::$id, '=', $insertedId)
            ->sole([
                NestedSections::$tableName . '.' . NestedSections::$id,
                NestedSections::$tableName . '.' . NestedSections::$name,
                NestedSections::$tableName . '.' . NestedSections::$acceptedStatus,
            ]);
        return response()->json($category);
    }
    public function addStoreCategory(Request $request)
    {
        $storeId = $request->input('storeId');
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
    public function addStoreSection(Request $request)
    {
        $storeId = $request->input('storeId');
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
                    Sections::$tableName . '.' . Sections::$name . ' as sectionName',
                    StoreSections::$tableName . '.' . StoreSections::$storeCategoryId . ' as storeCategoryId'

                ]
            );

        return response()->json($storeCategory);
    }
    public function addProduct(Request $request)
    {
        $storeId = $request->input('storeId');
        $nestedSectionId = $request->input('nestedSectionId');
        $name = $request->input('name');
        $description = $request->input('description');

        $insertedId = DB::table(table: Products::$tableName)
            ->insertGetId([
                Products::$id => null,
                Products::$nestedSectionId => $nestedSectionId,
                Products::$name => $name,
                Products::$storeId => $storeId,
                Products::$description => $description,
                Products::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                Products::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        $product = DB::table(table: Products::$tableName)->where(Products::$tableName . '.' . Products::$id, '=', $insertedId)
            ->first(
                [
                    Products::$tableName . '.' . Products::$id,
                    Products::$tableName . '.' . Products::$name,
                    Products::$tableName . '.' . Products::$description,
                    Products::$tableName . '.' . Products::$acceptedStatus,
                ]
            );

        return response()->json($product);
    }
    public function addStoreNestedSection(Request $request)
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
            ->first(
                [
                    StoreNestedSections::$tableName . '.' . StoreNestedSections::$id . ' as id',
                    StoreNestedSections::$tableName . '.' . StoreNestedSections::$storeSectionId . ' as storeSectionId',
                    NestedSections::$tableName . '.' . NestedSections::$id . ' as nestedSectionId',
                    NestedSections::$tableName . '.' . NestedSections::$name . ' as nestedSectionName',
                ]
            );

        return response()->json($storeCategory);
    }

    public function addProductOption(Request $request)
    {
        $productId = $request->input('productId');
        $optionId = $request->input('optionId');
        $price = $request->input('price');
        $storeNestedSectionId = $request->input(key: 'storeNestedSectionId');
        $getWithProduct = $request->input(key: 'getWithProduct');
        $storeId = $request->input('storeId');

        try {
            $insertedId = DB::table(table: StoreProducts::$tableName)
                ->insertGetId([
                    StoreProducts::$id => null,
                    StoreProducts::$optionId => $optionId,
                    StoreProducts::$productId => $productId,
                    StoreProducts::$price => $price,
                    StoreProducts::$storeNestedSectionId => $storeNestedSectionId,
                    StoreProducts::$storeId => $storeId,
                    StoreProducts::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                    StoreProducts::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                ]);

            $productOption = null;
            if ($getWithProduct == 1) {
                $product = DB::table(StoreProducts::$tableName)
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
                    ->where(StoreProducts::$tableName . '.' . StoreProducts::$id, '=', $insertedId)
                    ->select(
                        StoreProducts::$tableName . '.' . StoreProducts::$id . ' as storeProductId',
                        StoreProducts::$tableName . '.' . StoreProducts::$storeNestedSectionId . ' as storeNestedSectionId',
                        Products::$tableName . '.' . Products::$id . ' as productId',
                        Products::$tableName . '.' . Products::$name . ' as productName',
                        Products::$tableName . '.' . Products::$description . ' as productDescription',
                        StoreProducts::$tableName . '.' . StoreProducts::$price . ' as price',
                            // 
                        Options::$tableName . '.' . Options::$id . ' as optionId',
                        Options::$tableName . '.' . Options::$name . ' as optionName',
                        // 

                        // Categories::$tableName . '.' . Categories::$id . ' as categoryId',
                        // Categories::$tableName . '.' . Categories::$name . ' as categoryName',

                    )
                    ->first();

                $images = DB::table(table: ProductImages::$tableName)->where(ProductImages::$tableName . '.' . ProductImages::$productId, '=', $product->productId)
                    ->get([
                        ProductImages::$tableName . '.' . ProductImages::$id,
                        ProductImages::$tableName . '.' . ProductImages::$image
                    ]);

                $result = [
                    'productId' => $product->productId,
                    'storeNestedSectionId' => $product->storeNestedSectionId,
                    'productName' => $product->productName,
                    'productDescription' => $product->productDescription,
                    'options' => [['optionId' => $product->optionId, 'storeProductId' => $product->storeProductId, 'name' => $product->optionName, 'price' => $product->price]],
                    'images' => $images
                ];
                return response()->json($result);

            } else {
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
        } catch (QueryException $e) {
            return $this->queryEX($e);
        }




    }

    public function addStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'accessToken' => 'required|string|max:255',
            'deviceId' => 'required|string|max:255',
            'logo' => 'required|image|mimes:jpg|max:80',
            'name' => 'required|string|max:100',
            'typeId' => 'required|string|max:1',
            'cover' => 'required|image|mimes:jpg|max:100',
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

            $name = $request->input('name');
            $typeId = $request->input('typeId');
            $logo = $request->file('logo');
            $cover = $request->file('cover');

            if ($logo->isValid() == false) {
                return response()->json(['error' => 'Invalid Logo file.'], 400);
            }

            if ($cover->isValid() == false) {
                return response()->json(['error' => 'Invalid Cover file.'], 400);
            }

            $logoName = Str::random(10) . '_' . time() . '.jpg';
            $coverName = Str::random(10) . '_' . time() . '.jpg';

            $insertedId = DB::table(table: Stores::$tableName)
                ->insertGetId([
                    Stores::$id => null,
                    Stores::$name => $name,
                    Stores::$userId => $accessToken->userId,
                    Stores::$typeId => $typeId,
                    Stores::$logo => $logoName,
                    Stores::$cover => $coverName,
                    Stores::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                    Stores::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                ]);

            try {
                $pathLogo = Storage::disk('s3')->put('stores/logos/' . $logoName, fopen($logo, 'r+'));
                $pathCover = Storage::disk('s3')->put('stores/covers/' . $coverName, fopen($cover, 'r+'));

                // Check if the file was uploaded successfully
                if ($pathLogo && $pathCover) {
                    $addedRecord = DB::table(Stores::$tableName)
                        ->where(Stores::$id, '=', $insertedId)
                        ->first();

                    $addedRecord->storeConfig = null;

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
    }
    public function addDeliveryManToStore(Request $request)
    {
        $validation = $this->validRequest($request, [
            'phone' => 'required|string|max:9',
            'storeId' => 'required|string|max:9'
        ]);


        if ($validation != null) {
            return $this->responseError($validation);
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

            $phone = $request->input('phone');
            $storeId = $request->input('storeId');

            $deliveryMan = DB::table(table: DeliveryMen::$tableName)
                ->join(
                    Users::$tableName,
                    Users::$tableName . '.' . Users::$id,
                    '=',
                    DeliveryMen::$tableName . '.' . DeliveryMen::$userId
                )
                ->where(Users::$tableName . '.' . Users::$phone, '=', $phone)
                ->sole(
                    [
                        DeliveryMen::$tableName . '.' . DeliveryMen::$id,
                            // Users::$tableName . '.' . Users::$id . 'as userId',
                        Users::$tableName . '.' . Users::$firstName,
                        Users::$tableName . '.' . Users::$lastName,
                        Users::$tableName . '.' . Users::$phone,
                    ]
                );
            try {

                $insertedId = DB::table(table: StoreDeliveryMen::$tableName)
                    ->insertGetId([
                        StoreDeliveryMen::$id => null,
                        StoreDeliveryMen::$storeId => $storeId,
                        StoreDeliveryMen::$deliveryManId => $deliveryMan->id,
                        StoreDeliveryMen::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                        StoreDeliveryMen::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);

                return response()->json($deliveryMan);
            } catch (QueryException $e) {
                  // Manually trigger a rollback
                return $this->queryEX($e);
            }




        });
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
}
