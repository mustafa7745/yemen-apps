<?php
namespace App\Http\Controllers\Api\Whatsapp;

use App\Http\Controllers\Controller;
use App\Models\Apps;
use App\Models\AppStores;
use App\Models\Countries;
use App\Models\DevicesSessions;
use App\Models\Stores;
use App\Models\Users;
use App\Models\UsersSessions;
use App\Services\WhatsappService;
use Carbon\Carbon;
use DB;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Log\Logger;

class WhatsappController extends Controller
{
    public function whatsapp_webhook(Request $request)
    {
        try {
            $whatsapp = new WhatsappService();

            $phoneNumber = $request->input('entry.0.changes.0.value.messages.0.from');
            $message = $request->input('entry.0.changes.0.value.messages.0.text.body');
            $name = $request->input('entry.0.changes.0.value.contacts.0.profile.name');
            $message = trim($message);

            $messageId = $request->input('entry.0.changes.0.value.messages.0.id');

            
            Logger('Message Id:' . $messageId);
            Logger('Message:' . $message);

            return response()->json(['status' => 'received'], 200);
            
            $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
            $number = $phoneUtil->parse("+" . $phoneNumber, null);
            $countryCode = $number->getCountryCode();
            $regionCode = $phoneUtil->getRegionCodeForNumber($number);
            $nationalNumber = $number->getNationalNumber();
            $user = $this->findUser($nationalNumber, $countryCode, $regionCode);

            if ($message == "اشتراك") {
                $this->handleSubscription($user, $name, $nationalNumber, $countryCode, $regionCode, $phoneNumber, $whatsapp);
            } elseif ($message == "نسيت كلمة المرور") {
                $this->handlePasswordReset($user, $phoneNumber, $whatsapp);
            } elseif (preg_match('/^رمز التطبيق\s*\{\s*([^{}]+)\s*\}$/u', $message, $matches)) {
                $storeId = $matches[1];
                $this->handleAppCode($user, $storeId, $phoneNumber, $whatsapp);
            } elseif (preg_match('/^تسجيل الخروج من \{([^{}]+)\}$/u', $message, $matches)) {
                $appId = $matches[1];
                $this->handleLogout($user, $appId, $phoneNumber, $whatsapp);
            }

            return response()->json(['status' => 'received'], 200);
        } catch (\Throwable $th) {
            Logger($th->getMessage());
            //throw $th;
        } 
        // finally {
        //     // return response()->json(['success' => true]);
        //     return response()->json(['status' => 'received'], 200);
        // }

    }

    private function findUser($nationalNumber, $countryCode, $regionCode)
    {
        return DB::table(Users::$tableName)
            ->join(
                Countries::$tableName,
                Countries::$tableName . '.' . Countries::$id,
                '=',
                Users::$tableName . '.' . Users::$countryId
            )
            ->where(Users::$tableName . '.' . Users::$phone, '=', $nationalNumber)
            ->where(Countries::$tableName . '.' . Countries::$code, '=', $countryCode)
            ->where(Countries::$tableName . '.' . Countries::$region, '=', $regionCode)
            ->first([
                Users::$tableName . '.' . Users::$id,
                Users::$tableName . '.' . Users::$firstName,
                Users::$tableName . '.' . Users::$lastName,
            ]);
    }

