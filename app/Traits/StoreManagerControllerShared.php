<?php
namespace App\Traits;
use App\Models\GooglePurchases;
use App\Models\Stores;
use App\Models\StoreSubscriptions;
use Carbon\Carbon;
use DB;
use Exception;
use Google\Service\AndroidPublisher;
use Google\Service\AndroidPublisher\ProductPurchasesAcknowledgeRequest;
use Google\Service\Pubsub\AcknowledgeRequest;
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
            Logger(json_encode($purchase));
            // print_r($purchase);
            // print_r('purchaseSatae' . ': ' . $purchase->purchaseState);
            // print_r($purchaseToken);
            // print_r($googlePurchase->productId);
            // print_r($app->packageName);

            // Logger(json_encode($purchase));
            $updatedData = [
                GooglePurchases::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ];
            if ($googlePurchase->orderId == null) {
                DB::table(table: GooglePurchases::$tableName)
                    ->where(GooglePurchases::$purchaseToken, '=', $purchaseToken)
                    ->update(
                        [GooglePurchases::$orderId => $purchase->orderId,]
                    );
            }
            if ($purchase->purchaseState == 0) {
                // print_r("4343434");
                if ($googlePurchase->isPending != 0) {
                    $updatedData[GooglePurchases::$isPending] = 0;
                    $updatedData[GooglePurchases::$orderId] = $googlePurchase->orderId;

                }
                // if ($purchase->consumptionState != 1) {
                //     $service->purchases_products->consume($app->packageName, $googlePurchase->productId, $purchaseToken);
                //     $updatedData[GooglePurchases::$isCounsumed] = 1;
                // }
                if ($purchase->acknowledgementState !== 1) {
                    $acknowledgeRequest = new ProductPurchasesAcknowledgeRequest();
                    $service->purchases_products->acknowledge($app->packageName, $googlePurchase->productId, $purchaseToken, $acknowledgeRequest);
                    $updatedData[GooglePurchases::$isAck] = 1;
                }
                if ($purchase->consumptionState != 1) {
                    $service->purchases_products->consume($app->packageName, $googlePurchase->productId, $purchaseToken);
                    $updatedData[GooglePurchases::$isCounsumed] = 1;
                }

                // if ($purchase->acknowledgementState !== 1) {
                //     $service->purchases_products->acknowledge($app->packageName, $googlePurchase->productId, $purchaseToken);
                //     $updatedData[GooglePurchases::$isAck] = 1;
                // }
                ////


                if ($googlePurchase->isSubs == 1) {
                    $newDate = Carbon::now()->addMonths($inAppProduct->points)->endOfDay()->format('Y-m-d H:i:s');
                    DB::table(table: StoreSubscriptions::$tableName)
                        ->where(StoreSubscriptions::$storeId, '=', $store->id)
                        ->update(
                            [
                                StoreSubscriptions::$isPremium => 1,
                                StoreSubscriptions::$expireAt => $newDate
                            ]
                        );
                    DB::table(table: Stores::$tableName)
                        ->where(Stores::$id, '=', $store->id)
                        ->update(
                            [
                                Stores::$typeId => 2,
                            ]
                        );
                } else {
                    DB::table(table: StoreSubscriptions::$tableName)
                        ->where(StoreSubscriptions::$storeId, '=', $store->id)
                        ->update(
                            [StoreSubscriptions::$points => DB::raw(StoreSubscriptions::$points . " + ($inAppProduct->points)")]
                        );
                }
                $updatedData[GooglePurchases::$isGet] = 1;
                if ($updatedData > 1) {
                    DB::table(table: GooglePurchases::$tableName)
                        ->where(GooglePurchases::$purchaseToken, '=', $purchaseToken)
                        ->update(
                            $updatedData
                        );
                }

                $inAppProduct->isPending = false;
                return $inAppProduct;
            } elseif ($purchase->purchaseState == 1) {
                if ($googlePurchase->isPending != 1) {
                    $updatedData[GooglePurchases::$isPending] = 1;
                }
                if ($updatedData > 1) {
                    DB::table(table: GooglePurchases::$tableName)
                        ->where(GooglePurchases::$purchaseToken, '=', $purchaseToken)
                        ->update(
                            $updatedData
                        );
                }

                // DB::table(table: StoreSubscriptions::$tableName)
                //     ->where(StoreSubscriptions::$storeId, '=', $store->id)
                //     ->update(
                //         [StoreSubscriptions::$points => DB::raw(StoreSubscriptions::$points . " + ($inAppProduct->points)")]
                //     );
                $inAppProduct->isPending = false;
                return $inAppProduct;
            } elseif ($purchase->purchaseState == 2) {
                if ($googlePurchase->isPending != 2) {
                    $updatedData[GooglePurchases::$isPending] = 2;
                }
                if ($updatedData > 1) {
                    DB::table(table: GooglePurchases::$tableName)
                        ->where(GooglePurchases::$purchaseToken, '=', $purchaseToken)
                        ->update(
                            $updatedData
                        );
                }

                // DB::table(table: StoreSubscriptions::$tableName)
                //     ->where(StoreSubscriptions::$storeId, '=', $store->id)
                //     ->update(
                //         [StoreSubscriptions::$points => DB::raw(StoreSubscriptions::$points . " + ($inAppProduct->points)")]
                //     );

            }
            $inAppProduct->isPending = true;
            return $inAppProduct;
        } catch (Exception $e) {
            throw new CustomException($e->getMessage(), 0, 403);
        }
    }
}