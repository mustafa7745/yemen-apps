<?php
namespace App\Http\Controllers\Api\StoreManager;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\NestedSections;
use App\Models\OrdersAmounts;
use App\Models\OrdersProducts;
use App\Models\ProductImages;
use App\Models\Products;
use App\Models\Stores;
use App\Models\Sections;
use App\Models\StoreCategories;
use App\Models\StoreNestedSections;
use App\Models\StoreProducts;
use App\Models\StoreSections;
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
                return response()->json(['message' => "لا يمكن الحذف حدث خطأ", 'code' => 0], 409);

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
    public function deleteNestedSections(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $ids = $request->input('ids');
            $ids = json_decode($ids);

            // check if have store products related
            $count = DB::table(StoreNestedSections::$tableName)
                ->whereIn(StoreNestedSections::$nestedSectionId, $ids)
                ->count();

            if ($count > 0) {
                return response()->json(['message' => "لايمكنك الحذف في حال وجود نفس المنتجات  في المتجر", 'code' => 0], 409);
            }


            $countDeleted = DB::table(NestedSections::$tableName)
                ->whereIn(NestedSections::$id, $ids)
                ->delete();
            if ($countDeleted != count($ids)) {
                return response()->json(['message' => "لا يمكن الحذف حدث خطأ", 'code' => 0], 409);
            }
            return response()->json(["success" => "yes"]);

        });
    }
    public function deleteStoreNestedSections(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $ids = $request->input('ids');
            $ids = json_decode($ids);

            // check if have store products related
            $count = DB::table(StoreProducts::$tableName)
                ->whereIn(StoreProducts::$storeNestedSectionId, $ids)
                ->count();

            if ($count > 0) {
                return response()->json(['message' => "لايمكنك الحذف في حال وجود نفس المنتجات  في المتجر", 'code' => 0], 409);
            }

            $countDeleted = DB::table(StoreNestedSections::$tableName)
                ->whereIn(StoreNestedSections::$id, $ids)
                ->delete();
            if ($countDeleted != count($ids)) {
                return response()->json(['message' => "لا يمكن الحذف حدث خطأ", 'code' => 0], 409);
            }
            return response()->json(["success" => "yes"]);

        });
    }
    // 
    public function deleteSections(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $ids = $request->input('ids');
            $ids = json_decode($ids);

            // check if have store products related
            $count = DB::table(StoreSections::$tableName)
                ->whereIn(StoreSections::$sectionId, $ids)
                ->count();

            if ($count > 0) {
                return response()->json(['message' => "لايمكنك الحذف في حال وجود نفس المنتجات  في المتجر", 'code' => 0], 409);
            }


            $countDeleted = DB::table(Sections::$tableName)
                ->whereIn(Sections::$id, $ids)
                ->delete();
            if ($countDeleted != count($ids)) {
                return response()->json(['message' => "لا يمكن الحذف حدث خطأ", 'code' => 0], 409);
            }
            return response()->json(["success" => "yes"]);

        });
    }
    public function deleteStoreSections(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $ids = $request->input('ids');
            $ids = json_decode($ids);

            // check if have store products related
            $count = DB::table(StoreNestedSections::$tableName)
                ->whereIn(StoreNestedSections::$storeSectionId, $ids)
                ->count();

            if ($count > 0) {
                return response()->json(['message' => "لايمكنك الحذف في حال وجود نفس المنتجات  في المتجر", 'code' => 0], 409);
            }

            $countDeleted = DB::table(StoreSections::$tableName)
                ->whereIn(StoreSections::$id, $ids)
                ->delete();
            if ($countDeleted != count($ids)) {
                return response()->json(['message' => "لا يمكن الحذف حدث خطأ", 'code' => 0], 409);
            }
            return response()->json(["success" => "yes"]);

        });
    }
    public function deleteCategories(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $ids = $request->input('ids');
            $ids = json_decode($ids);

            // check if have store products related
            $count = DB::table(StoreCategories::$tableName)
                ->whereIn(StoreCategories::$categoryId, $ids)
                ->count();

            if ($count > 0) {
                return response()->json(['message' => "لايمكنك الحذف في حال وجود نفس المنتجات  في المتجر", 'code' => 0], 409);
            }


            $countDeleted = DB::table(Categories::$tableName)
                ->whereIn(Categories::$id, $ids)
                ->delete();
            if ($countDeleted != count($ids)) {
                return response()->json(['message' => "لا يمكن الحذف حدث خطأ", 'code' => 0], 409);
            }
            return response()->json(["success" => "yes"]);

        });
    }
    public function deleteStoreCategories(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $ids = $request->input('ids');
            $ids = json_decode($ids);

            // check if have store products related
            $count = DB::table(StoreSections::$tableName)
                ->whereIn(StoreSections::$storeCategoryId, $ids)
                ->count();

            if ($count > 0) {
                return response()->json(['message' => "لايمكنك الحذف في حال وجود نفس المنتجات  في المتجر", 'code' => 0], 409);
            }

            $countDeleted = DB::table(StoreCategories::$tableName)
                ->whereIn(StoreCategories::$id, $ids)
                ->delete();
            if ($countDeleted != count($ids)) {
                return response()->json(['message' => "لا يمكن الحذف حدث خطأ", 'code' => 0], 409);
            }
            return response()->json(["success" => "yes"]);

        });
    }
    public function deleteStores(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $ids = $request->input('ids');
            $ids = json_decode($ids);

            // check if have store products related
            $count = DB::table(StoreCategories::$tableName)
                ->whereIn(StoreCategories::$storeId, $ids)
                ->count();

            if ($count > 0) {
                return response()->json(['message' => "لايمكنك الحذف في حال وجود نفس المنتجات  في المتجر", 'code' => 0], 409);
            }

            $countDeleted = DB::table(Stores::$tableName)
                ->whereIn(Stores::$id, $ids)
                ->delete();
            if ($countDeleted != count($ids)) {
                return response()->json(['message' => "لا يمكن الحذف حدث خطأ", 'code' => 0], 409);
            }
            return response()->json(["success" => "yes"]);

        });
    }

    public function deleteOrderProducts(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $ids = $request->input('ids');
            $ids = json_decode($ids);

            $orderProducts = DB::table(table: OrdersProducts::$tableName)
                ->whereIn(OrdersProducts::$id, $ids)
                ->get();

            $orderIds = [];

            foreach ($orderProducts as $key => $value) {
                $orderIds[] = $value->orderId;
            }

            $orderAmounts = DB::table(table: OrdersProducts::$tableName)
                ->whereIn(OrdersProducts::$orderId, $orderIds)
                ->get();


            print_r($orderAmounts);
            foreach ($orderProducts as $key => $orderProduct) {
                $amount = $orderProduct->productPrice * $orderProduct->productQuantity;
                foreach ($orderAmounts as $key2 => $orderAmount) {
                    # code...
                    if ($orderProduct->orderId == $orderAmount->orderId && $orderProduct->currencyId == $orderAmount->currencyId) {
                        $orderAmounts[$key2]->amount -= $amount;
                        break;
                    }
                }
            }

            foreach ($orderAmounts as $key2 => $orderAmount) {
                DB::table(table: OrdersAmounts::$tableName)
                    ->where(OrdersAmounts::$orderId, '=', $orderProduct->orderId)
                    ->where(OrdersAmounts::$currencyId, '=', $orderProduct->currencyId)
                    ->update([
                        OrdersAmounts::$amount => $orderAmount->amount,
                    ]);
            }

            $countDeleted = DB::table(OrdersProducts::$tableName)
                ->whereIn(OrdersProducts::$id, $ids)
                ->delete();
            if ($countDeleted != count($ids)) {
                return response()->json(['message' => "لا يمكن الحذف حدث خطأ", 'code' => 0], 409);
            }
            return response()->json(["success" => "yes"]);

        });
    }
}