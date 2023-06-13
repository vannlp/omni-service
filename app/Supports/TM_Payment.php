<?php
/**
 * User: dai.ho
 * Date: 10/7/2019
 * Time: 11:40 AM
 */

namespace App\Supports;


use App\Cart;
use App\Order;
use App\TM;
use App\VirtualAccount;
use App\VoucherAccount;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use function Symfony\Component\String\u;

class TM_Payment
{

    protected $momoParam;
    protected $onePayParam;
    protected $shopeePayParam;

    /**
     * TM_Payment constructor.
     */
    public function __construct()
    {
        $this->momoParam = [
            "accessKey"   => env("MOMO_ACCESS_KEY", null),
            "partnerCode" => env("MOMO_PARTNER_CODE", null),
            "requestType" => "captureWallet",
        ];
        $this->shopeePayParam = [
            "merchant_id" =>env("MERCHANT_SPP_ID", null),
            "store_id" =>env("STORE_SPP_ID", null),
            "client_id_web" =>env("CLIENT_ID_SHOPEE_PAY_WEB", null),
            "client_id_mobile_web" =>env("CLIENT_ID_SHOPEE_PAY_MOBILE_WEB", null),
            "secret_web" =>env("SHOPPEPAY_SECURE_SECRET", null),
            "secret_mobile_web" =>env("SHOPPEPAY_SECURE_SECRET_WEB", null),
        ];
    }

    public function requestMOMO($input)
    {
        $time_date = date('Y-m-d H:i:s', time());
        $y         = date("Y", time());
        $m         = date("m", time());
        $d         = date("d", time());
        $h         = date("H", time());
        $i         = date("i", time());
        $s         = date("s", time());
        $order     = Order::model()->where('id', (int)$input['orderId'])->first();
//        if(!empty($order->time_qr_momo) && strtotime($time_date) < $order->time_qr_momo && $order->payment_status != 2){
//            return json_decode($order->log_qr_payment);
//        }
        $order_infor = "Order NTF" . $input['orderId'];
        $order_id    = CODE_SHOPPING_PAYMENT . $y . $m . $d . $h . $i . $s . "-" . $input['orderId'];
        $r_url       = base64_encode($input['returnUrl']);
        $amount      = (float)$input['amount'] ?? "";
        $url = url('/') . "/v0/momo/returnUrl/$r_url";
        $dataHash
             = "accessKey={$this->momoParam['accessKey']}" .
               "&amount=" . $amount .
               "&extraData=" . (array_get($input, 'extraData', "")) .
               "&ipnUrl=" . (array_get($input, 'notifyUrl', "")) .
               "&orderId=" . $order_id .
               "&orderInfo=" . $order_infor .
               "&partnerCode={$this->momoParam['partnerCode']}" .
               "&redirectUrl=" . $url .
               "&requestId=" . $order_id .
               "&requestType={$this->momoParam['requestType']}";
//        $a = "accessKey=Y1Qy4aimZ6Z1uUEY&amount=437991&extraData=eyJ1c2VybmFtZSI6ICJtb21vIn0=&ipnUrl=http://api.viettelomnichannel.com/v0/momo/callback&orderId=NTF20210813151251-3058&orderInfo=Order NTF3058&partnerCode=MOMOHUVF20210715&redirectUrl=http://omni-service.local/v0/momo/returnUrl/aHR0cDovL051dGlmb29kU2hvcC5jb20vY2hlY2tvdXQvY2hlY2tvdXQtcGF5bWVudA==&requestId=NTF20210813151251-3058&requestType=captureWallet";

        $param    = [
            "partnerCode" => $this->momoParam['partnerCode'],
            "requestType" => $this->momoParam['requestType'],
            "ipnUrl"      => $input['notifyUrl'],
            "redirectUrl" => $url,
            //            "ipnUrl" => "https://momo.vn",
            "orderId"     => $order_id,
            "amount"      => $amount,
            "lang"        => "vi",
            "orderInfo"   => $order_infor,
            "requestId"   => $order_id,
            "extraData"   => (array_get($input, 'extraData', "")),
            "signature"   => hash_hmac("sha256", $dataHash, env("MOMO_SECRET_KEY")),
        ];
        $endPoint = env("MOMO_ENDPOINT", null);
        if (!$endPoint) {
            return json_encode([]);
        }
        $client       = new Client();
        try {
            $momoResponse = $client->post($endPoint . "/api/create", [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode($param)]);
            $momoResponse = $momoResponse->getBody();
            $response = !empty($momoResponse) ? json_decode($momoResponse, true) : [];
        }catch (\Exception $exception){
            $order->update([
                'log_qr_payment'  => $exception->getMessage(),
            ]);
            return  $exception->getMessage();
        }
        $uid_payment  = md5(uniqid(rand(), true));
        if ($response) {
            $order->update([
                'time_qr_momo'    => strtotime('+5 minute', strtotime($time_date)),
                'log_qr_payment'  => $response,
                'id_momo_payment' => $response['orderId'],
                'payment_status'  => 0,
                'uid_payment'     => $uid_payment
            ]);
        }
        $now     = date('Y-m-d H:i:s', time());
        $newdate = date('Y-m-d H:i:s', strtotime('+5 minute', strtotime($now)));
        return [
            'title'               => "Thanh toán bằng ví MoMo",
//            'url_payment_qr'      => $response['qrCodeUrl'],
            'url_payment_qr_app'  => $response['payUrl'],
            'time_payment_qr'     => $newdate,
            'create_at_qr'        =>  $now,
            'payment_method'      => PAYMENT_METHOD_MOMO,
            'payment_method_name' => PAYMENT_METHOD_NAME[PAYMENT_METHOD_MOMO] ?? null,
            'order_code'          => $order->code ?? null,
            'order_id'            => $order->id ?? null,
            'total_price'         => $order->total_price,
            'thumbnail_asset'     => 'momo.png',
            'uid_payment'         => $uid_payment
        ];
    }

