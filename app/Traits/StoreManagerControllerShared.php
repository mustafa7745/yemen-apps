<?php
namespace App\Traits;
use App\Models\GooglePurchases;
use App\Models\Stores;
use App\Models\StoreSubscriptions;
use Carbon\Carbon;
use DB;
use Exception;
use Google\Service\AndroidPublisher;
use Illuminate\Database\CustomException;
use Google\Client;
use Illuminate\Log\Logger;

trait StoreManagerControllerShared
{
    use AllShared;
    public $appId = 1;

    public function getServiceClient()
    {
        $client = new Client();
        $client->setAuthConfig(storage_path('play/storesmanger-9dea8f2ba6b8.json'));
        $client->addScope(AndroidPublisher::ANDROIDPUBLISHER);
        $service = new AndroidPublisher($client);
        return $service;
    }
    function processPurchase($app, $store, $googlePurchase, $inAppProduct, $purchaseToken)
    {
        try {
            $service = $this->getServiceClient();
            $purchase = $service->purchases_products->get($app->packageName, $inAppProduct->productId, $purchaseToken);
            print_r($purchase);
            // print_r($purchaseToken);
            // print_r($googlePurchase->productId);
            // print_r($app->packageName);

            // Logger(json_encode($purchase));
            $updatedData = [
                Stores::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ];
            if ($purchase->purchaseState === 1) {
                if ($googlePurchase->isPending !== 0) {
                    $updatedData[GooglePurchases::$isPending] = 0;
                }
                if ($purchase->acknowledgementState !== 1) {
                    $service->purchases_products->acknowledge($app->packageName, $googlePurchase->productId, $purchaseToken);
                    $updatedData[GooglePurchases::$isAck] = 1;
                }
                if ($purchase->consumptionState !== 1) {
                    $service->purchases_products->consume($app->packageName, $inAppProduct->productId, $purchaseToken);
                    $updatedData[GooglePurchases::$isCounsumed] = 1;
                }
                // if ($purchase->acknowledgementState !== 1) {
                //     $service->purchases_products->acknowledge($app->packageName, $googlePurchase->productId, $purchaseToken);
                //     $updatedData[GooglePurchases::$isAck] = 1;
                // }
                ////
                if ($updatedData > 1) {
                    DB::table(table: GooglePurchases::$tableName)
                        ->where(GooglePurchases::$purchaseToken, '=', $purchaseToken)
                        ->update(
                            $updatedData
                        );
                }

                DB::table(table: StoreSubscriptions::$tableName)
                    ->where(StoreSubscriptions::$storeId, '=', $store->id)
                    ->update(
                        [StoreSubscriptions::$points => DB::raw(StoreSubscriptions::$points . " + ($inAppProduct->points)")]
                    );
                $inAppProduct->isPending = false;
                return $inAppProduct;
            }
            $inAppProduct->isPending = true;
            return $inAppProduct;
        } catch (Exception $e) {
            throw new CustomException($e->getMessage(), 0, 403);
        }
    }
}