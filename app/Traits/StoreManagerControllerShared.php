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
    function getDayName($dayNumber)
    {
        $days = [
            1 => 'السبت',
            2 => 'الأحد',
            3 => 'الاثنين',
            4 => 'الثلاثاء',
            5 => 'الأربعاء',
            6 => 'الخميس',
            7 => 'الجمعة'
        ];

        // التحقق من وجود الرقم في المصفوفة
        if (array_key_exists($dayNumber, $days)) {
            return $days[$dayNumber];
        } else {
            return 'رقم اليوم غير صحيح';
        }
    }
    function encryptData($password, $data, )
    {
        // Generate a random salt
        $salt = random_bytes(16);

        // Derive a 256-bit key using PBKDF2
        $iterations = 100000; // Number of iterations
        $keyLength = 32; // 256-bit key
        $secretKey = hash_pbkdf2('sha256', $password, $salt, $iterations, $keyLength, true);

        // Generate a random IV
        $iv = random_bytes(16);

        // Encrypt the data using AES-256-CBC
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $secretKey, OPENSSL_RAW_DATA, $iv);

        // Encode the encrypted data, IV, and salt as hexadecimal strings
        return [
            'encrypted' => bin2hex($encrypted),
            'iv' => bin2hex($iv),
            'salt' => bin2hex($salt),
        ];
    }

    /**
     * Decrypts data encrypted with AES-256-CBC using a password.
     * 
     * @param string $encryptedHex The encrypted data as a hexadecimal string.
     * @param string $ivHex The IV as a hexadecimal string.
     * @param string $saltHex The salt as a hexadecimal string.
     * @param string $password The password used for encryption.
     * @return string The decrypted data.
     */
    function decryptData($encryptedData, $password)
    {
        $parts = explode(':', $encryptedData);
        if (count($parts) !== 3) {
            throw new Exception("Invalid encrypted data format. Expected iv:salt:encryptedData.");
        }

        $ivHex = $encryptedData->iv;
        $saltHex = $encryptedData->salt;
        $encryptedHex = $encryptedData->encrypted;

        // Decode the hexadecimal strings to binary
        $iv = hex2bin($ivHex);
        $salt = hex2bin($saltHex);
        $encrypted = hex2bin($encryptedHex);

        // Derive the key using the same password and salt
        $iterations = 100000; // Number of iterations
        $keyLength = 32; // 256-bit key
        $secretKey = hash_pbkdf2('sha256', $password, $salt, $iterations, $keyLength, true);

        // Decrypt the data using AES-256-CBC
        return openssl_decrypt($encrypted, 'aes-256-cbc', $secretKey, OPENSSL_RAW_DATA, $iv);
    }

    // function encryptServiceAccount($serviceAccount, $key)
    // {
    //     // Ensure UTF-8 encoding for the service account string
    //     $serviceAccount = mb_convert_encoding($serviceAccount, 'UTF-8');

    //     // Generate a random 16-byte IV (Initialization Vector)
    //     $iv = openssl_random_pseudo_bytes(16);

    //     // Encrypt the service account string using AES-256-CBC
    //     $encrypted = openssl_encrypt($serviceAccount, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

    //     // Combine the IV and encrypted data, then base64-encode
    //     return base64_encode($iv . $encrypted);
    // }

    // function decryptServiceAccount($encryptedData, $key)
    // {
    //     // Decode the base64-encoded encrypted data
    //     $encryptedData = base64_decode($encryptedData);

    //     // Extract the IV (first 16 bytes) and the encrypted data (remaining bytes)
    //     $iv = substr($encryptedData, 0, 16);
    //     $encrypted = substr($encryptedData, 16);

    //     // Decrypt the service account string using AES-256-CBC
    //     $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

    //     // Ensure the decrypted string is UTF-8 encoded
    //     return mb_convert_encoding($decrypted, 'UTF-8');
    // }

    // function encryptRsa($password, $data)
    // {
    //     // 1. Generate a secure encryption key (this should be kept secret)
    //     $key = $password; // AES-128 requires 16 bytes, AES-256 requires 32 bytes
    //     $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc')); // Initialization Vector

    //     // 2. Encrypt the data
    //     $dataToEncrypt = str_replace(["\n", "\r"], '', $data);

    //     // print_r($data);
    //     // $dataToEncrypt = mb_convert_encoding($data, 'UTF-8');
    //     // print_r($dataToEncrypt);

    //     $ciphertext = openssl_encrypt($dataToEncrypt, 'aes-256-cbc', $key, 0, $iv);

    //     // Encode the ciphertext and IV so they can be stored or transmitted (Base64 encoding)
    //     $encodedCiphertext = base64_encode($ciphertext);

    //     return $encodedCiphertext;
    //     // $encodedIv = base64_encode($iv);

    //     // echo "Encrypted data: " . $encodedCiphertext . "\n";
    //     // echo "IV: " . $encodedIv . "\n";

    //     // // 3. Decrypt the data (using the same key and IV)
    //     // $decodedCiphertext = base64_decode($encodedCiphertext);
    //     // $decodedIv = base64_decode($encodedIv);

    //     // $decryptedData = openssl_decrypt($decodedCiphertext, 'aes-256-cbc', $key, 0, $decodedIv);

    //     // echo "Decrypted data: " . $decryptedData . "\n";
    // }
    // function decryptRsa($password, $encodedCiphertext)
    // {
    //     // 1. Generate a secure encryption key (this should be kept secret)
    //     $key = $password; // AES-128 requires 16 bytes, AES-256 requires 32 bytes
    //     $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc')); // Initialization Vector

    //     // 2. Encrypt the data
    //     // $dataToEncrypt = $data;
    //     // $ciphertext = openssl_encrypt($dataToEncrypt, 'aes-256-cbc', $key, 0, $iv);

    //     // Encode the ciphertext and IV so they can be stored or transmitted (Base64 encoding)
    //     // $encodedCiphertext = base64_encode($ciphertext);

    //     // return $encodedCiphertext;
    //     $encodedIv = base64_encode($iv);

    //     // echo "Encrypted data: " . $encodedCiphertext . "\n";
    //     // echo "IV: " . $encodedIv . "\n";

    //     // 3. Decrypt the data (using the same key and IV)
    //     $decodedCiphertext = base64_decode($encodedCiphertext);
    //     $decodedIv = base64_decode($encodedIv);

    //     $decryptedData = openssl_decrypt($decodedCiphertext, 'aes-256-cbc', $key, 0, $decodedIv);

    //     // echo "Decrypted data: " . $decryptedData . "\n";
    //     return $decryptedData;
    //     // mb_convert_encoding($decryptedData, 'UTF-8');
    // }
    function isValidJson($string)
    {
        json_decode($string); // Attempt to decode the string
        return (json_last_error() == JSON_ERROR_NONE); // Check for JSON errors
    }
}