    public function requestOnePay($input)
    {
        $params = [
            "vpc_Merchant"    => env("ONEPAY_MERCHANT_ID", ""),
            "vpc_AccessCode"  => env("ONEPAY_ACCESS_CODE", ""),
            "vpc_MerchTxnRef" => array_get($input, 'vpc_MerchTxnRef', ""),
            "vpc_OrderInfo"   => array_get($input, 'vpc_OrderInfo', ""),
            "vpc_Amount"      => array_get($input, 'vpc_Amount', 0),
            "vpc_ReturnURL"   => array_get($input, 'vpc_ReturnURL', ""),
            "vpc_Version"     => array_get($input, 'vpc_Version', 2),
            "vpc_Command"     => array_get($input, 'vpc_Command', "pay"),
            "vpc_Locale"      => array_get($input, 'vpc_Locale', "vn"),
            "vpc_Currency"    => array_get($input, 'vpc_Currency', "VND"),
            "vpc_TicketNo"    => array_get($input, 'vpc_TicketNo', url()),
        ];

        ksort($params);
        $dataHash = "";
        foreach ($params as $key => $param) {
            if (!empty($param)) {
                $dataHash .= "$key=$param&";
            }
        }
        $dataHash   = trim(rtrim($dataHash, "&"));
        $secureHash = strtoupper(hash_hmac("sha256", $dataHash, pack('H*', env("ONEPAY_SECURE_SECRET"))));
        $dataHash   .= "&vpc_SecureHash=$secureHash";
        $dataHash   .= "&Title=" . (urlencode(array_get($input, 'Title', "")));
        $dataHash   .= "&AgainLink=" . (urlencode(array_get($input, 'AgainLink', "")));

        $endPoint = env("ONEPAY_ENDPOINT", null);
        if (!$endPoint) {
            return json_encode([]);
        }

        return ["signature" => $secureHash, "payUrl" => "$endPoint?$dataHash"];
    }

