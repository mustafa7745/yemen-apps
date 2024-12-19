<?php
namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Controller;
use App\Models\StoreNestedSections;
use App\Models\Options;
use App\Models\Stores;
use App\Models\ProductImages;
use App\Models\Products;
use App\Models\Sections;
use App\Models\SectionsStoreCategory;
use App\Models\StoreProducts;
use App\Traits\UserControllerShared;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class UserControllerGet extends Controller
{
    use UserControllerShared;
    public function index()
    { 
        $stores = DB::table(Stores::$tableName)->get();
        return response()->json($stores);
    }
    public function getProducts(Request $request)
    {
        $StoreNestedSectionsId = $request->input('StoreNestedSectionsId');
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
                StoreProducts::$tableName . '.' . StoreProducts::$StoreNestedSectionsId
            )
            // ->join(
            //     Categories::$tableName,
            //     Categories::$tableName . '.' . Categories::$id,
            //     '=',
            //     StoreCategories::$tableName . '.' . StoreCategories::$categoryId
            // )
            ->where(StoreProducts::$tableName . '.' . StoreProducts::$storeId, '=', $storeId)
            ->where(StoreProducts::$tableName . '.' . StoreProducts::$StoreNestedSectionsId, '=', $StoreNestedSectionsId)
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
                StoreNestedSections::$tableName . '.' . StoreNestedSections::$id . ' as StoreNestedSectionsId',



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
                    'StoreNestedSectionsId' => $product->StoreNestedSectionsId,
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
     
 
        return response()->json(array_values($result));
    }
    public function getStores(Request $request)
    {
        $stores = DB::table(Stores::$tableName)->get();
        return response()->json($stores);
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