    private function handleSubscription($user, $name, $nationalNumber, $countryCode, $regionCode, $phoneNumber, $whatsapp)
    {
        if ($user != null) {
            $whatsapp->sendMessageText($phoneNumber, "هذا المستخدم لديه حساب مسبق");
            return;
        }

        $country = DB::table(Countries::$tableName)
            ->where(Countries::$code, $countryCode)
            ->where(Countries::$region, $regionCode)
            ->first();

        $countryId = $country?->id ?? DB::table(Countries::$tableName)->insertGetId([
            Countries::$code => $countryCode,
            Countries::$region => $regionCode,
            Countries::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
            Countries::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $password = $this->generateRandomPassword();
        $hashedPassword = Hash::make($password);

        DB::table(Users::$tableName)->insert([
            Users::$firstName => $name,
            Users::$lastName => $name,
            Users::$phone => $nationalNumber,
            Users::$password => $hashedPassword,
            Users::$countryId => $countryId,
            Users::$createdAt => Carbon::now()->format('Y-m-d H:i:s'),
            Users::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $msg = "تم اضافة هذا المستخدم بنجاح\n";
        $msg .= "معلومات الدخول:\n";
        $msg .= "المنطقة: {$regionCode}\n";
        $msg .= "رقم الهاتف هو:\n+{$countryCode}{$nationalNumber}\n";
        $msg .= "الرقم السري هو:";

        $whatsapp->sendMessageText($phoneNumber, $msg);
        $whatsapp->sendMessageText($phoneNumber, $password);
    }

    private function handlePasswordReset($user, $phoneNumber, $whatsapp)
    {
        if ($user == null) {
            $whatsapp->sendMessageText($phoneNumber, "يجب الاشتراك اولا");
            return;
        }

        $password = $this->generateRandomPassword();
        $hashedPassword = Hash::make($password);

        DB::table(Users::$tableName)
            ->where(Users::$id, $user->id)
            ->update([
                Users::$password => $hashedPassword,
                Users::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

        $whatsapp->sendMessageText($phoneNumber, "الرقم السري الجديد هو:");
        $whatsapp->sendMessageText($phoneNumber, $password);
    }

    private function handleAppCode($user, $storeId, $phoneNumber, $whatsapp)
    {
        if ($user == null) {
            $whatsapp->sendMessageText($phoneNumber, "يجب الاشتراك اولا");
            return;
        }

        $app = DB::table(table: AppStores::$tableName)
            ->where(AppStores::$tableName . '.' . AppStores::$storeId, '=', $storeId)
            ->where(Stores::$tableName . '.' . Stores::$userId, '=', $user->id)

            ->join(
                Apps::$tableName,
                Apps::$tableName . '.' . Apps::$id,
                '=',
                AppStores::$tableName . '.' . AppStores::$appId
            )
            ->join(
                Stores::$tableName,
                Stores::$tableName . '.' . Stores::$id,
                '=',
                AppStores::$tableName . '.' . AppStores::$storeId
            )
            ->join(
                Users::$tableName,
                Users::$tableName . '.' . Users::$id,
                '=',
                Stores::$tableName . '.' . Stores::$userId
            )
            ->first(
                [Apps::$tableName . '.' . Apps::$id . ' as id']
            );

        if ($app == null) {
            $whatsapp->sendMessageText($phoneNumber, "التطبيق غير موجود");
            return;
        }

        $password = $this->generateRandomPassword();
        $hashedPassword = Hash::make($password);

        DB::table(Apps::$tableName)
            ->where(Apps::$id, $app->id)
            ->update([
                Apps::$password => $hashedPassword,
                Apps::$updatedAt => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

        $whatsapp->sendMessageText($phoneNumber, "الرقم السري الجديد للتطبيق هو:");
        $whatsapp->sendMessageText($phoneNumber, $password);
    }

    private function handleLogout($user, $appId, $phoneNumber, $whatsapp)
    {
        if ($user == null) {
            $whatsapp->sendMessageText($phoneNumber, "يجب الاشتراك اولا");
            return;
        }

        $userSession = DB::table(UsersSessions::$tableName)
            ->join(
                DevicesSessions::$tableName,
                DevicesSessions::$tableName . '.' . DevicesSessions::$id,
                '=',
                UsersSessions::$tableName . '.' . UsersSessions::$deviceSessionId
            )
            ->where(UsersSessions::$tableName . '.' . UsersSessions::$userId, '=', $user->id)
            ->where(UsersSessions::$tableName . '.' . UsersSessions::$isLogin, '=', 1)

            ->where(DevicesSessions::$tableName . '.' . DevicesSessions::$appId, '=', $appId)

            ->first(
                [
                    UsersSessions::$tableName . '.' . UsersSessions::$id,

                ]
            );

        if ($userSession != null) {
            DB::table(table: UsersSessions::$tableName)
                ->where(UsersSessions::$id, '=', $userSession->id)
                ->update([
                    UsersSessions::$isLogin => 0,
                    UsersSessions::$logoutCount => DB::raw(UsersSessions::$logoutCount . ' + 1'),
                    UsersSessions::$lastLogoutAt => Carbon::now()->format('Y-m-d H:i:s'),
                    UsersSessions::$updatedAt => Carbon::now()->format('Y-m-d H:i:s')
                ]);
            $whatsapp->sendMessageText($phoneNumber, "تم تسجيل الخروج بنجاح (Session ID: {$userSession->id})");
        } else {
            $whatsapp->sendMessageText($phoneNumber, "ثمة خطأ");
        }
    }

    function generateRandomPassword($length = 8)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
        $charactersLength = strlen($characters);
        $randomPassword = '';
        for ($i = 0; $i < $length; $i++) {
            $randomPassword .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomPassword;
    }

}