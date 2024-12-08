<?php
namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\Categories1;
use App\Models\Categories3;
use App\Models\CsPsSCR;
use App\Models\Options;
use App\Models\Post;
use App\Models\ProductImages;
use App\Models\Products;
use App\Models\Sections;
use App\Models\SectionsStoreCategory;
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
        $storeCategories = DB::table(Categories1::$tableName)

            ->where(
                Categories1::$tableName . '.' . Categories1::$storeId,
                '=',
                $storeId
            )
            ->select(
                Categories1::$tableName . '.' . Categories1::$id,
                Categories1::$tableName . '.' . Categories1::$name,
                Categories1::$tableName . '.' . Categories1::$storeId,
            )
            ->get()->toArray();
        // 
        $storeCategoriesIds = [];
        foreach ($storeCategories as $storeCategory) {
            $storeCategoriesIds[] = $storeCategory->id;
        }
        $storeCategoriesSections = DB::table(SectionsStoreCategory::$tableName)
            ->whereIn(
                SectionsStoreCategory::$tableName . '.' . SectionsStoreCategory::$storeCategory1Id,
                $storeCategoriesIds

            )
            ->join(
                Sections::$tableName,
                Sections::$tableName . '.' . Sections::$id,
                '=',
                SectionsStoreCategory::$tableName . '.' . SectionsStoreCategory::$sectionId
            )
            ->select(
                SectionsStoreCategory::$tableName . '.' . SectionsStoreCategory::$id . ' as id',
                SectionsStoreCategory::$tableName . '.' . SectionsStoreCategory::$storeCategory1Id . ' as storeCategoryId',
                Sections::$tableName . '.' . Sections::$name . ' as sectionName',
                Sections::$tableName . '.' . Sections::$id . ' as sectionId',
            )
            ->get()->toArray();

        $storeCategoriesSectionsIds = [];
        foreach ($storeCategoriesSections as $storeCategorySection) {
            $storeCategoriesSectionsIds[] = $storeCategorySection->id;
        }

        $csps = DB::table(CsPsSCR::$tableName)
            ->join(
                Categories3::$tableName,
                Categories3::$tableName . '.' . Categories3::$id,
                '=',
                CsPsSCR::$tableName . '.' . CsPsSCR::$category3Id
            )

            ->whereIn(CsPsSCR::$tableName . '.' . CsPsSCR::$sectionsStoreCategoryId, $storeCategoriesSectionsIds)
            ->select(
                CsPsSCR::$tableName . '.' . CsPsSCR::$id . ' as id',
                CsPsSCR::$tableName . '.' . CsPsSCR::$sectionsStoreCategoryId . ' as storeCategorySectionId',
                CsPsSCR::$tableName . '.' . CsPsSCR::$category3Id . ' as category3Id',
                Categories3::$tableName . '.' . Categories3::$name . ' as name',
            )
            ->get();

        return response()->json(['storeCategories' => $storeCategories, 'storeCategoriesSections' => $storeCategoriesSections, 'csps' => $csps]);
        // return new JsonResponse([
        //     'data' => 88888
        // ]);

        // return Post::all();
    }

    public function getProducts(Request $request)
    {
        $CsPsSCRId = $request->input('CsPsSCRId');
        $storeId = 1;

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
                StoreProducts::$tableName . '.' . StoreProducts::$CsPsSCRId
            )
            ->join(
                Categories::$tableName,
                Categories::$tableName . '.' . Categories::$id,
                '=',
                StoreCategories::$tableName . '.' . StoreCategories::$categoryId
            )
            ->where(StoreProducts::$tableName . '.' . StoreProducts::$storeId, '=', $storeId)
            ->where(StoreProducts::$tableName . '.' . StoreProducts::$CsPsSCRId, '=', $CsPsSCRId)
            ->select(
                StoreProducts::$tableName . '.' . StoreProducts::$id . ' as storeProductId',
                Products::$tableName . '.' . Products::$id . ' as productId',
                Products::$tableName . '.' . Products::$name . ' as productName',
                Products::$tableName . '.' . Products::$description . ' as productDescription',
                StoreProducts::$tableName . '.' . StoreProducts::$price . ' as price',
                    // 
                Options::$tableName . '.' . Options::$id . ' as optionId',
                Options::$tableName . '.' . Options::$name . ' as optionName',

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


            // Add the option to the options array
            $result[$product->productId]['options'][] = ['storeProductId' => $product->storeProductId, 'name' => $product->optionName, 'price' => $product->price];
        }
        $value = ['products' => array_values($result)];
        array_push($final, $value);
        return response()->json($final);
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
