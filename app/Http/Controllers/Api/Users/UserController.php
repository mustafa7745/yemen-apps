<?php
namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\Options;
use App\Models\Post;
use App\Models\Products;
use App\Models\StoreCategories;
use App\Models\StoreProducts;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index()
    {
        $storeId = 1;
        $categories = DB::table(StoreCategories::$tableName)
            ->where(
                StoreCategories::$tableName . '.' . StoreCategories::$storeId,
                '=',
                $storeId
            )->get()->toArray();
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
        $categoriesAndProducts = [];
        // 
        // print_r($categories);
        $final = [];
        // for ($i = 0; $i < count($categories); $i++) {
        //     print_r($categories[$i]->id);
        //     print_r($categories[$i]->name);
        // }

        foreach ($categories as $category) {
            // $products=[];
            //    $options = [];
            // for ($product = 0; $product < count($storeProducts); $product++) {

            //     # code...
            // }
            $result = [];

            foreach ($storeProducts as $product) {
                if (!isset($result[$product->productId])) {
                    $result[$product->productId] = [
                        'productId' => $product->productId,
                        'productName' => $product->productName,
                        'options' => []
                    ];
                }

                // Add the option to the options array
                $result[$product->productId]['options'][] = ['price' => $product->price, 'name' => $product->optionName];
            }
            $value = ['category' => $category, 'products' => $result];
            array_push($final, $value);
        }

        return response()->json($final);
        // return new JsonResponse([
        //     'data' => 88888
        // ]);

        // return Post::all();
    }

    // public function store(Request $request)
    // {
    //     // $post = Post::create($request->validate([
    //     //     'title' => 'required|string|max:255',
    //     //     'content' => 'required|string',
    //     // ]));
    //     // return response()->json($post, 201);
    //     return new JsonResponse([
    //         'data' => 11111
    //     ]);
    // }

    // public function show(Post $post)
    // {
    //     return new JsonResponse([
    //         'data' => 55555
    //     ]);
    //     // return $post;
    // }

    // public function update(Request $request, Post $post)
    // {
    //     $post->update($request->validate([
    //         'title' => 'sometimes|required|string|max:255',
    //         'content' => 'sometimes|required|string',
    //     ]));
    //     return response()->json($post);
    // }

    // public function destroy(Post $post)
    // {
    //     $post->delete();
    //     return response()->json(null, 204);
    // }
}
