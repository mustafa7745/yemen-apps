<?php
namespace App\Http\Controllers\Api\StoreManager;

use App\Http\Controllers\Controller;
use App\Models\Categories1;
use App\Models\StoreCategories1;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StoreManagerController2 extends Controller
{
    public function getCategories()
    {
        $storeId = 1;
        $categories = DB::table(Categories1::$tableName)
            ->get([
                Categories1::$tableName . '.' . Categories1::$id,
                Categories1::$tableName . '.' . Categories1::$name
            ])->toArray();
        return response()->json($categories);
    }
    public function getStoreCategories()
    {
        $storeId = 1;
        $storeCategories = DB::table(table: StoreCategories1::$tableName)
        ->where(StoreCategories1::$tableName . '.' . StoreCategories1::$storeId, '=', $storeId)
            ->join(
                Categories1::$tableName,
                Categories1::$tableName . '.' . Categories1::$id,
                '=',
                StoreCategories1::$tableName . '.' . StoreCategories1::$categoryId1
            )
            ->get(
                [
                    StoreCategories1::$tableName . '.' . StoreCategories1::$id . ' as id',
                    Categories1::$tableName . '.' . Categories1::$id . ' as categoryId',
                    Categories1::$tableName . '.' . Categories1::$name . ' as categoryName'
                ]
            );

        return response()->json($storeCategories);
    }
    public function addStoreCategory(Request $request)
    {
        $storeId = 1;
        $categoryId = $request->input('categoryId');
        $insertedId = DB::table(table: StoreCategories1::$tableName)
            ->insertGetId([
                StoreCategories1::$id => null,
                StoreCategories1::$categoryId1 => $categoryId,
                StoreCategories1::$storeId => $storeId,
                StoreCategories1::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                StoreCategories1::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        $storeCategory = DB::table(table: StoreCategories1::$tableName)->where(StoreCategories1::$tableName . '.' . StoreCategories1::$id, '=', $insertedId)
            ->join(
                Categories1::$tableName,
                Categories1::$tableName . '.' . Categories1::$id,
                '=',
                StoreCategories1::$tableName . '.' . StoreCategories1::$categoryId1
            )
            ->first(
                [
                    StoreCategories1::$tableName . '.' . StoreCategories1::$id . ' as id',
                    Categories1::$tableName . '.' . Categories1::$id . ' as categoryId',
                    Categories1::$tableName . '.' . Categories1::$name . ' as categoryName'
                ]
            );

        return response()->json($storeCategory);
    }
}
