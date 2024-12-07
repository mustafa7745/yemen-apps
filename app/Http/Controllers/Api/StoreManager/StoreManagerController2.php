<?php
namespace App\Http\Controllers\Api\StoreManager;

use App\Http\Controllers\Controller;
use App\Models\Categories1;
use App\Models\Categories3;
use App\Models\CsPsSCR;
use App\Models\Sections;
use App\Models\SectionsStoreCategory;
use App\Models\StoreCategories1;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StoreManagerController2 extends Controller
{
    public function getCategories(Request $request)
    {
    
        $storeId = 1;
        $categories = DB::table(Categories1::$tableName)
        
            ->get([
                Categories1::$tableName . '.' . Categories1::$id,
                Categories1::$tableName . '.' . Categories1::$name
            ])->toArray();
        return response()->json($categories);
    }
    public function getStoreCategories(Request $request)
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
    // 
    public function getSections(Request $request)
    {
        $category1Id = $request->input('category1Id');
        $storeId = 1;
        $categories = DB::table(Sections::$tableName)
        ->where(Sections::$tableName . '.' . Sections::$category1Id, '=', $category1Id)
            ->get([
                Sections::$tableName . '.' . Sections::$id,
                Sections::$tableName . '.' . Sections::$name
            ])->toArray();
        return response()->json($categories);
    }
    public function getSecionsStoreCategories(Request $request)
    {
        // $storeId = 1;
        $storeCategory1Id = $request->input('storeCategory1Id');
        $storeCategories = DB::table(table: SectionsStoreCategory::$tableName)
            ->where(SectionsStoreCategory::$tableName . '.' . SectionsStoreCategory::$storeCategory1Id, '=', $storeCategory1Id)
            ->join(
                Sections::$tableName,
                Sections::$tableName . '.' . Sections::$id,
                '=',
                SectionsStoreCategory::$tableName . '.' . SectionsStoreCategory::$sectionId
            )
            ->get(
                [
                    SectionsStoreCategory::$tableName . '.' . SectionsStoreCategory::$id . ' as id',
                    Sections::$tableName . '.' . Sections::$id . ' as sectionId',
                    Sections::$tableName . '.' . Sections::$name . ' as sectionName'
                ]
            );

        return response()->json($storeCategories);
    }
    public function addSectionStoreCategory(Request $request)
    {
        $storeId = 1;
        $storeCategory1Id = $request->input('storeCategory1Id');
        $sectionId = $request->input('sectionId');

        $insertedId = DB::table(table: SectionsStoreCategory::$tableName)
            ->insertGetId([
                SectionsStoreCategory::$id => null,
                SectionsStoreCategory::$sectionId => $sectionId,
                SectionsStoreCategory::$storeCategory1Id => $storeCategory1Id,
                SectionsStoreCategory::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                SectionsStoreCategory::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        $storeCategory = DB::table(table: SectionsStoreCategory::$tableName)->where(SectionsStoreCategory::$tableName . '.' . SectionsStoreCategory::$id, '=', $insertedId)
            ->join(
                Sections::$tableName,
                Sections::$tableName . '.' . Sections::$id,
                '=',
                SectionsStoreCategory::$tableName . '.' . SectionsStoreCategory::$sectionId

            )
            ->first(
                [
                    SectionsStoreCategory::$tableName . '.' . SectionsStoreCategory::$id . ' as id',
                    Sections::$tableName . '.' . Sections::$id . ' as sectionId',
                    Sections::$tableName . '.' . Sections::$name . ' as sectionName'
                ]
            );

        return response()->json($storeCategory);
    }
    //
    public function getCsPsSCR(Request $request)
    {
        // $storeId = 1;
        $sectionsStoreCategoryId = $request->input('sectionsStoreCategoryId');
        $storeCategories = DB::table(table: CsPsSCR::$tableName)
            ->where(CsPsSCR::$tableName . '.' . CsPsSCR::$sectionsStoreCategoryId, '=', $sectionsStoreCategoryId)
            ->join(
                Categories3::$tableName,
                Categories3::$tableName . '.' . Categories3::$id,
                '=',
                CsPsSCR::$tableName . '.' . CsPsSCR::$category3Id
            )
            ->join(
                Sections::$tableName,
                Sections::$tableName . '.' . Sections::$id,
                '=',
                Categories3::$tableName . '.' . Categories3::$sectionId
            )
            ->get(
                [
                    CsPsSCR::$tableName . '.' . CsPsSCR::$id . ' as id',
                    Categories3::$tableName . '.' . Categories3::$id . ' as category3Id',
                    Categories3::$tableName . '.' . Categories3::$name . ' as category3Name',
                ]
            );

        return response()->json($storeCategories);
    }
    public function addCsPsSCR(Request $request)
    {
        $storeId = 1;
        $sectionsStoreCategoryId = $request->input('sectionsStoreCategoryId');
        $category3Id = $request->input('category3Id');

        $insertedId = DB::table(table: CsPsSCR::$tableName)
            ->insertGetId([
                CsPsSCR::$id => null,
                CsPsSCR::$category3Id => $category3Id,
                CsPsSCR::$sectionsStoreCategoryId => $sectionsStoreCategoryId,
                CsPsSCR::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
                CsPsSCR::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);
        $storeCategory = DB::table(table: CsPsSCR::$tableName)->where(CsPsSCR::$tableName . '.' . CsPsSCR::$id, '=', $insertedId)
            ->join(
                Categories3::$tableName,
                Categories3::$tableName . '.' . Categories3::$id,
                '=',
                CsPsSCR::$tableName . '.' . CsPsSCR::$category3Id
            )
            ->join(
                Sections::$tableName,
                Sections::$tableName . '.' . Sections::$id,
                '=',
                Categories3::$tableName . '.' . Categories3::$sectionId
            )
            ->first(
                [
                    CsPsSCR::$tableName . '.' . CsPsSCR::$id . ' as id',
                    Categories3::$tableName . '.' . Categories3::$id . ' as category3Id',
                    Categories3::$tableName . '.' . Categories3::$name . ' as category3Name',
                ]
            );

        return response()->json($storeCategory);
    }
    public function getCategories3(Request $request)
    {
        $sectionId = $request->input('sectionId');
        $categories = DB::table(Categories3::$tableName)
            ->where(Categories3::$tableName . '.' . Categories3::$sectionId, '=', $sectionId)
            ->join(
                Sections::$tableName,
                Sections::$tableName . '.' . Sections::$id,
                '=',
                Categories3::$tableName . '.' . Categories3::$sectionId
            )
            ->get([
                Categories3::$tableName . '.' . Categories3::$id,
                Categories3::$tableName . '.' . Categories3::$name
            ])->toArray();
        return response()->json($categories);
    }


}