    public function requestZalo($input)
    {
        $hashInput = env("ZALO_APPID") . "|" . $input['app_trans_id'] . "|" . $input['app_user'] . "|" . $input['amount'] . "|" . $input['app_time'] . "|" . $input['embed_data'] . "|" . $input['item'];

        $param = [
            "app_id"       => env("ZALO_APPID"),
            "app_trans_id" => $input['app_trans_id'],
            "app_user"     => $input['app_user'],
            "amount"       => $input['amount'],
            "app_time"     => $input['app_time'],
            "embed_data"   => $input['embed_data'],
            "item"         => $input['item'],
            "order_type"   => $input['order_type'],
            "title"        => $input['title'],
            "description"  => $input['description'],
            "device_info"  => $input['device_info'],
            "mac"          => hash_hmac("sha256", $hashInput, env("ZALO_KEY1")),
            "currency"     => $input['currency'],
            "bank_code"    => $input['bank_code'],
            "phone"        => $input['phone'],
            // "email"        => $input['email'],
            "address"      => $input['address']
        ];

        $endPoint = env("ZALO_ENDPOINT", null);
        if (!$endPoint) {
            return json_encode([]);
        }

        $client       = new Client();
        $zaloResponse = $client->post($endPoint . "/v2/create", ['form_params' => $param]);
        $zaloResponse = $zaloResponse->getBody();

        return $zaloResponse;
    }

    public function requestVNPay($input)
    {
        $params = [
            "vnp_Version"    => "2.0.1",
            "vnp_TmnCode"    => env('VNPAY_TMNCODE'),
            "vnp_Amount"     => array_get($input, 'vnp_Amount', ""),
            "vnp_Command"    => array_get($input, 'vnp_Command', "pay"),
            "vnp_CreateDate" => array_get($input, 'vnp_CreateDate', date('YmdHis')),
            "vnp_CurrCode"   => array_get($input, 'vnp_CurrCode', date('VND')),
            "vnp_IpAddr"     => array_get($input, 'vnp_IpAddr', ""),
            "vnp_Locale"     => array_get($input, 'vnp_Locale', "vn"),
            "vnp_OrderInfo"  => array_get($input, 'vnp_OrderInfo', ""),
            "vnp_OrderType"  => array_get($input, 'vnp_OrderType', ""),
            "vnp_ReturnUrl"  => array_get($input, 'vnp_ReturnURL', ""),
            "vnp_TxnRef"     => array_get($input, 'vnp_TxnRef', ""),
        ];
        ksort($params);
        $dataHash = "";
        foreach ($params as $key => $param) {
            if (!empty($param)) {
                $dataHash .= "$key=$param&";
            }
        }
        $dataHash   = trim(rtrim($dataHash, "&"));
        $secureHash = hash('sha256', env("VNPAY_HASHSECRET") . $dataHash);
        $dataHash   .= '&vnp_SecureHashType=SHA256&vnp_SecureHash=' . $secureHash;
        $endPoint   = env("VNPAY_ENDPOINT", null);
        if (!$endPoint) {
            return json_encode([]);
        }
        return ["signature" => $secureHash, "payUrl" => "$endPoint?$dataHash"];
    }

