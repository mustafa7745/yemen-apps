<?php
namespace App\Http\Controllers\Api\StoreManager;

use App\Http\Controllers\Controller;
use App\Models\ProductImages;
use App\Models\Products;
use App\Models\StoreProducts;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoreManagerControllerDelete extends Controller
{

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
    public function deleteProductOptions(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $ids = $request->input('ids');
            $ids = json_decode($ids);
            $count = DB::table(table: StoreProducts::$tableName)
                ->whereIn(StoreProducts::$id, $ids)
                ->delete();
            if ($count > 0) {
                return response()->json(["success" => "yes"]);
            } else {
                return response()->json("Error Remove", 400);
            }



        });
    }
    public function deleteProducts(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $ids = $request->input('ids');
            $ids = json_decode($ids);

            // check if have store products related
            $count = DB::table(StoreProducts::$tableName)
                ->whereIn(StoreProducts::$productId, $ids)
                ->count();

            if ($count > 0) {
                return response()->json(['message' => "لايمكنك الحذف في حال وجود نفس المنتجات  في المتجر", 'code' => 0], 409);
            }

            // check if have image products related
            $count = DB::table(ProductImages::$tableName)
                ->whereIn(ProductImages::$productId, $ids)
                ->count();

            if ($count > 0) {
                return response()->json(['message' => "لايمكنك الحذف في حال وجود صور للمنتجات ", 'code' => 0], 409);
            }

            $countDeleted = DB::table(Products::$tableName)
                ->whereIn(Products::$id, $ids)
                ->delete();
            if ($countDeleted != count($ids)) {
                return response()->json(['message' => "لا يمكن الحذف حدث خطأ", 'code' => 0], 409);
            }
            return response()->json(["success" => "yes"]);

        });
    }
}