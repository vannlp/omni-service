<?php
/**
 * User: dai.ho
 * Date: 11/06/2020
 * Time: 1:44 PM
 */

namespace App\Http\Controllers\Auth;


use App\Setting;
use GuzzleHttp\Client;
use Laravel\Lumen\Routing\Controller;
use App\Supports\Message;

class BaseController extends Controller
{
    protected function responseError($msg = null, $code = 400)
    {
        $msg = $msg ? $msg : Message::get("V1001");
        return response()->json(['status' => 'error', 'error' => ['errors' => ["msg" => $msg]]], $code);
    }


    protected function sendSMS($message, $phone)
    {
        $param           = [
            'Phone'     => $phone,
            'Content'   => $message,
            'ApiKey'    => env('SMS_API_KEY'),
            'SecretKey' => env('SMS_SECRET_KEY'),
            'SmsType'   => 2,
            'Brandname' => env('SMS_BRAND_NAME'),
        ];
        $client          = new Client();
        $phonesException = Setting::model()->select('data')->where('code', 'PHONE-TEST')->first();
        $phonesException = explode(',', $phonesException->data ?? '');
        if (env('SMS_ENABLE_SEND', null) == 1 && !in_array($phone, $phonesException)) {
            $smsResponse = $client->get(env('SMS_URL'), ['query' => $param])->getBody();
        }
        return $smsResponse ?? null;
    }
}