    public function requestShoppepay($input)
    {
        $order = Order::model()->where('id', $input['order_id'])->first();
//        if (!empty($order->shopee_reference_id)) {
//            $param_validate_qr               = [
//                "request_id"           => $order->shopee_reference_id,
//                "store_ext_id"         => "Nutifood123",
//                "merchant_ext_id"      => "Nutifood0123 ",
//                "payment_reference_id" => $order->shopee_reference_id
//            ];
//            $dataHash_validate_qr            = (json_encode($param_validate_qr));
//            $base64_encoded_hash_validate_qr = base64_encode(hash_hmac('sha256', $dataHash_validate_qr, ($input['device'] == "WEB" ? env("SHOPPEPAY_SECURE_SECRET_WEB") : env("SHOPPEPAY_SECURE_SECRET")), true));
//            $client                          = new Client();
//            $client->post(env("API_SHOPPEPAY") . "/v3/merchant-host/order/invalidate", [
//                'headers' => ['Content-Type' => 'application/json', 'X-Airpay-ClientId' => $input['device'] == "WEB" ? 11000068 : 11000067, 'X-Airpay-Req-H' => $base64_encoded_hash_validate_qr
//                ],
//                'body'    => json_encode($param_validate_qr),
//            ]);
//        }
        $now          = date('Y-m-d H:i:s', time());
        $y            = date("Y", time());
        $m            = date("m", time());
        $d            = date("d", time());
        $h            = date("H", time());
        $i            = date("i", time());
        $s            = date("s", time());
        $request_id   = CODE_SHOPPING_PAYMENT . $y . $m . $d . $h . $i . $s . "-" . $order->id;
        $reference_id = CODE_SHOPPING_PAYMENT . $y . $m . $d . $h . $i . $s . "-" . $order->id;
        $newdate      = strtotime('+5 minute', strtotime($now));
        $fe_url       = base64_encode($input['url']);
        $total_price  = $input['total_price'] * 100;
        $url          = url('/') . "/v0/shoppepay/return/$fe_url?request_id=$request_id&reference=$reference_id&price=$total_price";
        if ($input['device'] == "WEB") {
            $params = [
                "request_id"           => $request_id,
                "store_ext_id"         => $this->shopeePayParam['store_id'],
                "merchant_ext_id"      =>$this->shopeePayParam['merchant_id'],
                "amount"               => $total_price,
                "return_url"           => $url,
                //                "platform_type"   => $input['device']=="WEB"? "pc" : ($input['device']=="MWEB"  ? "mweb": "app"),
                "currency"             => "VND",
                "expiry_time"          => $newdate,
                "payment_reference_id" => $reference_id
            ];
        } else {
            $params = [
                "request_id"           => $request_id,
                "store_ext_id"         => $this->shopeePayParam['store_id'],
                "merchant_ext_id"      => $this->shopeePayParam['merchant_id'],
                "amount"               => $total_price,
                "return_url"           => $url,
                "platform_type"        => $input['device'] == "WEB" ? "pc" : ($input['device'] == "MWEB" ? "mweb" : "app"),
                "currency"             => "VND",
                "expiry_time"          => $newdate,
                "payment_reference_id" => $reference_id
            ];
        }
        $dataHash            = (json_encode($params));
        $base64_encoded_hash = base64_encode(hash_hmac('sha256', $dataHash, ($input['device'] == "WEB" ? $this->shopeePayParam['secret_web'] : $this->shopeePayParam['secret_mobile_web']), true));
        $client              = new Client();
        $shoppe_response     = $client->post(env("API_SHOPPEPAY") . ($input['device'] == "WEB" ? "/v3/merchant-host/qr/create" : "/v3/merchant-host/order/create"), [
            'headers' => ['Content-Type' => 'application/json', 'X-Airpay-ClientId' => $input['device'] == "WEB" ? (int)$this->shopeePayParam['client_id_web'] : $this->shopeePayParam['client_id_mobile_web'], 'X-Airpay-Req-H' => $base64_encoded_hash
            ],
            'body'    => json_encode($params),
        ]);
        $shoppe_response     = $shoppe_response->getBody();
        $response            = !empty($shoppe_response) ? json_decode($shoppe_response, true) : [];
        $uid_payment         = md5(uniqid(rand(), true));

        $order->update([
            'shopee_reference_id' => $reference_id,
            'time_qr_spp'         => $newdate,
            'uid_payment'         => $uid_payment,
            'log_qr_payment'      => json_encode($response)
        ]);
        return [
            'title'               => "Thanh toán bằng ví " . PAYMENT_METHOD_NAME[PAYMENT_METHOD_SPP] ?? null,
            'url_payment_qr'      => $response['qr_url'] ?? null,
            'url_payment_qr_app'  => $response['redirect_url_http'] ?? null,
            'time_payment_qr'     => date("Y-m-d H:i:s", $newdate),
            'create_at_qr'        => $now,
            'payment_method'      => PAYMENT_METHOD_SPP,
            'payment_method_name' => PAYMENT_METHOD_NAME[PAYMENT_METHOD_SPP] ?? null,
            'order_code'          => $order->code ?? "",
            'order_id'            => $order->id ?? null,
            'total_price'         => $order->total_price ?? null,
            'thumbnail_asset'     => 'sp-pay.png',
            'uid_payment'         => $uid_payment
        ];
    }

