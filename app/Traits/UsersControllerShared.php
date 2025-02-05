<?php
namespace App\Traits;
use App\Models\Apps;
use DB;
use Illuminate\Http\Request;

trait UsersControllerShared
{
    // public function getMyApp(Request $request)
    // {
    //     $sha = $request->input('sha');
    //     $packageName = $request->input('packageName');

    //     // 
    //     $app = DB::table(Apps::$tableName)
    //         ->where(Apps::$sha, $sha)
    //         ->where(Apps::$packageName, $packageName)
    //         ->sole([
    //             Apps::$tableName . '.' . Apps::$id 
    //         ]);
    //      return $app;
    // }
}