    public function virtualAccount($input){
        $order= Order::find($input['order_id']);
        $uid_payment                 = md5(uniqid(rand(), true));
        if(empty($order->virtual_account_code)) {
            $virtualAccount            = VirtualAccount::where('is_active', 1)->first();
            if(empty($virtualAccount)){
                VirtualAccount::model()->whereNull('order_id')->update([
                    'is_active' => 1
                ]);
                $virtualAccount            = VirtualAccount::where('is_active',1)->first();
            }
            $virtualAccountCode        = $virtualAccount->code;
            $virtualAccount->order_id  = $order->id;
            $virtualAccount->is_active = 0;
            $virtualAccount->save();
            $order->virtual_account_code = $virtualAccountCode;
            $order->uid_payment          = $uid_payment;
            $order->save();
        }
        if(!empty($order->virtual_account_code)){
            $virtualAccountCode=$order->virtual_account_code;
        }

        return [
            'title'               => "Quý khách vui lòng chuyển khoản theo nội dung bên dưới:",
            'create_at_qr'        => date('Y-m-d H:i:s', time()),
            'payment_method'      => PAYMENT_METHOD_BANK,
            'payment_method_name' => PAYMENT_METHOD_NAME[PAYMENT_METHOD_BANK] ?? null,
            'order_code'          => $order->code ?? "",
            'virtual_account_code'=> $virtualAccountCode,
            'order_id'            => $order->id ?? null,
            'total_price'         => $order->total_price ?? null,
            'thumbnail_asset'     => 'money-transfer.png',
            'phone'               => $order->customer_phone ?? null,
            'uid_payment'         => $uid_payment
        ];
        
        
    }

    public function check3DSVpBank($input)
    {
        $now                    = date('Y-m-d H:i:s', time());
        $y                      = date("Y", time());
        $m                      = date("m", time());
        $d                      = date("d", time());
        $h                      = date("H", time());
        $i                      = date("i", time());
        $s                      = date("s", time());
        $request_id             = CODE_SHOPPING_PAYMENT . $y . $m . $d . $h . $i . $s . "-" . $input['order_id'];
        $order_id               = $input['order_id'];
        $amount                 = $input['amount'];
        $currency               = $input['currency'];
        $session_id             = $input['session_id'];
        $version                = $input['session_version'];
        $order_code             = $input['order_code'];
        $url_link               = base64_encode($input['url']);
        $url                    = "https://api.nutifoodshop.com/v0/vpbank/returnVpbank?amount=$amount&currency=$currency&order_id=$order_id&session=$session_id&code=$order_code&version=$version&check=$request_id&url=$url_link";
        $code                   = $input['order_code'];
        $authenticationredirect = [
            'authenticationRedirect' => ['responseUrl' => $url]
        ];
        $session                = [
            'id'      => $session_id,
            'version' => $version
        ];
        $param                  = [
            '3DSecure'     => $authenticationredirect,
            'session'      => $session,
            'apiOperation' => "CHECK_3DS_ENROLLMENT"
        ];
        $authorization          = $this->getAuthorization();
        $client                 = new Client();
        $vp_bank                = $client->put(env("API_VPBANK") . "/3DSecureId/$request_id", [
            'headers' => ['Content-Type' => 'application/json', 'authorization' => "Basic $authorization"
            ],
            'body'    => json_encode($param)
        ]);
        $vp_bank                = $vp_bank->getBody();
        $response               = !empty($vp_bank) ? json_decode($vp_bank, true) : [];
        return $response;
    }

    public function getAuthorization()
    {
        $username      = 'Merchant.' . env("VPBANK_MERCHANT");
        $password      = env("VPBANK_PASSWORD");
        $authorization = base64_encode("$username:$password");
        return $authorization;
    }

    public function initAuthentication($input){

        $authorization          = $this->getAuthorization();
        $client                 = new Client();
        $endPoint = env("API_VPBANK") . '/order/' . $input['order_id'] . '/transaction/' . $input['request_id'];
        
        $param = [
            'authentication'     => [
                'acceptVersions' => "3DS1,3DS2",
                'channel' => "PAYER_BROWSER",
                'purpose' => "PAYMENT_TRANSACTION",
            ],
            'correlationId' => $input['order_code'],
            'order'     => [
                'currency' => $input['currency'],
            ],
            'session'     => [
                'id' => $input['session_id']
            ],
            'apiOperation' => 'INITIATE_AUTHENTICATION'
        ];
        
        $this->writeLogVpbank('initAuthentication | ' . $endPoint, json_encode($param));
        $vp_bank                = $client->put($endPoint, [
            'headers' => ['Content-Type' => 'application/json', 'authorization' => "Basic $authorization"
            ],
            'body'    => json_encode($param)
        ]);
        $vp_bank                = $vp_bank->getBody();
        $response               = !empty($vp_bank) ? json_decode($vp_bank, true) : [];
        // TM::sendMessage("initAuthentication:",[$param,$response]);
        $this->writeLogVpbank('initAuthentication | ' . $endPoint, json_encode($param), $vp_bank);
        return $response;
    }

    public function authenticatePayer($input)
    {
        $authorization          = $this->getAuthorization();
        $client                 = new Client();

        $returnUrl              = env('VPBANK_RETURN_URL');
        $request_id             = $input['request_id'];
        $order_id               = $input['order_id'];
        $amount                 = $input['amount'];
        $currency               = $input['currency'];
        $session_id             = $input['session_id'];
        $order_code             = $input['order_code'];
        $url_link               = base64_encode($input['url']);
        $endPoint               = env("API_VPBANK") . '/order/' . $order_id . '/transaction/' . $request_id;
        $url                    = "$returnUrl/v0/vpbank/returnVpbank?amount=$amount&currency=$currency&order_id=$order_id&session=$session_id&code=$order_code&check=$request_id&url=$url_link";
        $browser                = explode('/', $_SERVER['HTTP_USER_AGENT']);
        $param = [
            'authentication'     => [
                'redirectResponseUrl' => $url
            ],
            'correlationId' => $order_code,
            'device' => [
                'browser'  => strtoupper($browser[0]),
                'browserDetails' => [
                    '3DSecureChallengeWindowSize' => "FULL_SCREEN",
                    'acceptHeaders' => 'application/json',
                    'colorDepth' => 24,
                    'javaEnabled' => true,
                    'language' =>    'vi-VN',
                    'timeZone' =>    238,
                    'screenHeight' => $input['screenHeight'] ?? 1280,
                    'screenWidth'  => $input['screenWidth'] ?? 768,
                ],
                'ipAddress' => $_SERVER['REMOTE_ADDR'],
            ],
            'order'     => [
                'amount'   => $amount,
                'currency' => $currency
            ],
            'session'     => [
                'id'      => $session_id
            ],
            'apiOperation' => 'AUTHENTICATE_PAYER'
        ];
        try {
            $this->writeLogVpbank('authenticatePayer | ' . $endPoint, json_encode($param));
            $vp_bank                = $client->put($endPoint, [
                'headers' => [
                    'Content-Type' => 'application/json', 'authorization' => "Basic $authorization"
                ],
                'body'    => json_encode($param)
            ]);
            $vp_bank                = $vp_bank->getBody();
            $response               = !empty($vp_bank) ? json_decode($vp_bank, true) : [];
            // TM::sendMessage("authenticatePayer:", [$param, $response]);
            $this->writeLogVpbank('authenticatePayer | ' . $endPoint, json_encode($param), $vp_bank);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $this->writeLogVpbank('authenticatePayer | ' . $endPoint, json_encode($param), $e->getResponse()->getBody()->getContents());
        }
        return $response ?? null;
    }


    #Log VPBANK
    private function writeLogVpbank($data = null, $request = null, $response = null){
        try {
            DB::table('vpbank_logs')->insert([
                'data'     => $data,
                'request'  => $request,
                'response' => $response,
                'time'     => date('Y-m-d H:i:s', time())
            ]);
        }
        catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
        }
    }
}