<?php

namespace App\V1\Controllers;


use App\Cart;
use App\CheckActiveAccesstrade;
use App\City;
use App\Company;
use App\CouponHistory;
use App\CustomerInformation;
use App\District;
use App\Jobs\SendCustomerMailNewOrderJob;
use App\Jobs\SendHUBMailNewOrderJob;
use App\Jobs\SendStoreMailNewOrderJob;
use App\LogPaymentRequest;
use App\LogShippingOrder;
use App\Order;
use App\OrderDetail;
use App\OrderStatus;
use App\PaymentHistory;
use App\PaymentRefund;
use App\PaymentVirtualAccount;
use App\Profile;
use App\PromotionProgram;
use App\Setting;
use App\Store;
use App\Supports\DataUser;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\Supports\TM_Payment;
use App\TM;
use App\RotationDetail;
use App\User;
use App\UserGroup;
use App\UserSession;
use App\V1\Library\OrderSyncDMS;
use App\V1\Models\CartModel;
use App\V1\Models\NotificationHistoryModel;
use App\V1\Models\OrderModel;
use App\V1\Models\PaymentHistoryModel;
use App\V1\Models\WalletHistoryModel;
use App\V1\Traits\ControllerTrait;
use App\V1\Validators\Payment\PaymentMoMoRequestValidator;
use App\V1\Validators\Payment\PaymentOnePayRequestValidator;
use App\V1\Validators\Payment\PaymentVNPayRequestValidator;
use App\V1\Validators\Payment\PaymentVpBankRequestValidator;
use App\V1\Validators\Payment\PaymentZaloRequestValidator;
use App\Wallet;
use App\Ward;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Routing\UrlGenerator;
use Monolog\Logger;
use function Couchbase\basicEncoderV1;
use App\V1\Library\Accesstrade;

class PaymentController extends BaseController
{
    use ControllerTrait;

    protected $paymentMethod;
    protected $shopeePayParam;
    protected $momoParam;

    /**
     * PaymentController constructor.
     */
    public function __construct()
    {
        $this->orderModel     = new OrderModel();
        $this->shopeePayParam = [
            "merchant_id"          => env("MERCHANT_SPP_ID", null),
            "store_id"             => env("STORE_SPP_ID", null),
            "client_id_web"        => env("CLIENT_ID_SHOPEE_PAY_WEB", null),
            "client_id_mobile_web" => env("CLIENT_ID_SHOPEE_PAY_MOBILE_WEB", null),
            "secret_web"           => env("SHOPPEPAY_SECURE_SECRET", null),
            "secret_mobile_web"    => env("SHOPPEPAY_SECURE_SECRET_WEB", null),
        ];
        $this->momoParam      = [
            "accessKey"   => env("MOMO_ACCESS_KEY", null),
            "partnerCode" => env("MOMO_PARTNER_CODE", null),
            "requestType" => "captureWallet",
        ];
        $this->paymentMethod  = [
            [
                "code"            => PAYMENT_METHOD_CASH,
                "name"            => "Thanh toán tiền mặt khi nhận hàng (COD)",
                "thumbnail"       => TM::urlBase("/_nuxt/img/cash.d4d3fd5.png"),
                "thumbnail_asset" => "cash.png",
                "description"     => "",
            ],
            [
                "code"            => PAYMENT_METHOD_ONEPAY,
                "name"            => "Thanh toán qua thẻ ATM",
                "thumbnail"       => TM::urlBase("/_nuxt/img/credit-card.d9d35f2.png"),
                "thumbnail_asset" => "credit-card.png",
                "description"     => "",
            ],
            [
                "code"            => PAYMENT_METHOD_MOMO,
                "name"            => "Thanh toán qua ví MOMO",
                "thumbnail"       => TM::urlBase("/_nuxt/img/momo.931b119.png"),
                "thumbnail_asset" => "momo.png",
                "description"     => "",
            ],
            [
                "code"            => PAYMENT_METHOD_ZALO,
                "name"            => "Thanh toán qua ví Zalo",
                "thumbnail"       => TM::urlBase("/_nuxt/img/zalo-pay.0fa5573.png"),
                "thumbnail_asset" => "zalo-pay.png",
                "description"     => "",
            ],
            [
                "code"            => PAYMENT_METHOD_VNPAY,
                "name"            => "Thanh toán qua ví VnPay",
                "thumbnail"       => "",
                "thumbnail_asset" => "vn-pay.png",
                "description"     => "",
            ],
            [
                "code"            => PAYMENT_METHOD_SPP,
                "name"            => "Thanh toán qua ví ShopeePay",
                "thumbnail"       => "",
                "thumbnail_asset" => "sp-pay.png",
                "description"     => "",
            ],
            [
                "code"            => PAYMENT_METHOD_VPB,
                "name"            => "Thanh toán qua thẻ tín dụng/thẻ ghi nợ quốc tế",
                "thumbnail"       => "",
                "thumbnail_asset" => "vb-bank.png",
                "description"     => "",
            ],
            [
                "code"            => "bank_transfer",
                "name"            => "Chuyển khoản qua ngân hàng",
                "thumbnail"       => "",
                "thumbnail_asset" => "money-transfer.png",
                "description"     => "Khách hàng tự chuyển qua internet banking",
                "content"         => "<strong>Số tài khoản:</strong> 044100066695<br><strong>Ngân hàng:</strong> Vietcombank",
            ]
        ];
    }

    public function getPaymentMethod()
    {
        return response()->json(['data' => $this->paymentMethod]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function transactionProcessor(Request $request, PaymentMoMoRequestValidator $moMoRequestValidator)
    {
        $input = $request->all();
        //        $moMoRequestValidator->validate($input);
        //        if (!filter_var($input['result_link'], FILTER_VALIDATE_URL)) {
        //            return $this->responseError(Message::get('V002', '`result_link`'));
        //        }

        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
            $group_id   = TM::getCurrentGroupId();
            $company_id = TM::getCurrentCompanyId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id   = $store->id;
                    $company_id = $store->company_id;
                }
            }
        }

        if (empty($store_id) || empty($company_id)) {
            return $this->responseError(Message::get('users.not-exist', Message::get("users")));
        }
        if (!empty($input['cart_id'])) {
            $cart = Cart::model()->where([
                'id' => $input['cart_id'],
            ])->first();
            if (empty($cart)) {
                return $this->responseError(Message::get('carts.not-exist', $input['cart_id']));
            }
            $order = $this->confirmOrder($cart);
            //           $userId   = $order['customer_id'];
            $price    = $order['total_price'];
            $order_id = $order['id'];
        }
        if (!empty($input['order_id'])) {
            $order = Order::model()->where('id', $input['order_id'])->where('payment_status', '!=', 1)->first();
            if (empty($order)) {
                return $this->responseError(Message::get('orders.not-exist', $input['order_id']));
            }
            //            $userId   = $order->customer_id;
            $price    = $order->total_price;
            $order_id = $order->id;
            try {
                DB::beginTransaction();
                $order->payment_method = $input['payment_method'];
                $order->save();
                DB::commit();
            } catch (\Exception $ex) {
                DB::rollBack();
                $response = TM_Error::handle($ex);
                return $this->response->errorBadRequest($response['message']);
            }
        }
        $param = [
            "notifyUrl" => url("/") . "/v0/momo/callback",
            "orderId"   => (string)$order_id,
            "amount"    => (string)$price,
            //            "orderInfo" => $input["title"],
            //            "requestId" => $input["requestId"],
            "extraData" => array_get($input, "extraData", ""),
        ];
        try {
            //            $url                = base64_encode($input['result_link']);
            //            $userSession        = UserSession::model()->where('user_id', $userId)->where('deleted', '0')->first();
            //            $device             = trim($userSession->device_token ?? null);
            //            $device             = "device=mweb";
            $param['returnUrl'] = $input['url'];

            $payment  = new TM_Payment();
            $response = $payment->requestMOMO($param);
            return response()->json(['status' => 'success', 'data' => $response], 200);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

    }

    public function shoppePayTransactionProcessor(Request $request)
    {
        $input    = $request->all();
        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
            $group_id   = TM::getCurrentGroupId();
            $company_id = TM::getCurrentCompanyId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id   = $store->id;
                    $company_id = $store->company_id;
                }
            }
        }
        if (empty($store_id) || empty($company_id)) {
            return $this->responseError(Message::get('users.not-exist', Message::get("users")));
        }
        DB::beginTransaction();
        try {
            if (!empty($input['cart_id'])) {
                $cart = Cart::model()->where([
                    'id' => $input['cart_id'],
                ])->first();
                if (empty($cart)) {
                    return $this->responseError(Message::get('carts.not-exist', $input['cart_id']));
                }
                $order = $this->confirmOrder($cart);
                //            $userId   = $order['customer_id'];
                $price    = $order['total_price'];
                $order_id = $order['id'];
            }
            if (!empty($input['order_id'])) {
                $order = Order::model()->where('id', $input['order_id'])->where('payment_status', "!=", 1)->first();
                if (empty($order)) {
                    return $this->responseError(Message::get('orders.not-exist', $input['order_id']));
                }
                //            $userId   = $order->customer_id;
                $price    = $order->total_price;
                $order_id = $order->id;
                try {
                    $order->payment_method = $input['payment_method'];
                    $order->save();
                } catch (\Exception $ex) {
                    DB::rollBack();
                    $response = TM_Error::handle($ex);
                    return $this->response->errorBadRequest($response['message']);
                }
            }
            $params   = [
                'order_id'    => $order_id,
                'total_price' => $price,
                'url'         => $input['url'],
                'device'      => $input['device']
            ];
            $payment  = new TM_Payment();
            $response = $payment->requestShoppepay($params);
            DB::commit();
            return response()->json(['status' => 'success', 'data' => $response], 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function vpBankVirtualAccountTransactionProcessor(Request $request)
    {
        $input    = $request->all();
        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
            $group_id   = TM::getCurrentGroupId();
            $company_id = TM::getCurrentCompanyId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id   = $store->id;
                    $company_id = $store->company_id;
                }
            }
        }
        if (empty($store_id) || empty($company_id)) {
            return $this->responseError(Message::get('users.not-exist', Message::get("users")));
        }
        try {
            DB::beginTransaction();

            if (!empty($input['cart_id'])) {
                $cart = Cart::model()->where([
                    'id' => $input['cart_id'],
                ])->first();
                if (empty($cart)) {
                    return $this->responseError(Message::get('carts.not-exist', $input['cart_id']));
                }

                $order = $this->confirmOrder($cart);
                //            $userId   = $order['customer_id'];
                $price    = $order['total_price'];
                $order_id = $order['id'];
            }
            if (!empty($input['order_id'])) {
                $order = Order::model()->where('id', $input['order_id'])->where('payment_status', "!=", 1)->first();
                if (empty($order)) {
                    return $this->responseError(Message::get('orders.not-exist', $input['order_id']));
                }
                //            $userId   = $order->customer_id;
                $price    = $order->total_price;
                $order_id = $order->id;


                $order->payment_method = $input['payment_method'];
                $order->save();
            }
            $params = [
                'order_id'    => $order_id,
                'total_price' => $price,
                // 'device' => $input['device']
            ];

            $payment  = new TM_Payment();
            $response = $payment->virtualAccount($params);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return response()->json(['status' => 'success', 'data' => $response], 200);

    }

    #Log VPBANK
    private function writeLogVpbank($data = null, $request = null, $response = null)
    {
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
    public function vpBankSessionTransactionProcessor()
    {
        // $msg = "Create Session";
        try {

            $payment       = new TM_Payment();
            $client        = new Client();
            $authorization = $payment->getAuthorization();
            $endPoint = env("API_VPBANK") . '/session';
            // $msg .= " - " . $endPoint;
            $this->writeLogVpbank('Session', $endPoint);
            $vp_bank       = $client->post($endPoint, [
                'headers' => [
                    'Content-Type' => 'application/json', 'authorization' => "Basic $authorization"
                ]
            ]);
            $vp_bank       = $vp_bank->getBody();
            $response      = !empty($vp_bank) ? json_decode($vp_bank, true) : [];
            // $msg .= " - " . $vp_bank;
            $this->writeLogVpbank('Session', $endPoint, $vp_bank);
            // TM::sendMessage("Create Session Success: $msg: ", $response);
            return response()->json(['status' => 'success', 'data' => $response], 200);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            // TM::sendMessage("Create Session Error: $msg: ", $response['message']);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function initiateAuthentication(Request $request, PaymentVpBankRequestValidator $vpBankRequestValidator)
    {
        $msg = "initiateAuthentication";
        $input = $request->all();
        $vpBankRequestValidator->validate($input);
        try {
            if (!empty($input['cart_id'])) {
                $userId = TM::getCurrentUserId();
                if ($userId) {
                    $cart = Cart::model()->where('user_id', $userId)->first();
                } else {
                    if (empty($input['session_id'])) {
                        $this->responseError(Message::get("V001", Message::get("session_id")));
                    }
                    $cart = Cart::model()->where('session_id', $input['session_id'])->first();
                }
                if (empty($cart)) {
                    return $this->responseError(Message::get('carts.not-exist', $input['cart_id']));
                }
                $order      = $this->confirmOrder($cart);
                $price      = $order['total_price'];
                $order_id   = $order['id'];
                $order_code = $order['code'];
            }
            if (!empty($input['order_id'])) {
                $id = $input['order_id'];
                $result = Str::contains($id, '-');
                if ($result) {
                    $idEx = explode('-', $id);
                    $id = end($idEx);
                }
                $order = Order::find($id);
                if (empty($order)) {
                    return $this->responseError(Message::get('orders.not-exist', $id));
                }
                $price      = $order->total_price;
                $order_id   = $order->id;
                $order_code = $order->code;
            }
            $sesion_id = $input['session_id'];
            $msg .= " - " . $sesion_id;
            $timeNow = date('YmdHis', time());
            $request_id =  CODE_SHOPPING_PAYMENT . $timeNow . "-" . $order_id;
            $price_fotmat = number_format($price) . "đ" ?? "";
            $url = $input['url'] ?? env('VPBANK_RETURN_STATUS_URL');
            $input = [
                'order_id'   => $timeNow . "-" . $order_id,
                'order_code' => $order_code,
                'amount'     => $price,
                'currency'   => 'VND',
                'session_id' => $sesion_id,
                'url'        => $input['url'],
                'request_id' => $request_id
            ];

            #Initiate Authentication
            $payment = new TM_Payment();
            $init = $payment->initAuthentication($input);
            if (!empty($init) && $init['transaction']['authenticationStatus'] == 'AUTHENTICATION_AVAILABLE') {
                $htmlInit = $init['authentication']['redirectHtml'] ?? null;
                if($htmlInit){
                    return response()->json(['status' => 'success','time' => $timeNow, 'orderId' => $order_id, 'request_id' => $request_id, 'htmlInit' => $htmlInit], 200);
                }
            }
            return $this->responseError('Xảy ra lỗi trong quá trình thanh toán. Vui lòng thử lại.');
            // return redirect($url . "?code=$order_code&price=$price_fotmat&payment_method=Thanh toán qua thẻ tín dụng/thẻ ghi nợ quốc tế&order_id=$order_id&status=fail");
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // $response = TM_Error::handle($ex);
            // TM::sendMessage("initiateAuthentication: $msg: ", $response['message']);
            $this->writeLogVpbank('initiateAuthentication RequestException | ' . $msg, json_encode($input), $e->getResponse()->getBody()->getContents());
            return $this->responseError($e->getResponse()->getBody()->getContents());
        } catch(\Exception $ex){
            $response = TM_Error::handle($ex);
            $this->writeLogVpbank('initiateAuthentication Error | ' . $msg, json_encode($input), $response['message']);
            return $this->responseError($response['message']);
        }
    }

    public function authenticatePayer(Request $request, PaymentVpBankRequestValidator $vpBankRequestValidator)
    {
        // $msg = "authenticatePayer";
        $input = $request->all();
        $vpBankRequestValidator->validate($input);
        if (empty($input['order_id'])) {
            return $this->responseError(Message::get("V001", Message::get("order_id")));
        }
        try {
            $order = Order::find($input['order_id']);
            if (empty($order)) {
                return $this->responseError(Message::get('orders.not-exist', $input['order_id']));
            }
            $initTime   = $input['time'] ?? "Unknown";
            $price      = $order->total_price;
            $order_id   = $order->id;
            $order_code = $order->code;
            $sesion_id  = $input['session_id'];
            $request_id = $input['request_id'];
            $price_fotmat = number_format($price) . "đ" ?? "";
            $url = $input['url'] ?? env('VPBANK_RETURN_STATUS_URL');
            // $msg .= " - " . $sesion_id;
            $input = [
                'order_id'   => $initTime . "-" . $order_id,
                'order_code' => $order_code,
                'amount'     => $price,
                'currency'   => 'VND',
                'session_id' => $sesion_id,
                'url'        => $input['url'],
                'request_id' => $request_id
            ];
 
            #Authenticate Payer
            $payment = new TM_Payment();
            $payer = $payment->authenticatePayer($input);

            if (!empty($payer) && $payer['result'] != 'ERROR' && !empty($payer['authentication']['redirectHtml'])) {
                $htmlPayer = $payer['authentication']['redirectHtml'] ?? null;
                if($htmlPayer){
                    $chkFrictionLess = Str::contains($htmlPayer, 'threedsFrictionLessRedirect');
                    if($chkFrictionLess){
                        #Xử lý thanh toán FrictionLess
                    }
                    return response()->json(['status' => 'success', 'htmlPayer' => $htmlPayer], 200);
                }
            }
            return $this->responseError($payer['message']['explanation'] ?? 'Xảy ra lỗi trong quá trình thanh toán. Vui lòng thử lại.');
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $this->writeLogVpbank('authenticatePayer RequestException', null, $e->getResponse()->getBody()->getContents(), null);
            return $this->responseError($e->getResponse()->getBody()->getContents());
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            $this->writeLogVpbank('authenticatePayer Error | ', null, $response['message'], null);
            return $this->responseError($response['message']);
        }
    }

    public function onePayTransactionProcessor(Request $request, PaymentOnePayRequestValidator $onePayRequestValidator)
    {
        $input = $request->all();
        $onePayRequestValidator->validate($input);

        if (!filter_var($input['result_link'], FILTER_VALIDATE_URL)) {
            return $this->responseError(Message::get('V002', '`result_link`'));
        }

        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
            $group_id   = TM::getCurrentGroupId();
            $company_id = TM::getCurrentCompanyId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id   = $store->id;
                    $company_id = $store->company_id;
                }
            }
        }

        if (empty($store_id) || empty($company_id)) {
            return $this->responseError(Message::get('users.not-exist', Message::get("users")));
        }

        $order = Order::model()->where([
            'code'           => $input['orderId'],
            'store_id'       => $store_id,
            'payment_status' => '0'
        ])->first();
        if (empty($order)) {
            return $this->responseError(Message::get('orders.not-exist', $input['orderId']));
        }

        $userId = $order->customer_id;

        $transactionId = strtoupper(uniqid($input["orderId"]));
        $param         = [
            "vpc_MerchTxnRef" => $transactionId,
            "vpc_OrderInfo"   => $input["orderId"],
            "vpc_Amount"      => $input["amount"],
            "Title"           => $input["title"],
            "extraData"       => array_get($input, "extraData", ""),
            "AgainLink"       => $input['AgainLink'] ?? $input['result_link'],
        ];
        try {
            $userSession = UserSession::model()->where('user_id', $userId)->where('deleted', '0')->first();
            $device      = trim($userSession->device_token ?? null);
            if (!empty($device)) {
                $device = "device/$device";
            }
            $result_url             = base64_encode($input['result_link']);
            $param['vpc_ReturnURL'] = url('/') . "/v0/onepay/returnUrl/$result_url/type/{$input['type']}/user/$userId/$device";

            $payment  = new TM_Payment();
            $response = $payment->requestOnePay($param);

            $wallet = Wallet::model()->where('user_id', $userId)->first();
            if (empty($wallet)) {
                $wallet                = new Wallet();
                $wallet->user_id       = $userId;
                $wallet->code          = strtoupper(hash('crc32', $userId) . hash('adler32', $userId));
                $wallet->balance       = 0;
                $wallet->total_pay     = 0;
                $wallet->total_deposit = 0;
                $wallet->pin           = null;
                $wallet->using_pin     = 0;
                $wallet->description   = "AUTO GENERATED BY SYSTEM";
                $wallet->is_active     = 1;
                $wallet->created_at    = date("Y-m-d H:i:s", time());
                $wallet->created_by    = $userId;
            }
            $wallet->current_signature = $response['signature'];
            $wallet->save();

            return response()->json(['status' => 'success', 'data' => $response], 200);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function zaloPayTransactionProcessor(Request $request, PaymentZaloRequestValidator $paymentZaloRequestValidator)
    {
        $input = $request->all();
        $paymentZaloRequestValidator->validate($input);
        if (!filter_var($input['url'], FILTER_VALIDATE_URL)) {
            return $this->responseError(Message::get('V002', '`result_link`'));
        }

        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
            $group_id   = TM::getCurrentGroupId();
            $company_id = TM::getCurrentCompanyId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id   = $store->id;
                    $company_id = $store->company_id;
                }
            }
        }

        if (empty($store_id) || empty($company_id)) {
            return $this->responseError(Message::get('users.not-exist', Message::get("users")));
        }

        // $order = Cart::model()->where([
        //     'id' => (int)$input['orderId']
        // ])->whereIn('payment_status', [0, -3])->first();
        // if (empty($order)) {
        //     return $this->responseError(Message::get('orders.not-exist', $input['orderId']));
        // }
        // $totalInfo  = $order->total_info;
        // $total      = end($totalInfo)['value'] ?? 0;
        // $userId     = $order->user_id ?? TM::getCurrentUserId();
        if (!empty($input['cart_id'])) {
            $cart = Cart::model()->where([
                'id' => $input['cart_id'],
            ])->first();
            if (empty($cart)) {
                return $this->responseError(Message::get('carts.not-exist', $input['cart_id']));
            }
            $order    = $this->confirmOrder($cart);
            $userId   = $order['customer_id'];
            $price    = $order['total_price'];
            $order_id = $order['id'];
            $address  = $order['shiping_address'];
            $phone    = $order['shiping_address_phone'];
        }
        if (!empty($input['order_id'])) {
            $order = Order::model()->where('id', $input['order_id'])->where('payment_status', '!=', 1)->first();
            if (empty($order)) {
                return $this->responseError(Message::get('orders.not-exist', $input['order_id']));
            }
            $userId   = $order->customer_id;
            $price    = $order->total_price;
            $order_id = $order->id;
            $address  = $order->shiping_address;
            $phone    = $order->shiping_address_phone;
            try {
                DB::beginTransaction();
                $order->payment_method = $input['payment_method'];
                $order->save();
                DB::commit();
            } catch (\Exception $ex) {
                DB::rollBack();
                $response = TM_Error::handle($ex);
                return $this->response->errorBadRequest($response['message']);
            }
        }
        $appTransId = date("ymd") . "*" . rand(100000, 999999) . "-" . strtoupper($order_id);
        $param      = [
            "app_user"     => "User-" . $order_id,
            "app_trans_id" => $appTransId,
            "amount"       => $price,
            "app_time"     => round(microtime(true) * 1000),
            "item"         => "[]",
            "order_type"   => "GOODS",
            "title"        => $input['title'] ?? "Thanh toán qua Zalo Pay",
            "description"  => "Nutifoodshop.com - Thanh toán đơn hàng #{$order_id}",
            "device_info"  => "",
            "currency"     => "VND",
            "bank_code"    => "zalopayapp",
            "phone"        => $phone,
            // "email"        => $order->customer_email,
            "address"      => $address
        ];
        try {
            $url = base64_encode($input['url']);
            //            $url = base64_encode("https://dev.nutifoodshop.com/checkout/checkout-payment");
            $userSession         = UserSession::model()->where('user_id', $userId)->where('deleted', '0')->first();
            $device              = trim($userSession->device_token ?? null);
            $device              = "device=$device";
            $param['embed_data'] = ['redirecturl' => url('/') . "/v0/zalopay/returnUrl/$url?user_id=$userId&$device"];
            $param['embed_data'] = json_encode($param['embed_data']);
            $now                 = date('Y-m-d H:i:s', time());
            $newdate             = date('Y-m-d H:i:s', strtotime('+5 minute', strtotime($now)));
            $uid_payment         = md5(uniqid(rand(), true));
            $order               = Order::model()->where("id", $order_id)->first();
            $order->app_trans_id = $appTransId;
            $order->time_qr_zalo = strtotime($newdate);
            $order->uid_payment  = $uid_payment;
            $order->save();

            $payment     = new TM_Payment();
            $response    = $payment->requestZalo($param);
            $response    = json_decode($response, true);
            $response_qr = [
                'title'               => "Thanh toán bằng ví " . PAYMENT_METHOD_NAME[PAYMENT_METHOD_ZALO] ?? null,
                'url_payment_qr'      => $response['order_url'],
                'url_payment_qr_app'  => $response['order_url'],
                'time_payment_qr'     => $newdate,
                'payment_method'      => PAYMENT_METHOD_ZALO,
                'payment_method_name' => PAYMENT_METHOD_NAME[PAYMENT_METHOD_ZALO] ?? null,
                'order_code'          => $order->code ?? null,
                'order_id'            => $order->id ?? null,
                'total_price'         => $order->total_price,
                'thumbnail_asset'     => 'zalo-pay.png',
                'uid_payment'         => $uid_payment,
            ];
            return response()->json(['status' => 'success', 'data' => $response_qr], 200);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function vnPayTransactionProcessor(Request $request, PaymentVNPayRequestValidator $onePayRequestValidator)
    {
        $input = $request->all();
        $onePayRequestValidator->validate($input);
        if (!filter_var($input['url'], FILTER_VALIDATE_URL)) {
            return $this->responseError(Message::get('V002', '`result_link`'));
        }
        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
            $group_id   = TM::getCurrentGroupId();
            $company_id = TM::getCurrentCompanyId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id   = $store->id;
                    $company_id = $store->company_id;
                }
            }
        }

        if (empty($store_id) || empty($company_id)) {
            return $this->responseError(Message::get('users.not-exist', Message::get("users")));
        }
        try {
            DB::beginTransaction();
            if (!empty($input['cart_id'])) {
                $userId = TM::getCurrentUserId();
                if ($userId) {
                    $cart = Cart::model()->where('user_id', $userId)->first();
                } else {
                    if (empty($input['session_id'])) {
                        $this->responseError(Message::get("V001", Message::get("session_id")));
                    }
                    $cart = Cart::model()->where('session_id', $input['session_id'])->first();
                }
                if (empty($cart)) {
                    return $this->responseError(Message::get('carts.not-exist', $input['cart_id']));
                }
                $order    = $this->confirmOrder($cart);
                $userId   = $order['customer_id'];
                $price    = $order['total_price'];
                $order_id = $order['id'];
                $address  = $order['shiping_address'];
                $phone    = $order['shiping_address_phone'];
            }
            if (!empty($input['order_id'])) {
                $order = Order::model()->where('id', $input['order_id'])->where('payment_status', '!=', 1)->first();
                if (empty($order)) {
                    return $this->responseError(Message::get('orders.not-exist', $input['order_id']));
                }
                $userId   = $order->customer_id;
                $price    = $order->total_price;
                $order_id = $order->id;
                $address  = $order->shiping_address;
                $phone    = $order->shiping_address_phone;
                try {
                    $order->payment_method = $input['payment_method'];
                    $order->save();
                    DB::commit();
                } catch (\Exception $ex) {
                    DB::rollBack();
                    $response = TM_Error::handle($ex);
                    return $this->response->errorBadRequest($response['message']);
                }
            }
            $randTxnRef = rand(100000, 999999) . "-" . $order_id;
            $url        = base64_encode($input['url']);
            $param      = [
                "vnp_Version"    => "2.0.1",
                "vnp_TmnCode"    => env("VNPAY_TMNCODE"),
                "vnp_Amount"     => $price * 100,
                "vnp_Command"    => "pay",
                "vnp_CreateDate" => date('YmdHis'),
                "vnp_CurrCode"   => "VND",
                "vnp_IpAddr"     => $request->ip(),
                "vnp_Locale"     => "vn",
                "vnp_OrderInfo"  => $order_id,
                "vnp_OrderType"  => '100000',
                "vnp_ReturnURL"  => url('/') . "/v0/vnpay/returnUrl/$url",
                "vnp_TxnRef"     => $randTxnRef,
            ];
            if (!empty($input['vnp_BankCode'])) {
                $param['vnp_BankCode'] = $input['vnp_BankCode'];
            }
            $payment           = new TM_Payment();
            $response          = $payment->requestVNPay($param);
            $order             = Order::model()->where('id', $order_id)->first();
            $order->vnp_txnref = $randTxnRef;
            // $order->log_payment = json_encode($response);
            $order->save();
            DB::commit();
            return response()->json(['status' => 'success', 'data' => $response], 200);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
    }

    public function returnVpBank(Request $request)
    {
        $input = $request->all();
        $this->writeLogVpbank('returnVpBank', null, json_encode($input));

        $amount        = $input['amount'];
        $currency      = $input['currency'];
        $secure        = $input['check'];
        $session_id    = $input['session'];
        $url           = base64_decode($input['url']);
        $order_id      = $input['order_id'];
        $code          = $input['code'];
        $price_fotmat  = number_format($amount) . "đ" ?? "";
        $client        = new Client();
        $payment       = new TM_Payment();
        $authorization = $payment->getAuthorization();
        try {
            $this->logPaymentRequest($input['order_id'] ?? null, PAYMENT_METHOD_VPB, $input);
        } catch (\Exception $exception) {
        }

        $input = [
            'order_id'   => $order_id,
            'order_code' => $code,
            'amount'     => $amount,
            'currency'   => $currency,
            'session_id' => $session_id,
            'url'        => $url,
            'request_id' => $secure
        ];
        $param = [
            "apiOperation"  => "PAY",
            "authentication"    => [
                'transactionId' => $secure
            ],
            "order"         => [
                'amount'   => $amount,
                'currency' => $currency,
                'reference' => $order_id,
            ],
            "session"       => [
                'id'      => $session_id
            ],
            "transaction"       => [
                'reference'    => $order_id,
            ]
        ];
        $this->writeLogVpbank('PAY', json_encode($input));
        $tranIdPay = "PAY$secure";
        try {
            $vp_bank = $client->put(env("API_VPBANK") . "/order/$order_id/transaction/$tranIdPay", [
                'headers' => [
                    'Content-Type' => 'application/json', 'authorization' => "Basic $authorization"
                ],
                'body'    => json_encode($param)
            ]);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $this->writeLogVpbank('PAY Error', json_encode($input), $e->getResponse()->getBody()->getContents());
            // return $this->responseError($e->getResponse()->getBody()->getContents());
            return redirect($url . "?code={$code}&price=$price_fotmat&payment_method=Thanh toán qua thẻ tín dụng/thẻ ghi nợ quốc tế&order_id=$order_id&status=fail");
        }
        // TM::sendMessage("VPBANK PAY REQ - $order_id: ", $param);

        $vp_bank  = $vp_bank->getBody();
        $response = !empty($vp_bank) ? json_decode($vp_bank, true) : [];

        $this->writeLogVpbank('PAY Res', json_encode($input), $vp_bank);

        if ($response && $response['result'] == 'SUCCESS' && ($response['transaction']['authenticationStatus'] ?? null) == 'AUTHENTICATION_SUCCESSFUL') {
            $order    = Order::where('code',$code)->first();

            if ($order && $order->payment_status == 1) {
                return redirect($url . "?distributor_code={$order->distributor_code}&code={$order->code}&full_name={$order->customer_name}&status=success");
            }
           
            $param_ntf_histori = [
                'title'      => $response['result'] ?? null,
                'body'       => Message::get("V021", $order_id),
                'message'    => $response['result'] ?? null,
                'type'       => "VPB",
                'extra_data' => '', // anyType
                'receiver'   => $input['device']['browser'] ?? null,
                'action'     => 1,
                'item_id'    => $order_id,
            ];
            try {
                DB::beginTransaction();
                $order->payment_method = PAYMENT_METHOD_VPB;
                $order->payment_status = 1;
                $order->status_crm     = ORDER_STATUS_CRM_APPROVED;
                $order->payment_code   = $response['transaction']['id'] ?? null;
                $order->save();
                $notificationHistoryModel = new NotificationHistoryModel();
                $notificationHistoryModel->create($param_ntf_histori);

                try {
                    $syncDMS = OrderSyncDMS::dataOrder(array($order->code), "C");
                    if (!empty($syncDMS)) {
                        $pushOrderDms = OrderSyncDMS::callApiDms($syncDMS, "CREATE-ORDER");
                        if (!empty($pushOrderDms['errors'])) {
                            foreach ($pushOrderDms['errors'] as $item) {
                                Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $syncDMS ?? [], "CREATE-ORDER", 0, $item);
                            }
                        } else {
                            if (!empty($pushOrderDms)) {
                                Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                            }
                            if (empty($pushOrderDms)) {
                                Log::logSyncDMS($order->code, "Connection Error", $syncDMS ?? [], "CREATE-ORDER", 0, $pushOrderDms);
                            }
                        }
                    }
                    // Order::where('code', $order->code)->update(['log_order_dms' => json_encode($syncDMS)]);
                } catch (\Exception $exception) {
                    Log::logSyncDMS($order->code, $exception->getMessage(), $syncDMS ?? [], "CREATE-ORDER", 0, null);
                }

                // Create Payment History
                $status              = isset($response['result']) && $response['result'] == 'SUCCESS' ? PAYMENT_STATUS_SUCCESS : PAYMENT_STATUS_FAIL;
                $paymentHistoryModel = new PaymentHistoryModel();
                $paymentHistoryModel->create([
                    'transaction_id' => $response['transaction']['id'],
                    'date'           => date("Y-m-d H:i:s"),
                    'type'           => 'PAYMENT',
                    'method'         => PAYMENT_METHOD_VPB,
                    'status'         => $status,
                    'content'        => $response['result'] ?? null,
                    'total_pay'      => $response['transaction']['amount'],
                    'balance'        => $input['balance'] ?? null,
                    'user_id'        => $input['user_id'] ?? null,
                    'data'           => json_encode($response),
                    'note'           => $response['result'] ?? null
                ]);
                $paramVPB              = [
                    'transaction_id'        => $response['transaction']['id'],
                    'order_id'              => $order->id,
                    'master_account_number' => $response['sourceOfFunds']['provided']['card']['number'] ?? null,
                    'collect_ammount'       => $response['transaction']['amount'],
                    'type'                  => PAYMENT_METHOD_VPB,
                    'payer_name'            => $response['sourceOfFunds']['provided']['card']['nameOnCard'] ?? null,
                    'type_cart'             => $response['sourceOfFunds']['provided']['card']['scheme'] ?? null,
                ];
                $paymentVirtualAccount = new PaymentVirtualAccount();
                $paymentVirtualAccount->create($paramVPB);
                DB::commit();
                #CREATE[ACCESSTRADE]
                try {
                    $accesstrade_id = $order->access_trade_id;
                    $click_id       = $order->access_trade_click_id;
                    Accesstrade::create($order, $accesstrade_id, $click_id);
                    $status = ORDER_STATUS_APPROVED;
                    $reason = ORDER_STATUS_NEW_NAME['APPROVED'];
                    Accesstrade::update($order, $status, $reason);
                } catch (\Exception $e) {
                }
                return redirect($url . "?distributor_code={$order->distributor_code}&code={$order->code}&full_name={$order->customer_name}&status=success");
            } catch (\Exception $ex) {
                DB::rollBack();
                $response = TM_Error::handle($ex);
                $this->writeLogVpbank('PAY Error', json_encode($input), $response['message']);
                return redirect($url . "?code={$code}&price=$price_fotmat&payment_method=Thanh toán qua thẻ tín dụng/thẻ ghi nợ quốc tế&order_id=$order_id&status=fail");
            }
        }
        return redirect($url . "?code={$code}&price=$price_fotmat&payment_method=Thanh toán qua thẻ tín dụng/thẻ ghi nợ quốc tế&order_id=$order_id&status=fail");
    }

    public function returnPaymentVirtualAccount(Request $request)
    {
        $input = $request->all();
        return $input;
    }

    public function returnOnePay($url, $type, $user_id, Request $request)
    {
        $url   = base64_decode($url);
        $input = $request->all();
        if ((isset($input['vpc_TxnResponseCode']) && $input['vpc_TxnResponseCode'] != 0) && (empty($input['vpc_Command']) || empty($input['vpc_CurrencyCode']) || empty($input['vpc_Merchant']))) {
            $data['url']                 = $url;
            $data['localMessage']        = "Thanh toán thất bại";
            $data['title']               = "Không thể thanh toán";
            $data['error_discription']   = $input['vpc_Message'];
            $data['error_code']          = $input['vpc_TxnResponseCode'];
            $data['error_vpc_orderInfo'] = $input['vpc_OrderInfo'];
            return redirect($url . "?status=fail");
            // return view("payment/onepay_result", $data);
        } else {
            $result        = $this->returnOnePayAccess($request, $user_id, $type);
            $result['url'] = $url;
            return redirect($url . "?status=" . $result['status'] . "&message" . $result['message']);
            // return view("payment/onepay_result", $result);
        }

    }

    public function returnOnePayDevice($url, $type, $user_id, $device, Request $request)
    {
        $input = $request->all();
        if ((isset($input['vpc_TxnResponseCode']) && $input['vpc_TxnResponseCode'] != 0) && (empty($input['vpc_Command']) || empty($input['vpc_CurrencyCode']) || empty($input['vpc_Merchant']))) {
            $data['localMessage']        = "Thanh toán thất bại";
            $data['title']               = "Không thể thanh toán";
            $data['error_discription']   = $input['vpc_Message'];
            $data['error_code']          = $input['vpc_TxnResponseCode'];
            $data['error_vpc_orderInfo'] = $input['vpc_OrderInfo'];
            return view("payment/onepay_result", $data);
        } else {
            $result = $this->returnOnePayAccess($request, $user_id, $type, $device);
            return view("payment/onepay_result", $result);
        }
    }

    public function returnMomo($url, Request $request)
    {
        $input        = $request->all();
        $url_redirect = base64_decode($url);
        $partnerCode  = env("MOMO_PARTNER_CODE", null);
        $accessKey    = env("MOMO_ACCESS_KEY", null);
        $secretKey    = env("MOMO_SECRET_KEY");
        $requestId    = time() . "";
        $requestType  = "transactionStatus";
        $order_id     = $input['orderId'];
        $rawHash      = "accessKey=" . $accessKey . "&orderId=" . $order_id . "&partnerCode=" . $partnerCode . "&requestId=" . $requestId;
        $signature    = hash_hmac("sha256", $rawHash, $secretKey);
        $params       = [
            'partnerCode' => $partnerCode,
            //            'accessKey' => $accessKey,
            'requestId'   => $requestId,
            'orderId'     => $order_id,
            //            'requestType' => $requestType,
            'signature'   => $signature,
            "lang"        => "vi"
        ];
        try {
            $this->logPaymentRequest($input['orderId'] ?? null, PAYMENT_METHOD_SPP, $input);
        } catch (\Exception $exception) {
        }
        $endPoint     = env("MOMO_ENDPOINT", null) . "/api/query";
        $client       = new Client();
        $momoResponse = $client->post($endPoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => json_encode($params)]);
        $momoResponse = $momoResponse->getBody();
        $response     = !empty($momoResponse) ? json_decode($momoResponse, true) : [];
        $title        = empty($response['message']) ? "Có lỗi trong quá trình thanh toán. Vui lòng liên hệ quản trị viên" : $response['message'];
        $param        = [
            'title'      => $title,
            'body'       => Message::get("V021", $order_id),
            'message'    => $title,
            'type'       => "MOMO",
            'extra_data' => '', // anyType
            'receiver'   => $input['device'] ?? null,
            'action'     => 1,
            'item_id'    => $order_id,
        ];
        try {
            $array_order    = explode("-", $response['orderId']);
            $id             = end($array_order);
            $order          = Order::model()->where('id', $id)->first();
            $payment_method = PAYMENT_METHOD_NAME[$order->payment_method] ?? "";
            $price_fotmat   = number_format($order->total_price) . "đ" ?? "";
            DB::beginTransaction();
            if ($response['resultCode'] == 0) {
                if ($order->payment_status != 1) {
                    $order->payment_method = PAYMENT_METHOD_MOMO;
                    $order->payment_status = 1;
                    $order->status_crm     = ORDER_STATUS_CRM_APPROVED;
                    $order->payment_code   = $response['transId'] ?? null;
                    $order->save();

                    try {
                        $syncDMS = OrderSyncDMS::dataOrder(array($order->code), "C");
                        if (!empty($syncDMS)) {
                            $pushOrderDms = OrderSyncDMS::callApiDms($syncDMS, "CREATE-ORDER");
                            if (!empty($pushOrderDms['errors'])) {
                                foreach ($pushOrderDms['errors'] as $item) {
                                    Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $syncDMS ?? [], "CREATE-ORDER", 0, $item);
                                }
                            } else {
                                if (!empty($pushOrderDms)){
                                    Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                                }
                                if (empty($pushOrderDms)){
                                    Log::logSyncDMS($order->code, "Connection Error", $syncDMS ?? [], "CREATE-ORDER", 0, $pushOrderDms);
                                }
                                //                                Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                            }

                        }
                        Order::where('code', $order->code)->update(['log_order_dms' => json_encode($syncDMS)]);
                    } catch (\Exception $exception) {
                        Log::logSyncDMS($order->code, $exception->getMessage(), $syncDMS ?? [], "CREATE-ORDER", 0, null);
                    }
                }
            }
            // Create Notification History
            $notificationHistoryModel = new NotificationHistoryModel();
            $notificationHistoryModel->create($param);

            // Create Payment History
            $status              = isset($response['resultCode']) && $response['resultCode'] == 0 ? PAYMENT_STATUS_SUCCESS : PAYMENT_STATUS_FAIL;
            $paymentHistoryModel = new PaymentHistoryModel();
            $paymentHistoryModel->create([
                'transaction_id' => $response['transId'],
                'date'           => date("Y-m-d H:i:s"),
                'type'           => PAYMENT_TYPE_PAYMENT,
                'method'         => PAYMENT_METHOD_MOMO,
                'status'         => $status,
                'content'        => MOMO_CODE_MSG[$response['resultCode']] ?? null,
                'total_pay'      => $response['amount'],
                'balance'        => $input['balance'] ?? null,
                'user_id'        => $input['user_id'] ?? null,
                'data'           => json_encode($response),
                'note'           => $response['message'] ?? null,
            ]);
            DB::commit();
            if ($response['resultCode'] == 0) {
                return redirect($url_redirect . "?distributor_code={$order->distributor_code}&code={$order->code}&full_name={$order->customer_name}&status=success");
            }
            if (($response['resultCode']) && $response['resultCode'] != 0) {
                //                if ($order->payment_status != 1) {
                //                    $order->payment_status = 2;
                //                    $order->save();
                //                }
                return redirect($url_redirect . "?code={$order->code}&price=$price_fotmat&payment_method=$payment_method&order_id=$id&status=fail");
            }
        } catch (\Exception $ex) {
            DB::rollBack();
            return $ex->getMessage();;
        }
        return redirect($url_redirect . "?distributor_code={$order->distributor_code}&code={$order->code}&full_name={$order->customer_name}&status=success");
    }

    public function returnShopeePay($url, Request $request)
    {
        $input  = $request->all();
        $fe_url = base64_decode($url);
        $param  = [
            "request_id"       => $input['request_id'],
            "reference_id"     => $input['reference'],
            "transaction_type" => 13,
            "merchant_ext_id"  => "Nutifood0123",
            "store_ext_id"     => "Nutifood123",
            "amount"           => (int)$input['price']
        ];
        try {
            $this->logPaymentRequest($input['reference'] ?? null, PAYMENT_METHOD_SPP, $input);
        } catch (\Exception $exception) {
        }
        $dataHash            = (json_encode($param));
        $base64_encoded_hash = base64_encode(hash_hmac('sha256', $dataHash, env("SHOPPEPAY_SECURE_SECRET"), true));
        $client              = new Client();
        for ($i = 1; $i <= 6; $i++) {
            $shoppe_response = $client->post(env("API_SHOPPEPAY") . "/v3/merchant-host/transaction/check", [
                'headers' => ['Content-Type' => 'application/json', 'X-Airpay-ClientId' => 11000067, 'X-Airpay-Req-H' => $base64_encoded_hash
                ],
                'body'    => json_encode($param),
            ]);
            $shoppe_response = $shoppe_response->getBody();
            $response        = !empty($shoppe_response) ? json_decode($shoppe_response, true) : [];
            if ($response) {
                $array_order    = explode("-", $response['transaction']['reference_id']);
                $id_order       = end($array_order);
                $order          = Order::model()->where('id', $id_order)->first();
                $payment_method = PAYMENT_METHOD_NAME[$order->payment_method] ?? "";
                $price_fotmat   = number_format($order->total_price) . "đ" ?? "";
                if ($response['transaction']['status'] == 2) {
                    $back_url = $fe_url . "?code={$order->code}&price=$price_fotmat&payment_method=$payment_method&order_id=$id_order&status=fail";
                }
                $param_ntf_histori = [
                    'title'      => $response['debug_msg'] ?? null,
                    'body'       => Message::get("V021", $id_order),
                    'message'    => $response['debug_msg'] ?? null,
                    'type'       => "SPP",
                    'extra_data' => '', // anyType
                    'receiver'   => $input['device'] ?? null,
                    'action'     => 1,
                    'item_id'    => $id_order,
                ];
                DB::beginTransaction();
                try {
                    if ($response['transaction']['status'] == 3) {
                        if (!empty($order) && $order->payment_status != 1) {
                            $order->payment_method = PAYMENT_METHOD_SPP;
                            $order->payment_status = 1;
                            $order->status_crm     = ORDER_STATUS_CRM_APPROVED;
                            $order->payment_code   = $response['reference_id'] ?? null;
                            $order->save();
                            $notificationHistoryModel = new NotificationHistoryModel();
                            $notificationHistoryModel->create($param_ntf_histori);

                            try {
                                $syncDMS = OrderSyncDMS::dataOrder(array($order->code), "C");
                                if (!empty($syncDMS)) {
                                    $pushOrderDms = OrderSyncDMS::callApiDms($syncDMS, "CREATE-ORDER");
                                    if (!empty($pushOrderDms['errors'])) {
                                        foreach ($pushOrderDms['errors'] as $item) {
                                            Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $syncDMS ?? [], "CREATE-ORDER", 0, $item);
                                        }
                                    } else {
                                        if (!empty($pushOrderDms)){
                                            Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                                        }
                                        if (empty($pushOrderDms)){
                                            Log::logSyncDMS($order->code, "Connection Error", $syncDMS ?? [], "CREATE-ORDER", 0, $pushOrderDms);
                                        }
                                        //                                        Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                                    }

                                }
                                Order::where('code', $order->code)->update(['log_order_dms' => json_encode($syncDMS)]);
                            } catch (\Exception $exception) {
                                Log::logSyncDMS($order->code, $exception->getMessage(), $syncDMS ?? [], "CREATE-ORDER", 0, null);
                            }
                        }
                        $back_url = $fe_url . "?distributor_code={$order->distributor_code}&code={$order->code}&full_name={$order->customer_name}&status=success";
                    }
                    // Create Payment History
                    $status              = isset($response['transaction']['status']) && $response['transaction']['status'] == 3 ? PAYMENT_STATUS_SUCCESS : PAYMENT_STATUS_FAIL;
                    $paymentHistoryModel = new PaymentHistoryModel();
                    $paymentHistoryModel->create([
                        'transaction_id' => $response['transaction']['reference_id'],
                        'date'           => date("Y-m-d H:i:s"),
                        'type'           => 'PAYMENT',
                        'method'         => PAYMENT_METHOD_SPP,
                        'status'         => $status,
                        'content'        => SHOPEE_PAY_STATUS[$response['transaction']['status']] ?? null,
                        'total_pay'      => $response['transaction']['amount'],
                        'balance'        => $input['balance'] ?? null,
                        'user_id'        => $input['user_id'] ?? null,
                        'data'           => json_encode($response),
                        'note'           => $response['transaction']['status'] ?? null
                    ]);
                    DB::commit();
                } catch (\Exception $ex) {
                    DB::rollBack();
                    return $ex->getMessage();;
                }
                if ($response['transaction']['status'] == 4) {
                    $back_url = $fe_url . "?code={$order->code}&price=$price_fotmat&payment_method=$payment_method&order_id=$id_order&status=fail";
                }
                break;
            } else(sleep(5));
        }
        return redirect($back_url);
    }

    public function returnMomoPayCallback(Request $request)
    {
        $input = $request->all();
        try {
            $this->logPaymentRequest($input["orderId"] ?? null, PAYMENT_METHOD_MOMO, $input);
        } catch (\Exception $exception) {
        }
        $partnerCode = env("MOMO_PARTNER_CODE", null);
        $accessKey   = env("MOMO_ACCESS_KEY", null);
        $serectkey   = env("MOMO_SECRET_KEY");
        $orderId     = $input["orderId"];
        //        $localMessage = $input["localMessage"];
        $message      = $input["message"];
        $transId      = $input["transId"];
        $orderInfo    = $input["orderInfo"];
        $amount       = $input["amount"];
        $errorCode    = $input["resultCode"];
        $responseTime = $input["responseTime"];
        $requestId    = $input["requestId"];
        $extraData    = $input["extraData"] ?? "";
        $payType      = $input["payType"];
        $orderType    = $input["orderType"];
        $m2signature  = $input["signature"]; //MoMo signature
        $rawHash      = "accessKey=" . $accessKey . "&amount=" . $amount . "&extraData=" . $extraData . "&message=" . $message . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo . "&orderType=" . $orderType . "&partnerCode=" . $partnerCode . "&payType=" . $payType
            . "&requestId=" . $requestId . "&responseTime=" . $responseTime . "&resultCode=" . $errorCode . "&transId=" . $transId;
        //        $rawHash = "partnerCode=" . $partnerCode . "&accessKey=" . $accessKey . "&requestId=" . $requestId . "&amount=" . $amount . "&orderId=" . $orderId . "&orderInfo=" . $orderInfo .
        //            "&orderType=" . $orderType . "&transId=" . $transId . "&message=" . $message . "&localMessage=" . $localMessage . "&responseTime=" . $responseTime . "&errorCode=" . $errorCode .
        //            "&payType=" . $payType . "&extraData=" . $extraData;
        $partnerSignature = hash_hmac("sha256", $rawHash, $serectkey);
        if ($m2signature == $partnerSignature) {
            if ($errorCode == 0) {
                $array_order = explode("-", $orderId);
                $order       = end($array_order);
                //                $title = empty($localMessage) ? "Có lỗi trong quá trình thanh toán. Vui lòng liên hệ quản trị viên" : $localMessage;
                //                $param = [
                //                    'title' => $title,
                //                    'body' => Message::get("V021", $order),
                //                    'message' => $title,
                //                    'type' => "MOMO",
                //                    'extra_data' => '', // anyType
                //                    'receiver' => $input['device'] ?? null,
                //                    'action' => 1,
                //                    'item_id' => $order,
                //                ];
                DB::beginTransaction();
                $result_order = Order::find($order);
                if (empty($result_order)) {
                    return true;
                }
                $result_order->payment_method = PAYMENT_METHOD_MOMO;
                $result_order->payment_status = 1;
                $result_order->status_crm     = ORDER_STATUS_CRM_APPROVED;
                $result_order->payment_code   = $transId ?? null;
                $result_order->log_qr_payment = json_encode($input) ?? null;
                $result_order->save();

                try {
                    $syncDMS = OrderSyncDMS::dataOrder(array($result_order->code), "C");
                    if (!empty($syncDMS)) {
                        $pushOrderDms = OrderSyncDMS::callApiDms($syncDMS, "CREATE-ORDER");
                        if (!empty($pushOrderDms['errors'])) {
                            foreach ($pushOrderDms['errors'] as $item) {
                                Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $syncDMS ?? [], "CREATE-ORDER", 0, $item);
                            }
                        } else {
                            if (!empty($pushOrderDms)){
                                Log::logSyncDMS($result_order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                            }
                            if (empty($pushOrderDms)){
                                Log::logSyncDMS($result_order->code, "Connection Error", $syncDMS ?? [], "CREATE-ORDER", 0, $pushOrderDms);
                            }
                            //                            Log::logSyncDMS($result_order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                        }

                    }
                    // Order::where('code', $result_order->code)->update(['log_order_dms' => json_encode($syncDMS)]);
                } catch (\Exception $exception) {
                    Log::logSyncDMS($result_order->code, $exception->getMessage(), $syncDMS ?? [], "CREATE-ORDER", 0, null);
                }

                // Create Notification History
                //                $notificationHistoryModel = new NotificationHistoryModel();
                //                $notificationHistoryModel->create($param);
                ////                        // Create Payment History
            }
            //            $status = isset($errorCode) && (int)$errorCode == 0 ? PAYMENT_STATUS_SUCCESS : PAYMENT_STATUS_FAIL;
            //            $paymentHistoryModel = new PaymentHistoryModel();
            //            $paymentHistoryModel->create([
            //                'transaction_id' => $transId ?? null,
            //                'date' => $responseTime,
            //                'type' => PAYMENT_TYPE_PAYMENT,
            //                'method' => PAYMENT_METHOD_MOMO,
            //                'status' => $status ?? null,
            //                'content' => MOMO_CODE_MSG[(int)$errorCode] ?? null,
            //                'total_pay' => $amount ?? null,
            //                'balance' => null,
            //                'user_id' => null,
            //                'data' => json_encode($input),
            //                'note' => $localMessage ?? null,
            //            ]);
            #CREATE[ACCESSTRADE]
            try {
                $accesstrade_id = $result_order->access_trade_id;
                $click_id       = $result_order->access_trade_click_id;
                Accesstrade::create($result_order, $accesstrade_id, $click_id);
                $status = ORDER_STATUS_APPROVED;
                $reason = ORDER_STATUS_NEW_NAME['APPROVED'];
                Accesstrade::update($order, $status, $reason);
            } catch (\Exception $e) {
            }
            DB::commit();
        }
        return $input;
    }

    public function returnZaloPay($url, Request $request)
    {
        $url            = base64_decode($url);
        $input          = $request->all();
        $order_id       = explode("-", $input['apptransid']);
        $order          = Order::model()->where('id', $order_id[1])->first();
        $payment_method = PAYMENT_METHOD_NAME[$order->payment_method] ?? "";
        $price_fotmat   = number_format($order->total_price) . "đ" ?? "";
        try {
            $this->logPaymentRequest($input['apptransid'] ?? null, PAYMENT_METHOD_ZALO, $input);
        } catch (\Exception $exception) {
        }
        if (empty($input['apptransid'])) {
            return redirect($url . "?order_id=$order->id&code=$order->code&price=$price_fotmat&payment_method=$payment_method&status=fail&message=`apptransid`%20is%20empty");
        }


        if (empty($order_id[1])) {
            return redirect($url . "?order_id=$order->id&code=$order->code&price=$price_fotmat&payment_method=$payment_method&status=fail&message=`apptransid`%20is%20empty");
        }
        if ($input['status'] != 1) {
            return redirect($url . "?order_id=$order->id&code=$order->code&price=$price_fotmat&payment_method=$payment_method&status=fail");
        }
        $param = [
            'title'      => "Thanh toán qua Zalo pay",
            'body'       => Message::get("V021", $order_id[1]),
            'message'    => "Thanh toán qua Zalo pay",
            'type'       => "ZALO",
            'extra_data' => '', // anyType
            'receiver'   => $input['device'],
            'action'     => 1,
            'item_id'    => $order_id[1],
        ];
        try {
            DB::beginTransaction();
            $order = Order::model()->where('id', $order_id[1])->first();
            //            if (!empty($order)) {
            //                $order->payment_method = PAYMENT_METHOD_ZALO;
            //                $order->payment_status = 1;
            //                $order->payment_code = $input["apptransid"] ?? null;
            //                $order->status_crm = ORDER_STATUS_CRM_APPROVED;
            //                $order->save();
            //            }
            // Create Notification History
            $notificationHistoryModel = new NotificationHistoryModel();
            $notificationHistoryModel->create($param);
            $total = $order->total_price;
            // Create Payment History
            $status              = isset($input['status']) && $input['status'] == 1 ? PAYMENT_STATUS_SUCCESS : PAYMENT_STATUS_FAIL;
            $paymentHistoryModel = new PaymentHistoryModel();
            $paymentHistoryModel->create([
                'transaction_id' => $input['apptransid'],
                'date'           => date("Y-m-d H:i:s"),
                'type'           => PAYMENT_TYPE_PAYMENT,
                'method'         => PAYMENT_METHOD_ZALO,
                'status'         => $status,
                'content'        => "Thanh toán thành công",
                'total_pay'      => $total,
                'balance'        => $input['balance'] ?? null,
                'user_id'        => !empty($input['user_id']) ? $input['user_id'] : null,
                'data'           => json_encode($input),
                'note'           => "Thanh toán thành công",
            ]);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            return redirect($url . "?order_id=$order->id&code=$order->code&price=$price_fotmat&payment_method=$payment_method&status=fail&message=" . $ex->getMessage());
        }
        return redirect($url . "?status=success&code={$order->code}&distributor_code=$order->distributor_code&full_name=$order->customer_name");
    }

    public function returnZaloPayCallback(Request $request)
    {
        $result = [];
        $input  = $request->all();;
        try {
            $this->logPaymentRequest(json_decode($input['data'], true)['app_trans_id'] ?? null, PAYMENT_METHOD_ZALO, $input);
        } catch (\Exception $exception) {
        }
        try {
            $key2 = env('ZALO_KEY2');
            $mac  = hash_hmac("sha256", $input["data"], $key2);

            $requestMac = $input["mac"];
            if (strcmp($mac, $requestMac) != 0) {
                // callback không hợp lệ
                $result["return_code"]    = -1;
                $result["return_message"] = "mac not equal";
            } else {
                $result["return_code"]    = 1;
                $result["return_message"] = "success";
                $dataJson                 = json_decode($input['data'], true);
                $dataArray                = explode("-", $dataJson['app_trans_id']);
                $order_id                 = end($dataArray);
                $order                    = Order::find($order_id);
                $order->payment_method    = PAYMENT_METHOD_ZALO;
                $order->payment_status    = 1;
                $order->payment_code      = $dataJson['app_trans_id'] ?? null;
                $order->status_crm        = ORDER_STATUS_CRM_APPROVED;
                //                $order->is_active = 1;
                $order->save();

                try {
                    $syncDMS = OrderSyncDMS::dataOrder(array($order->code), "C");
                    if (!empty($syncDMS)) {
                        $pushOrderDms = OrderSyncDMS::callApiDms($syncDMS, "CREATE-ORDER");
                        if (!empty($pushOrderDms['errors'])) {
                            foreach ($pushOrderDms['errors'] as $item) {
                                Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $syncDMS ?? [], "CREATE-ORDER", 0, $item);
                            }
                        } else {
                            if (!empty($pushOrderDms)){
                                Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                            }
                            if (empty($pushOrderDms)){
                                Log::logSyncDMS($order->code, "Connection Error", $syncDMS ?? [], "CREATE-ORDER", 0, $pushOrderDms);
                            }
                            //                            Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                        }

                    }
                    Order::where('code', $order->code)->update(['log_order_dms' => json_encode($syncDMS)]);
                } catch (\Exception $exception) {
                    Log::logSyncDMS($order->code, $exception->getMessage(), $syncDMS ?? [], "CREATE-ORDER", 0, null);
                }

                #CREATE[ACCESSTRADE]
                try {
                    $accesstrade_id = $order->access_trade_id;
                    $click_id       = $order->access_trade_click_id;
                    Accesstrade::create($order, $accesstrade_id, $click_id);
                    $status = ORDER_STATUS_APPROVED;
                    $reason = ORDER_STATUS_NEW_NAME['APPROVED'];
                    Accesstrade::update($order, $status, $reason);
                } catch (\Exception $e) {
                }
            }
        } catch (\Exception $e) {
            $result["return_code"]    = 0;
            $result["return_message"] = $e->getMessage();
        }
        return json_encode($result);
    }

    public function returnVNPayIPN(Request $request)
    {
        try {
            //            $url = base64_decode($url);
            $returnData = [];
            $input      = $request->all();
            try {
                $this->logPaymentRequest($input['vnp_TxnRef'] ?? null, PAYMENT_METHOD_VNPAY, $input);
            } catch (\Exception $exception) {
            }
            $inputData      = $input;
            $vnp_SecureHash = $inputData['vnp_SecureHash'];

            unset($inputData['vnp_SecureHashType']);
            unset($inputData['vnp_SecureHash']);
            ksort($inputData);
            $dataHash = "";
            foreach ($inputData as $key => $param) {
                if (!empty($param)) {
                    $dataHash .= "$key=$param&";
                }
            }

            $dataHash   = trim(rtrim($dataHash, "&"));
            $secureHash = hash('sha256', env('VNPAY_HASHSECRET') . $dataHash);
            $orderId    = explode('-', $inputData['vnp_TxnRef']);
            $orderId    = end($orderId);
            $vnp_Amount = $inputData['vnp_Amount'];

            $vnp_Amount = (int)$vnp_Amount / 100;
            DB::beginTransaction();
            try {
                $order = Order::find($orderId);

                $total = $order->total_price ?? 0;
                if ($secureHash == $vnp_SecureHash) {
                    if (!empty($order)) {
                        $payment_method = PAYMENT_METHOD_NAME[$order->payment_method] ?? "";
                        $price_fotmat   = number_format($order->total_price) . "đ" ?? "";
                        if ($total == $vnp_Amount) {
                            if ($order->payment_status != 1) {
                                if ($inputData['vnp_ResponseCode'] == '00') {
                                    $order->payment_method = PAYMENT_METHOD_VNPAY;
                                    $order->payment_status = 1;
                                    $order->status_crm     = ORDER_STATUS_CRM_APPROVED;
                                    //                                    $order->is_active = 1;
                                    $order->payment_code = $inputData['vnp_TxnRef'] ?? null;
                                    $order->save();
                                    $returnData['RspCode'] = '00';
                                    $returnData['Message'] = 'Confirm Success';

                                    try {
                                        $syncDMS = OrderSyncDMS::dataOrder(array($order->code), "C");
                                        if (!empty($syncDMS)) {
                                            $pushOrderDms = OrderSyncDMS::callApiDms($syncDMS, "CREATE-ORDER");
                                            if (!empty($pushOrderDms['errors'])) {
                                                foreach ($pushOrderDms['errors'] as $item) {
                                                    Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $syncDMS ?? [], "CREATE-ORDER", 0, $item);
                                                }
                                            } else {
                                                if (!empty($pushOrderDms)){
                                                    Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                                                }
                                                if (empty($pushOrderDms)){
                                                    Log::logSyncDMS($order->code, "Connection Error", $syncDMS ?? [], "CREATE-ORDER", 0, $pushOrderDms);
                                                }
                                                //                                                Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                                            }

                                        }
                                        Order::where('code', $order->code)->update(['log_order_dms' => json_encode($syncDMS)]);
                                    } catch (\Exception $exception) {
                                        Log::logSyncDMS($order->code, $exception->getMessage(), $syncDMS ?? [], "CREATE-ORDER", 0, null);
                                    }

                                    #CREATE[ACCESSTRADE]
                                    try {
                                        $accesstrade_id = $order->access_trade_id;
                                        $click_id       = $order->access_trade_click_id;
                                        Accesstrade::create($order, $accesstrade_id, $click_id);
                                        $status = ORDER_STATUS_APPROVED;
                                        $reason = ORDER_STATUS_NEW_NAME['APPROVED'];
                                        Accesstrade::update($order, $status, $reason);
                                    } catch (\Exception $e) {
                                    }
                                } else {
                                    $order->payment_method = PAYMENT_METHOD_VNPAY;
                                    $order->payment_status = 0;
                                    //                                    $order->is_active = 0;
                                    $order->save();
                                    $returnData['RspCode'] = '00';
                                    $returnData['Message'] = 'Confirm Success';

                                }
                                // Create Payment History
                                $status              = $order->payment_status == 1 ? PAYMENT_STATUS_SUCCESS : PAYMENT_STATUS_FAIL;
                                $content             = $status == PAYMENT_STATUS_SUCCESS ? "Thanh toán thành công" : "Thanh toán thất bại.";
                                $paymentHistoryModel = new PaymentHistoryModel();
                                $paymentHistoryModel->create([
                                    'transaction_id' => $inputData['vnp_TransactionNo'],
                                    'date'           => date("Y-m-d H:i:s"),
                                    'type'           => PAYMENT_TYPE_PAYMENT,
                                    'method'         => PAYMENT_METHOD_VNPAY,
                                    'status'         => $status,
                                    'content'        => $content,
                                    'total_pay'      => $vnp_Amount,
                                    'balance'        => $inputData['balance'] ?? null,
                                    'user_id'        => $order->customer_id ?? null,
                                    'data'           => json_encode($inputData),
                                    'note'           => $content,
                                ]);

                            } else {
                                $returnData['RspCode'] = '02';
                                $returnData['Message'] = 'Order already confirmed';
                            }
                        } else {
                            $returnData['RspCode'] = '04';
                            $returnData['Message'] = 'Invalid Amount';
                        }
                    } else {
                        $returnData['RspCode'] = '01';
                        $returnData['Message'] = 'Order not found';
                    }
                } else {
                    $returnData['RspCode'] = '97';
                    $returnData['Message'] = 'Chu ky khong hop le';
                }
            } catch (\Exception $exception) {
                $returnData['RspCode'] = '99';
                $returnData['Message'] = 'Unknow error';
            }
            //            if ($order->payment_status != 1) {
            //                return redirect($url . "?order_id=$order->id&status=fail&code=$order->code&payment_method=$payment_method&price=$price_fotmat");
            //            }

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $returnData['RspCode'] = '99';
            $returnData['Message'] = 'Unknow error';
            return json_encode($returnData);
            //            return redirect($url . "?order_id=$order->id&status=fail&code=$order->code&payment_method=$payment_method&price=$price_fotmat");
        }
        return json_encode($returnData);
        //        return redirect($url . "?status=success&distributor_code=$order->distributor_code&full_name=$order->customer_name");

    }

    public function returnVNPay($url, Request $request)
    {
        try {
            $url        = base64_decode($url);
            $returnData = [];
            $input      = $request->all();
            $inputData  = $input;

            $vnp_ResponseCode = $input['vnp_ResponseCode'];
            //            $vnp_SecureHash = $inputData['vnp_SecureHash'];
            //
            //            unset($inputData['vnp_SecureHashType']);
            //            unset($inputData['vnp_SecureHash']);
            //            ksort($inputData);
            //            $dataHash = "";
            //            foreach ($inputData as $key => $param) {
            //                if (!empty($param)) {
            //                    $dataHash .= "$key=$param&";
            //                }
            //            }
            //            $dataHash = trim(rtrim($dataHash, "&"));
            //            $secureHash = hash('sha256', env('VNPAY_HASHSECRET') . $dataHash);
            $orderId    = explode('-', $inputData['vnp_TxnRef']);
            $orderId    = end($orderId);
            $vnp_Amount = $inputData['vnp_Amount'];
            $vnp_Amount = (int)$vnp_Amount / 100;
            //            DB::beginTransaction();
            //            try {
            $order          = Order::find($orderId);
            $payment_method = PAYMENT_METHOD_NAME[$order->payment_method] ?? "";
            $price_fotmat   = number_format($order->total_price) . "đ" ?? "";
            $total          = $order->total_price ?? 0;
            try {
                $this->logPaymentRequest($orderId ?? null, PAYMENT_METHOD_SPP, $input);
            } catch (\Exception $exception) {
            }
            if ($vnp_ResponseCode == '00') {
                return redirect($url . "?status=success&distributor_code=$order->distributor_code&code={$order->code}&full_name=$order->customer_name");
            } else {
                return redirect($url . "?order_id=$order->id&code=$order->code&price=$price_fotmat&payment_method=$payment_method&status=fail");
            }
            //                if ($secureHash == $vnp_SecureHash) {
            //                    if (!empty($order)) {
            //                        if ($total == $vnp_Amount) {
            //                            if ($order->payment_status != 1) {
            //                                if ($inputData['vnp_ResponseCode'] == '00') {
            //                                    $order->payment_method = PAYMENT_METHOD_VNPAY;
            //                                    $order->payment_status = 1;
            //                                    $order->payment_code = $inputData['vnp_TxnRef']?? null;
            //                                    $order->status_crm = ORDER_STATUS_CRM_APPROVED;
            //                                    $order->save();
            //                                    $returnData['RspCode'] = '00';
            //                                    $returnData['Message'] = 'Confirm Success';
            //                                } else {
            //                                    $order->payment_method = PAYMENT_METHOD_VNPAY;
            //                                    $order->payment_status = 0;
            //                                    $order->save();
            //                                    $returnData['RspCode'] = '00';
            //                                    $returnData['Message'] = 'Confirm Success';
            //
            //                                }
            //                                // Create Payment History
            //                                $status = $order->payment_status == 1 ? PAYMENT_STATUS_SUCCESS : PAYMENT_STATUS_FAIL;
            //                                $content = $status == PAYMENT_STATUS_SUCCESS ? "Thanh toán thành công" : "Thanh toán thất bại.";
            //                                $paymentHistoryModel = new PaymentHistoryModel();
            //                                $paymentHistoryModel->create([
            //                                    'transaction_id' => $inputData['vnp_TransactionNo'],
            //                                    'date' => date("Y-m-d H:i:s"),
            //                                    'type' => PAYMENT_TYPE_PAYMENT,
            //                                    'method' => PAYMENT_METHOD_VNPAY,
            //                                    'status' => $status,
            //                                    'content' => $content,
            //                                    'total_pay' => $vnp_Amount,
            //                                    'balance' => $inputData['balance'] ?? null,
            //                                    'user_id' => $order->customer_id ?? null,
            //                                    'data' => json_encode($inputData),
            //                                    'note' => $content,
            //                                ]);
            //
            //                            } else {
            //                                $returnData['RspCode'] = '02';
            //                                $returnData['Message'] = 'Order already confirmed';
            //                            }
            //                        } else {
            //                            $returnData['RspCode'] = '04';
            //                            $returnData['Message'] = 'Invalid Amount';
            //                        }
            //                    } else {
            //                        $returnData['RspCode'] = '01';
            //                        $returnData['Message'] = 'Order not found';
            //                    }
            //                } else {
            //                    $returnData['RspCode'] = '97';
            //                    $returnData['Message'] = 'Chu ky khong hop le';
            //                }
            //            } catch (\Exception $exception) {
            //                $returnData['RspCode'] = '99';
            //                $returnData['Message'] = 'Unknow error';
            //            }
            //            if ($order->payment_status != 1) {
            //                return redirect($url . "?order_id=$order->id&code=$order->code&price=$price_fotmat&payment_method=$payment_method&status=fail");
            //            }
            //            DB::commit();
        } catch (\Exception $ex) {
            //            DB::rollBack();
            //            $returnData['RspCode'] = '99';
            //            $returnData['Message'] = 'Unknow error';
            //            return redirect($url . "?order_id=$order->id&code=$order->code&price=$price_fotmat&payment_method=$payment_method&status=fail");
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        //        return redirect($url . "?status=success&distributor_code=$order->distributor_code&full_name=$order->customer_name");
    }
    ####################################################################################################################

    /**
     * @param $orderId
     * @param $msg
     * @return bool
     * @throws \Exception
     */
    private function sendMessageToToken($orderId, $msg)
    {
        try {
            //Get Device
            $userSession = UserSession::model()->where('user_id', $userId = TM::getCurrentUserId())->where('deleted',
                '0')->first();
            $device      = $userSession->device_token;
            if (empty($device)) {
                return false;
            }

            $title                    = empty($msg) ? "Có lỗi trong quá trình thanh toán. Vui lòng liên hệ quản trị viên" : $msg;
            $param                    = [
                'title'        => Message::get("V031"),
                'body'         => Message::get("V021", $orderId),
                'message'      => $title,
                'type'         => 1,
                'extra_data'   => '', // anyType
                'receiver'     => $device,
                'action'       => 1,
                'item_id'      => $orderId,
                "click_action" => "FLUTTER_NOTIFICATION_CLICK",
            ];
            $notificationHistoryModel = new NotificationHistoryModel();
            $notificationHistoryModel->create($param);

            $notification = ['title' => $title, 'body' => 'Đơn hàng ' . $orderId];
            $fields       = ['data' => $param, 'notification' => $notification, 'to' => $device];
            $headers      = ['Content-Type:application/json', 'Authorization:key=' . env("FIREBASE_SERVER_KEY", '')];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, env('FIREBASE_URL', ''));
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

            $result = curl_exec($ch);
            if ($result === false) {
                throw new \Exception('FCM Send Error: ' . curl_error($ch));
            }
            curl_close($ch);
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    private function returnOnePayAccess(Request $request, $userId, $type, $device = null)
    {
        $input = $request->all();
        try {
            if ((isset($input['vpc_TxnResponseCode']) && $input['vpc_TxnResponseCode'] != 0) && (empty($input['vpc_Command']) || empty($input['vpc_CurrencyCode']) || empty($input['vpc_Merchant']))) {
                return ['status' => 'fail', 'message' => 'Giao dịch không thành công'];
            }
            $input['payment_type'] = $type;
            $device_token          = $device;
            if ($device) {
                $device = "device/$device";
            }

            $params = [
                "vpc_Amount"      => $input['vpc_Amount'],
                "vpc_Command"     => $input['vpc_Command'],
                "vpc_Currency"    => $input['vpc_CurrencyCode'],
                "vpc_Locale"      => $input['vpc_Locale'],
                "vpc_MerchTxnRef" => $input['vpc_MerchTxnRef'],
                "vpc_Merchant"    => $input['vpc_Merchant'],
                "vpc_OrderInfo"   => $input['vpc_OrderInfo'],
                "vpc_AccessCode"  => env("ONEPAY_ACCESS_CODE", ""),
                "vpc_ReturnURL"   => url('/') . "/v0/onepay/returnUrl/type/$type/user/$userId/$device",
                "vpc_Version"     => $input['vpc_Version'],
                "vpc_TicketNo"    => url(),
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

            DB::beginTransaction();
            $wallet = Wallet::model()->where('user_id', $userId)
                // Todo:
                //->where('current_signature', $secureHash)
                ->first();
            if (empty($wallet)) {
                return [
                    'status'  => 'fail',
                    'message' => 'Your request does not allow to access!'
                ];
            }

            $localMessage = isset($input['vpc_TxnResponseCode']) && $input['vpc_TxnResponseCode'] == 0 ? \App\Supports\Message::get("V031") : "Thanh toán thất bại";
            $title        = !isset($input['vpc_TxnResponseCode']) ? "Có lỗi trong quá trình thanh toán. Vui lòng liên hệ quản trị viên" : ONEPAY_CODE_MSG[$input['vpc_TxnResponseCode']];
            $param        = [
                'title'       => $localMessage,
                'body'        => "vpc_OrderInfo: " . $input['vpc_OrderInfo'] . " - vpc_TransactionNo: " . ($input['vpc_MerchTxnRef'] ?? null),
                'message'     => $title,
                'notify_type' => "ONEPAY|$type",
                'type'        => "ONEPAY",
                'extra_data'  => '', // anyType
                'receiver'    => $device_token,
                'action'      => 1,
                'item_id'     => $input['vpc_OrderInfo'],
            ];

            $notificationHistoryModel = new \App\V1\Models\NotificationHistoryModel();
            $notificationHistoryModel->create($param);

            // Create Payment History
            $paymentHistoryModel = new \App\V1\Models\PaymentHistoryModel();
            $status              = $input['vpc_TxnResponseCode'];
            $paymentHistoryModel->create([
                'transaction_id' => $input['vpc_MerchTxnRef'] ?? null,
                'date'           => date("Y-m-d H:i:s", time()),
                'type'           => $type,
                'method'         => PAYMENT_METHOD_ONEPAY,
                'status'         => $status == 0 ? PAYMENT_STATUS_SUCCESS : PAYMENT_STATUS_FAIL,
                'content'        => $title,
                'total_pay'      => $input['vpc_Amount'],
                'balance'        => $input['balance'] ?? null,
                'user_id'        => $userId,
                'data'           => json_encode($input),
                'note'           => $localMessage,
            ]);

            $input['localMessage'] = $localMessage;
            $input['title']        = $title;

            if (!empty($device_token)) {
                $input['device'] = $device_token;
            }

            switch ($type) {
                case PAYMENT_TYPE_PAYMENT:
                    $order = Order::model()->where('code', $input['vpc_OrderInfo'])->first();
                    if (empty($order)) {
                        throw new \Exception(Message::get("orders.not-exist", "#{$input['vpc_OrderInfo']}"));
                    }
                    $order->payment_method = PAYMENT_METHOD_ONEPAY;
                    $order->payment_status = 1;
                    $order->save();
                    try {
                        $syncDMS = OrderSyncDMS::dataOrder(array($order->code), "C");
                        if (!empty($syncDMS)) {
                            $pushOrderDms = OrderSyncDMS::callApiDms($syncDMS, "CREATE-ORDER");
                            if (!empty($pushOrderDms['errors'])) {
                                foreach ($pushOrderDms['errors'] as $item) {
                                    Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $syncDMS ?? [], "CREATE-ORDER", 0, $item);
                                }
                            } else {
                                if (!empty($pushOrderDms)){
                                    Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                                }
                                if (empty($pushOrderDms)){
                                    Log::logSyncDMS($order->code, "Connection Error", $syncDMS ?? [], "CREATE-ORDER", 0, $pushOrderDms);
                                }
                                //                                Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                            }

                        }
                        Order::where('code', $order->code)->update(['log_order_dms' => json_encode($syncDMS)]);
                    } catch (\Exception $exception) {
                        Log::logSyncDMS($order->code, $exception->getMessage(), $syncDMS ?? [], "CREATE-ORDER", 0, null);
                    }
                    $wallet = Wallet::model()->where('user_id', $userId)->first();
                    if (!empty($wallet)) {
                        $wallet->balance += $input['vpc_Amount'];
                        $wallet->save();
                    }
                    break;
                case PAYMENT_TYPE_RECHARGE:
                    $wallet = Wallet::model()->where('user_id', $userId)->where('current_signature',
                        $secureHash)->first();
                    if (empty($wallet)) {
                        break;
                    }

                    $oldBalance         = $wallet->balance;
                    $wallet->balance    += $input['vpc_Amount'];
                    $wallet->updated_at = date("Y-m-d H:i:s", time());
                    $wallet->updated_by = $userId;
                    $wallet->save();

                    // Wallet History
                    $walletHistory = new WalletHistoryModel();
                    $walletHistory->create([
                        "wallet_id"      => $wallet->id,
                        "transaction_id" => $input['vpc_MerchTxnRef'],
                        "date"           => date("Y-m-d H:i:s", time()),
                        "balance"        => $oldBalance,
                        "increase"       => $input['vpc_Amount'],
                        "created_at"     => date("Y-m-d H:i:s", time()),
                        "created_by"     => $userId,
                    ]);
                    break;
                case PAYMENT_TYPE_WITHDRAW:
                    break;
            }
            DB::commit();
        } catch (\Exception $ex) {
            return ['status' => 'fail', 'message' => $ex->getMessage()];
        }

        return ['status' => 'success', 'message' => 'Thanh toán thành công'];
    }

    public function returnCallbackShopeePay(Request $request)
    {
        $result = [];

        $input = $request->all();
        try {
            $this->logPaymentRequest($input['reference_id'] ?? null, PAYMENT_METHOD_SPP, $input);
        } catch (\Exception $exception) {
        }
        $value       = $request->header('x-airpay-req-h');
        $array_order = explode("-", $input['reference_id']);
        $id_order    = end($array_order);
        $order       = Order::model()->where('id', $id_order)->first();
        try {
            $base64_encoded_hash = base64_encode(hash_hmac('sha256', json_encode($input), $order['order_channel'] == "WEB" ? $this->shopeePayParam['secret_web'] : $this->shopeePayParam['secret_mobile_web'], true));
            if (strcmp($value, $base64_encoded_hash) != 0) {
                $result["return_code"]    = -1;
                $result["return_message"] = "signature is incorrect";
            } else {
                $result["return_code"]    = 1;
                $result["return_message"] = "success";
                if (!empty($order) && $order->payment_status != 1) {
                    $param_ntf_histori = [
                        'title'      => SHOPEE_PAY_STATUS[$input['transaction_status']],
                        'body'       => Message::get("V021", $id_order),
                        'message'    => SHOPEE_PAY_STATUS[$input['transaction_status']],
                        'type'       => "SPP",
                        'extra_data' => '', // anyType
                        'receiver'   => $input['device'] ?? null,
                        'action'     => 1,
                        'item_id'    => $id_order,
                    ];
                    try {
                        DB::beginTransaction();
                        $order->payment_method = PAYMENT_METHOD_SPP;
                        $order->payment_status = 1;
                        $order->status_crm     = ORDER_STATUS_CRM_APPROVED;
                        $order->payment_code   = $input['reference_id'] ?? null;
                        $order->save();

                        try {
                            $syncDMS = OrderSyncDMS::dataOrder(array($order->code), "C");
                            if (!empty($syncDMS)) {
                                $pushOrderDms = OrderSyncDMS::callApiDms($syncDMS, "CREATE-ORDER");
                                if (!empty($pushOrderDms['errors'])) {
                                    foreach ($pushOrderDms['errors'] as $item) {
                                        Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $syncDMS ?? [], "CREATE-ORDER", 0, $item);
                                    }
                                } else {
                                    if (!empty($pushOrderDms)){
                                        Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                                    }
                                    if (empty($pushOrderDms)){
                                        Log::logSyncDMS($order->code, "Connection Error", $syncDMS ?? [], "CREATE-ORDER", 0, $pushOrderDms);
                                    }
                                    //                                    Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                                }

                            }
                            Order::where('code', $order->code)->update(['log_order_dms' => json_encode($syncDMS)]);
                        } catch (\Exception $exception) {
                            Log::logSyncDMS($order->code, $exception->getMessage(), $syncDMS ?? [], "CREATE-ORDER", 0, null);
                        }
                        //                        $notificationHistoryModel = new NotificationHistoryModel();
                        //                        $notificationHistoryModel->create($param_ntf_histori);

                        // Create Payment History
                        //                        $status = isset($input['transaction_status']) && $input['transaction_status'] == 3 ? PAYMENT_STATUS_SUCCESS : PAYMENT_STATUS_FAIL;
                        //                        $paymentHistoryModel = new PaymentHistoryModel();
                        //                        $paymentHistoryModel->create([
                        //                            'transaction_id' => $input['reference_id'],
                        //                            'date' => date("Y-m-d H:i:s"),
                        //                            'type' => 'PAYMENT',
                        //                            'method' => PAYMENT_METHOD_SPP,
                        //                            'status' => $status,
                        //                            'content' => SHOPEE_PAY_STATUS[$input['transaction_status']] ?? null,
                        //                            'total_pay' => $input['amount'],
                        //                            'balance' => $input['balance'] ?? null,
                        //                            'user_id' => $input['user_id'] ?? null,
                        //                            'data' => json_encode($input),
                        //                            'note' => $response['transaction_status'] ?? null
                        //                        ]);
                        DB::commit();
                        #CREATE[ACCESSTRADE]
                        try {
                            $accesstrade_id = $order->access_trade_id;
                            $click_id       = $order->access_trade_click_id;
                            Accesstrade::create($order, $accesstrade_id, $click_id);
                            $status = ORDER_STATUS_APPROVED;
                            $reason = ORDER_STATUS_NEW_NAME['APPROVED'];
                            Accesstrade::update($order, $status, $reason);
                        } catch (\Exception $e) {
                        }
                    } catch (\Exception $ex) {
                        DB::rollBack();
                        return $ex->getMessage();
                    }
                    //                    $cart->payment_method = PAYMENT_METHOD_SPP;
                    //                    $cart->payment_status = 1;
                    //                    $cart->log_payment    = $input;
                    //                    $cart->save();
                    //                    $result                  = $this->confirmOrder($cart);
                    //                    $cart->log_confirm_order = $result;
                    //                    $cart->save();
                }
            }
        } catch (\Exception $e) {
            $result["return_code"]    = 0;
            $result["return_message"] = $e->getMessage();
        }
        return json_encode($result);
    }

    public function updateIndirectPayment(Request $request)
    {
        $input = $request->all();
        if (empty($input['order_id'])) {
            throw new \Exception(Message::get("V001", 'order_id'));
        }
        if (!in_array($input['payment_method'], array_keys(PAYMENT_METHOD_NAME))) {
            throw new \Exception(Message::get("V003", Message::get("payment_method")));
        }
        try {
            DB::beginTransaction();
            $order                 = Order::find($input['order_id']);
            $order->payment_method = $input['payment_method'];
            $order->save();
            DB::commit();

        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return redirect($input['payUrl'] . "?distributor_code={$order->distributor_code}&full_name={$order->customer_name}&status=success");
    }

    public function getClientPaymentMethod(Request $request)
    {
        $store_id   = null;
        $company_id = null;
        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
            $group_id   = TM::getCurrentGroupId();
            $company_id = TM::getCurrentCompanyId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id   = $store->id;
                    $company_id = $store->company_id;
                }
            }
        }

        if (empty($company_id)) {
            return response()->json(['data' => []]);
        }

        return response()->json(['data' => $this->paymentMethod]);
    }

    public function paymentStatusQuery($code, Request $request)
    {
        $input = $request->all();
        $type = $input['type'] ?? null;
        
        try {

            if(!in_array($type, PAYMENT_METHOD_QUERY)){
                return $this->responseData();
            }

            $order = Order::model()->where('code', $code)->first();
            if (empty($order)) {
                return $this->responseError(Message::get("V003", Message::get("code") . " #$code"));
            }
            $payment_amont  = !empty($order->vpvirtualaccount->collect_ammount) ? $order->vpvirtualaccount->collect_ammount : "";
            $payment_method = PAYMENT_METHOD_NAME[$order->payment_method] ?? "";
            $price_fotmat   = number_format($order->total_price) . "đ" ?? "";
            if ($order->payment_status == 1) {
                return response()->json([
                    'status'       => 1,
                    'redirect_url' => $input['url'] . "?distributor_code={$order->distributor_code}&full_name={$order->customer_name}&code={$order->code}&payment_amont=$payment_amont&status=success"

                ]);
            }
            if (!empty($input['uid_payment']) && $input['uid_payment'] != $order->uid_payment) {
                return response()->json([
                    'status'       => 2,
                    'redirect_url' => ($input['url'] . "?code={$order->code}&price=$price_fotmat&payment_method=$payment_method&order_id=$order->id&status=fail")
                ]);
            }
            

            if (!empty($type) && in_array($type, PAYMENT_METHOD_QUERY)) {
                switch ($type) {
                    case PAYMENT_METHOD_ZALO:
                        $result = $this->queryZalo($order);
                        break;
                    case PAYMENT_METHOD_SPP:
                        $result = $this->querySPP($request, $order);
                        break;
                    case PAYMENT_METHOD_MOMO:
                        $result = $this->queryMomo($order);
                        break;
                    case PAYMENT_METHOD_BANK:
                        $result = $this->queryVpBank($order);
                        break;
                    default:
                        break;
                }
            }
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        if (!empty($result['status']) && $result['status'] == 2) {
            $result['redirect_url'] = ($input['url'] . "?code={$order->code}&price=$price_fotmat&payment_method=$payment_method&order_id=$order->id&status=fail");
        }
        if (!empty($result['status']) && $result['status'] == 1) {
            $result['redirect_url'] = $input['url'] . "?distributor_code={$order->distributor_code}&full_name={$order->customer_name}&code={$order->code}&payment_amont=$payment_amont&status=success";
        }
        return response()->json($result ?? ['status'=>0]);
    }

    function queryVpBank($order)
    {
        if ($order['payment_status'] != 0 && !empty($order->vpvirtualaccount->collect_ammount)) {
            return ['status' => 1];
        }
        if ($order['payment_status'] == 0 || empty($order->vpvirtualaccount->collect_ammount)) {
            return ['status' => 0];
        }
    }

    function queryVpBankHS($order)
    {
        if ($order['payment_status'] != 0) {
            return ['status' => 1];
        }
        if ($order['payment_status'] == 0) {
            return ['status' => 0];
        }
    }

    function queryMomo($order)
    {
        $partnerCode  = env("MOMO_PARTNER_CODE", null);
        $accessKey    = env("MOMO_ACCESS_KEY", null);
        $secretKey    = env("MOMO_SECRET_KEY");
        $requestId    = time() . "";
        $order_id     = $order['id_momo_payment'];
        $rawHash      = "accessKey=" . $accessKey . "&orderId=" . $order_id . "&partnerCode=" . $partnerCode . "&requestId=" . $requestId;
        $signature    = hash_hmac("sha256", $rawHash, $secretKey);
        $params       = [
            'partnerCode' => $partnerCode,
            'requestId'   => $requestId,
            'orderId'     => $order_id,
            'signature'   => $signature,
            "lang"        => "vi"
        ];
        $endPoint     = env("MOMO_ENDPOINT", null) . "/api/query";
        $client       = new Client();
        $momoResponse = $client->post($endPoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => json_encode($params)]);
        $momoResponse = $momoResponse->getBody();
        $response     = !empty($momoResponse) ? json_decode($momoResponse, true) : [];
        $title        = empty($response['message']) ? "Có lỗi trong quá trình thanh toán. Vui lòng liên hệ quản trị viên" : $response['message'];
        $param        = [
            'title'      => $title,
            'body'       => Message::get("V021", $order_id),
            'message'    => $title,
            'type'       => "MOMO",
            'extra_data' => '', // anyType
            'receiver'   => $input['device'] ?? null,
            'action'     => 1,
            'item_id'    => $order_id,
        ];
        try {
            $array_order = explode("-", $response['orderId']);
            $id          = end($array_order);
            $order       = Order::model()->where('id', $id)->first();
            if (($response['resultCode']) && $response['resultCode'] == 1003 || strtotime(date('Y-m-d H:i:s')) >= $order['time_qr_momo']) {
                return [
                    'status' => 2
                ];
            }
            if ($order->payment_status == 1 && $response['resultCode'] == 0) {
                return [
                    'status' => 1
                ];
            }
            if (($response['resultCode']) && $response['resultCode'] == 1000) {
                return [
                    'status' => 0
                ];
            }
            DB::beginTransaction();
            $order->payment_method = PAYMENT_METHOD_MOMO;
            $order->payment_status = 1;
            $order->payment_code   = $response['orderId'] ?? null;

            $order->save();

            try {
                $syncDMS = OrderSyncDMS::dataOrder(array($order->code), "C");
                if (!empty($syncDMS)) {
                    $pushOrderDms = OrderSyncDMS::callApiDms($syncDMS, "CREATE-ORDER");
                    if (!empty($pushOrderDms['errors'])) {
                        foreach ($pushOrderDms['errors'] as $item) {
                            Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $syncDMS ?? [], "CREATE-ORDER", 0, $item);
                        }
                    } else {
                        if (!empty($pushOrderDms)){
                            Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                        }
                        if (empty($pushOrderDms)){
                            Log::logSyncDMS($order->code, "Connection Error", $syncDMS ?? [], "CREATE-ORDER", 0, $pushOrderDms);
                        }
                        //                        Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                    }

                }
                Order::where('code', $order->code)->update(['log_order_dms' => json_encode($syncDMS)]);
            } catch (\Exception $exception) {
                Log::logSyncDMS($order->code, $exception->getMessage(), $syncDMS ?? [], "CREATE-ORDER", 0, null);
            }

            // Create Notification History
            $notificationHistoryModel = new NotificationHistoryModel();
            $notificationHistoryModel->create($param);

            // Create Payment History
            $status              = isset($response['resultCode']) && $response['resultCode'] == 0 ? PAYMENT_STATUS_SUCCESS : PAYMENT_STATUS_FAIL;
            $paymentHistoryModel = new PaymentHistoryModel();
            $paymentHistoryModel->create([
                'transaction_id' => $response['transId'],
                'date'           => date("Y-m-d H:i:s"),
                'type'           => PAYMENT_TYPE_PAYMENT,
                'method'         => PAYMENT_METHOD_MOMO,
                'status'         => $status,
                'content'        => MOMO_CODE_MSG[$response['resultCode']] ?? null,
                'total_pay'      => $response['amount'],
                'balance'        => $input['balance'] ?? null,
                'user_id'        => $input['user_id'] ?? null,
                'data'           => json_encode($response),
                'note'           => $response['message'] ?? null,
            ]);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            return $ex->getMessage();;
        }
        return [
            'status' => 1
        ];
    }

    function querySPP(Request $request, Order $model)
    {
        DB::beginTransaction();
        try {
            $returnStatus        = 0;
            $order_model         = $model->toArray();
            $input               = $request->all();
            $param               = [
                "request_id"       => $order_model['shopee_reference_id'],
                "reference_id"     => $order_model['shopee_reference_id'],
                "transaction_type" => 13,
                "merchant_ext_id"  => $this->shopeePayParam['merchant_id'],
                "store_ext_id"     => $this->shopeePayParam['store_id'],
                "amount"           => (int)$order_model['total_price'] * 100
            ];
            $dataHash            = (json_encode($param));
            $base64_encoded_hash = base64_encode(hash_hmac('sha256', $dataHash, ($order_model['order_channel'] == "WEB" ? $this->shopeePayParam['secret_web'] : $this->shopeePayParam['secret_mobile_web']), true));
            $client              = new Client();
            $shoppe_response     = $client->post(env("API_SHOPPEPAY") . "/v3/merchant-host/transaction/check", [
                'headers' => ['Content-Type' => 'application/json', 'X-Airpay-ClientId' => $order_model['order_channel'] == "WEB" ? $this->shopeePayParam['client_id_web'] : $this->shopeePayParam['client_id_mobile_web'], 'X-Airpay-Req-H' => $base64_encoded_hash
                ],
                'body'    => json_encode($param),
            ]);
            $shoppe_response     = $shoppe_response->getBody();
            $response            = !empty($shoppe_response) ? json_decode($shoppe_response, true) : [];
            if (!empty($response['transaction']['status'])) {
                $array_order    = explode("-", $response['transaction']['reference_id']);
                $id_order       = end($array_order);
                $order          = Order::model()->where('id', $id_order)->first();
                $payment_method = PAYMENT_METHOD_NAME[$order->payment_method] ?? "";
                $price_fotmat   = number_format($order->total_price) . "đ" ?? "";
                if ($response['transaction']['status'] == 3) {
                    if (!empty($order) && $order->payment_status != 1) {
                        $order->update([
                            'payment_method' => PAYMENT_METHOD_SPP,
                            'payment_code'   => $response['transaction']['reference_id'] ?? null,
                            'payment_status' => 1,
                            'status_crm'     => ORDER_STATUS_CRM_APPROVED
                        ]);

                        try {
                            $syncDMS = OrderSyncDMS::dataOrder(array($order->code), "C");
                            if (!empty($syncDMS)) {
                                $pushOrderDms = OrderSyncDMS::callApiDms($syncDMS, "CREATE-ORDER");
                                if (!empty($pushOrderDms['errors'])) {
                                    foreach ($pushOrderDms['errors'] as $item) {
                                        Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $syncDMS ?? [], "CREATE-ORDER", 0, $item);
                                    }
                                } else {
                                    if (!empty($pushOrderDms)){
                                        Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                                    }
                                    if (empty($pushOrderDms)){
                                        Log::logSyncDMS($order->code, "Connection Error", $syncDMS ?? [], "CREATE-ORDER", 0, $pushOrderDms);
                                    }
                                    //                                    Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                                }

                            }
                            Order::where('code', $order->code)->update(['log_order_dms' => json_encode($syncDMS)]);
                        } catch (\Exception $exception) {
                            Log::logSyncDMS($order->code, $exception->getMessage(), $syncDMS ?? [], "CREATE-ORDER", 0, null);
                        }

                    }
                    $returnStatus = 1;
                }
                if ($response['transaction']['status'] == 4 || strtotime(date('Y-m-d H:i:s')) >= $order['time_qr_spp']) {
                    if ($returnStatus != 1) {
                        $returnStatus = 2;
                    }
                    //                        return [
                    //                            'status' => 2
                    //                        ];
                }
                if ($response['transaction']['status'] == 2) {
                    $returnStatus = 0;
                    //                        return [
                    //                            "status" => 0
                    //                        ];
                }
                if ($returnStatus == 2 || $returnStatus == 1) {
                    $param_ntf_histori = [
                        'title'      => $response['debug_msg'] ?? null,
                        'body'       => Message::get("V021", $id_order),
                        'message'    => $response['debug_msg'] ?? null,
                        'type'       => "SPP",
                        'extra_data' => '', // anyType
                        'receiver'   => $input['device'] ?? null,
                        'action'     => 1,
                        'item_id'    => $id_order,
                    ];
                    //                        $order->payment_method = PAYMENT_METHOD_SPP;
                    //                        $order->payment_status = 1;
                    $notificationHistoryModel = new NotificationHistoryModel();
                    $notificationHistoryModel->create($param_ntf_histori);

                    // Create Payment History
                    $status              = isset($response['transaction']['status']) && $response['transaction']['status'] == 3 ? PAYMENT_STATUS_SUCCESS : PAYMENT_STATUS_FAIL;
                    $paymentHistoryModel = new PaymentHistoryModel();
                    $paymentHistoryModel->create([
                        'transaction_id' => $response['transaction']['reference_id'],
                        'date'           => date("Y-m-d H:i:s"),
                        'type'           => 'PAYMENT',
                        'method'         => PAYMENT_METHOD_SPP,
                        'status'         => $status,
                        'content'        => SHOPEE_PAY_STATUS[$response['transaction']['status']] ?? null,
                        'total_pay'      => $response['transaction']['amount'],
                        'balance'        => $input['balance'] ?? null,
                        'user_id'        => $input['user_id'] ?? null,
                        'data'           => json_encode($response),
                        'note'           => $response['transaction']['status'] ?? null
                    ]);
                }
            }
            DB::commit();
            if (strtotime(date('Y-m-d H:i:s')) >= $order_model['time_qr_spp']) {
                if ($returnStatus != 1) {
                    $returnStatus = 2;
                }
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            return [
                'status' => 0
            ];
        }
        return [
            'status' => $returnStatus
        ];
    }

    function queryZalo(Order $order)
    {
        $hashInput = (int)env("ZALO_APPID") . "|" . $order['app_trans_id'] . "|" . env("ZALO_KEY1");
        $params    = [
            "app_id"       => (int)env("ZALO_APPID"),
            "app_trans_id" => $order['app_trans_id'],
            "mac"          => hash_hmac("sha256", $hashInput, env("ZALO_KEY1"))
        ];
        try {
            $endPoint     = env("ZALO_ENDPOINT", null);
            $client       = new Client();
            $zaloResponse = $client->post($endPoint . "/v2/query", ['form_params' => $params]);
            $zaloResponse = $zaloResponse->getBody();
            $response     = !empty($zaloResponse) ? json_decode($zaloResponse, true) : [];
        } catch (\Exception $exception) {
            return [
                'status' => 0
            ];
        }
        $param = [
            'title'      => "Thanh toán qua Zalo pay",
            'body'       => Message::get("V021", $order['id']),
            'message'    => "Thanh toán qua Zalo pay",
            'type'       => "ZALO",
            'extra_data' => '', // anyType
            'receiver'   => null,
            'action'     => 1,
            'item_id'    => $order['id'],
        ];
        if ($response['return_code'] == 2 || strtotime(date('Y-m-d H:i:s')) >= $order['time_qr_zalo']) {
            return [
                'status' => 2
            ];
        }
        if ($response['return_code'] == 3) {
            return [
                'status' => 0
            ];
        }
        try {
            if ($order['payment_status'] != 1 && $response['return_code'] == 1) {
                DB::beginTransaction();
                $order = Order::model()->where('id', $order['id'])->first();
                if (!empty($order)) {
                    $order->payment_method = PAYMENT_METHOD_ZALO;
                    $order->payment_status = 1;
                    $order->payment_code   = $order->app_trans_id ?? null;
                    $order->status_crm     = ORDER_STATUS_CRM_APPROVED;
                    $order->save();

                    try {
                        $syncDMS = OrderSyncDMS::dataOrder(array($order->code), "C");
                        if (!empty($syncDMS)) {
                            $pushOrderDms = OrderSyncDMS::callApiDms($syncDMS, "CREATE-ORDER");
                            if (!empty($pushOrderDms['errors'])) {
                                foreach ($pushOrderDms['errors'] as $item) {
                                    Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $syncDMS ?? [], "CREATE-ORDER", 0, $item);
                                }
                            } else {
                                if (!empty($pushOrderDms)){
                                    Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                                }
                                if (empty($pushOrderDms)){
                                    Log::logSyncDMS($order->code, "Connection Error", $syncDMS ?? [], "CREATE-ORDER", 0, $pushOrderDms);
                                }
                                //                                Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                            }

                        }
                        Order::where('code', $order->code)->update(['log_order_dms' => json_encode($syncDMS)]);
                    } catch (\Exception $exception) {
                        Log::logSyncDMS($order->code, $exception->getMessage(), $syncDMS ?? [], "CREATE-ORDER", 0, null);
                    }

                }
                // Create Notification History
            }
            if ($response['return_code'] == 1) {
                $notificationHistoryModel = new NotificationHistoryModel();
                $notificationHistoryModel->create($param);
            }
            $total = $order->total_price ?? $order['total_price'];
            // Create Payment History
            $status              = isset($response['return_code']) && $response['return_code'] == 1 ? PAYMENT_STATUS_SUCCESS : PAYMENT_STATUS_FAIL;
            $paymentHistoryModel = new PaymentHistoryModel();
            $paymentHistoryModel->create([
                'transaction_id' => $response['zp_trans_id'],
                'date'           => date("Y-m-d H:i:s"),
                'type'           => PAYMENT_TYPE_PAYMENT,
                'method'         => PAYMENT_METHOD_ZALO,
                'status'         => $status,
                'content'        => "Thanh toán thành công",
                'total_pay'      => $total,
                'balance'        => $input['balance'] ?? null,
                'user_id'        => null,
                'data'           => json_encode($response),
                'note'           => "Thanh toán thành công",
            ]);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
        }
        return [
            'status' => 1
        ];
    }

    public function refundPayment($code, Request $request)
    {
        $input = $request->all();
        $order = Order::model()->where(['code' => $code, 'payment_status' => 1])->first();
        if (empty($order)) {
            throw new \Exception(Message::get("V003", Message::get("orders")));
        }
        $result = [];
        try {
            $type = $order->payment_method;
            switch ($type) {
                case PAYMENT_METHOD_MOMO:
                    $result = $this->refundMomo($order, $input);
                    break;
            }
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
        if (!empty($result) && $result['status'] == "success") {
            return ['status' => Message::get("payment_successful_refund")];
        }
        return ['status' => Message::get("payment_failed_refund")];
    }

    function refundMomo($order, $input)
    {
        $time_date = date('Y-m-d H:i:s', time());
        $y         = date("Y", time());
        $m         = date("m", time());
        $d         = date("d", time());
        $h         = date("H", time());
        $i         = date("i", time());
        $s         = date("s", time());
        $order_id  = CODE_SHOPPING_PAYMENT . $y . $m . $d . $h . $i . $s . "-" . $order->id;
        $dataHash
            = "accessKey={$this->momoParam['accessKey']}" .
            "&amount=" . (float)$order->total_price .
            "&description=" . (array_get($input, 'description', "")) .
            "&orderId=" . $order_id .
            "&partnerCode={$this->momoParam['partnerCode']}" .
            "&requestId=" . strtotime($time_date) .
            "&transId={$order->payment_code}";
        $param     = [
            "partnerCode" => $this->momoParam['partnerCode'],
            "orderId"     => $order_id,
            "requestId"   => strtotime($time_date),
            "amount"      => (float)$order->total_price,
            "transId"     => $order->payment_code,
            "lang"        => "vi",
            "description" => array_get($input, 'description', ""),
            "signature"   => hash_hmac("sha256", $dataHash, env("MOMO_SECRET_KEY")),
        ];
        $status    = "failed";
        $isStatus  = false;
        $client    = new Client();
        $endPoint  = env("MOMO_ENDPOINT", null);
        if (!$endPoint) {
            return json_encode([]);
        }
        $momoResponse = $client->post($endPoint . "/api/refund", [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => json_encode($param)]);

        $momoResponse = $momoResponse->getBody();
        $response     = !empty($momoResponse) ? json_decode($momoResponse, true) : [];
        if (isset($response['resultCode']) && $response['resultCode'] == 0) {
            $order->payment_status = 4;
            $order->save();
            $data     = [
                'order_id'     => $order->id,
                'code_refund'  => $response['orderId'],
                'code_request' => $response['requestId'],
                'price_refund' => $response['amount'],
                'trading_code' => $response['transId'],
                'type'         => PAYMENT_METHOD_MOMO,
                'is_active'    => 1,
                'data'         => json_encode($response)
            ];
            $status   = "success";
            $isStatus = true;
            PaymentRefund::create($data);
        }
        return ['status' => $status, 'success' => $isStatus];
    }

    function confirmOrder(Cart $cart)
    {
        $userId     = $cart->user_id;
        $session    = $cart->session_id;
        $cart_info  = json_decode($cart->cart_info, true);
        $input      = $cart_info;
        $errors     = "";
        $store_id   = $cart->store_id;
        $company_id = $cart->company_id;

        if ($userId) {
            $city                      = City::where('code', $input['city_code'])->first();
            $city_name                 = Arr::get($city, 'type') . " " . Arr::get($city, 'name');
            $district                  = District::where('code', $input['district_code'])->first();
            $district_name             = Arr::get($district, 'type') . " " . Arr::get($district, 'name');
            $ward                      = Ward::where('code', $input['ward_code'])->first();
            $ward_name                 = Arr::get($ward, 'type') . " " . Arr::get($ward, 'name');
            // $input['shipping_address'] = "{$input['street_address']} - {$ward_name} - {$district_name} - {$city_name}";
            $input['shipping_address'] = "{$input['street_address']}, {$ward_name}, {$district_name}, {$city_name}";
            //Update Info Cart
            $cart->address     = $input['shipping_address'] ?? null;
            $cart->description = $input['note'] ?? null;
            $cart->phone       = $input['phone'] ?? null;
            $cart->save();

            $totalPrice    = 0;
            $originalPrice = 0;
            $subTotalPrice = 0;
            $totalTmp = 0;
            //            $freeShip      = false;
            $customerPoint = null;

            $date = date('Y-m-d H:i:s', time());
            $promotion_flashsale = PromotionProgram::model()->where('promotion_type', 'FLASH_SALE')
                ->where('start_date', "<=", $date)->where('end_date', '>=', $date)
                ->where('status', 1)->where('deleted', 0)->where('company_id', TM::getCurrentCompanyId())->get();

            foreach ($cart->details as $detail) {
                //Check Qty Flashsale
                $limit_qty_flash_sale = null;
                $min_qty_flash_sale = null;
                foreach ($promotion_flashsale ?? [] as $flashsale) {
                    if ($flashsale->act_type == 'sale_off_on_products') {
                        if (!empty($flashsale->act_products) && $flashsale->act_products != "[]") {
                            $prod_promo = json_decode($flashsale->act_products);
                            $act_products = array_pluck(json_decode($flashsale->act_products), 'product_code');
                            $check_prod = array_search($detail->product_code, $act_products);

                            if(is_numeric($check_prod)){
                                if(!empty($flashsale->limit_qty_flash_sale) && $detail->quantity > $flashsale->limit_qty_flash_sale){
                                    throw new \Exception("Tổng số lượng được mua tối đa là $flashsale->limit_qty_flash_sale trên toàn bộ sản phẩm trong giỏ hàng đối với chương trình $flashsale->name. Cảm ơn bạn đồng hành cùng Nutifood san sẻ suất mua với cộng đồng.",400);
                                }

                                if (empty($flashsale->limit_qty_flash_sale) && $flashsale->limit_qty_flash_sale <= 0 && !empty($prod_promo[$check_prod]->limit_qty_flash_sale) && $prod_promo[$check_prod]->limit_qty_flash_sale > 0) {
                                    if ($detail->quantity > $prod_promo[$check_prod]->limit_qty_flash_sale) {
                                        $limit_qty_flash_sale = $prod_promo[$check_prod]->limit_qty_flash_sale ?? 0;
                                        throw new \Exception("Tổng số lượng được mua tối đa là $limit_qty_flash_sale trên toàn bộ sản phẩm trong giỏ hàng đối với chương trình $flashsale->name. Cảm ơn bạn đồng hành cùng Nutifood san sẻ suất mua với cộng đồng.");
                                    }
                                }
                                if (empty($flashsale->min_qty_sale) && $flashsale->min_qty_sale <= 0 && !empty($prod_promo[$check_prod]->min_qty_sale) && $prod_promo[$check_prod]->min_qty_sale > 0) {
                                    if ($detail->quantity < $prod_promo[$check_prod]->min_qty_sale) {
                                        $min_qty_sale = $prod_promo[$check_prod]->min_qty_sale ?? 0;
                                        throw new \Exception("Tổng số lượng được mua tối thiểu là $min_qty_sale trên toàn bộ sản phẩm trong giỏ hàng đối với chương trình $flashsale->name. Cảm ơn bạn đồng hành cùng Nutifood san sẻ suất mua với cộng đồng.");
                                    }
                                }
                            }
                        }
                    }
                    if ($flashsale->act_type == 'sale_off_on_categories') {
                        if (!empty($flashsale->act_categories) && $flashsale->act_categories != "[]") {
                            $Category = !empty($flashsale->act_categories) ? array_pluck(json_decode($flashsale->act_categories), 'category_id') : [];
                            foreach (json_decode($flashsale->act_categories) as $act_category) {
                                $check = array_intersect($Category, explode(',', $detail->product_category));
                                if (!empty($check)) {
                                    if(!empty($flashsale->limit_qty_flash_sale) && $detail->quantity > $act_category->limit_qty_flash_sale){
                                        throw new \Exception("Tổng số lượng được mua tối đa là $act_category->limit_qty_flash_sale trên toàn bộ sản phẩm trong giỏ hàng đối với chương trình $flashsale->name. Cảm ơn bạn đồng hành cùng Nutifood san sẻ suất mua với cộng đồng.",400);
                                    }

                                    if (empty($flashsale->limit_qty_flash_sale) && $flashsale->limit_qty_flash_sale <= 0 && !empty($act_category->limit_qty_flash_sale) && $act_category->limit_qty_flash_sale > 0) {
                                        if ($detail->quantity > $act_category->limit_qty_flash_sale) {
                                            $limit_qty_flash_sale = $prod_promo[$check_prod]->limit_qty_flash_sale ?? 0;
                                            throw new \Exception("Tổng số lượng được mua tối đa là $limit_qty_flash_sale trên toàn bộ sản phẩm trong giỏ hàng đối với chương trình $flashsale->name. Cảm ơn bạn đồng hành cùng Nutifood san sẻ suất mua với cộng đồng.");
                                        }
                                    }
                                    if (empty($flashsale->min_qty_sale) && $flashsale->min_qty_sale <= 0 && !empty($act_category->min_qty_sale) && $act_category->min_qty_sale > 0) {
                                        if ($detail->quantity < $act_category->min_qty_sale) {
                                            $min_qty_sale = $prod_promo[$check_prod]->min_qty_sale ?? 0;
                                            throw new \Exception("Tổng số lượng được mua tối thiểu là $min_qty_sale trên toàn bộ sản phẩm trong giỏ hàng đối với chương trình $flashsale->name. Cảm ơn bạn đồng hành cùng Nutifood san sẻ suất mua với cộng đồng.");
                                        }
                                    }
                                    break;
                                }
                            }
                        }
                    }
                }
                $priceProduct = Arr::get($detail->product, 'priceDetail.price', Arr::get($detail->product, 'price', 0));
                $originalPrice += $priceProduct;

                $totalTmp += $priceProduct * $detail->quantity;
            }
            $settingMinAmtSet =  Setting::model()->where(['code' => 'LIMITCARTTOTAL', 'company_id' => TM::getCurrentCompanyId()])->value('data');
            if (!empty($settingMinAmtSet)){
                $settingMinAmt = array_pluck(json_decode($settingMinAmtSet), null,'key');
                $settingMinAmtStatus = $settingMinAmt['STATUS']->value ?? 0;
                if ($settingMinAmtStatus == 1){
                    $settingMinAmt = $settingMinAmt['MINCARTTOTAL']->value ?? 0;
                    if ($settingMinAmt != 0 && $totalTmp < $settingMinAmt){
                        throw new \Exception('Tổng tiền đơn hàng không đúng!');
                    }
                }

            }
            foreach ($cart->total_info as $key => $item) {
                switch ($item['code']) {
                    case 'sub_total':
                        $subTotalPrice = $item['value'];
                        break;
                    case 'total':
                        $totalPrice = $item['value'];
                        break;
                    default:
                        //                        if (!empty($item['act_type']) && $item['act_type'] == 'free_shipping') {
                        //                            $freeShip = true;
                        //                        }

                        if (!empty($item['act_type']) && $item['act_type'] == 'accumulate_point') {
                            $customerPoint = $item['value'];
                        }
                        break;
                }
            }
            try {
                DB::beginTransaction();
                $orderStatus = OrderStatus::model()->where([
                    'company_id' => $company_id,
                    'code'       => ORDER_STATUS_NEW,
                ])->first();
                if (empty($orderStatus)) {
                    $errors .= Message::get("V002", "status");
                }
                $autoCode          = $this->getAutoOrderCode();
                $lat_long          = $cart->ship_address_latlong ? explode(",", $cart->ship_address_latlong) : null;
                $distributorToUser = null;
                if (!empty($input['distributor_code'])) {
                    $distributorToUser = User::model()
                        ->where([
                            'code'       => $input['distributor_code'],
                            //                            'name'       => $input['distributor_name'],
                            'company_id' => $company_id,
                            'store_id'   => $store_id,
                        ])->first();
                }
                if (!empty($cart->coupon_code)) {
                    $detail = RotationDetail::join('rotation_results as rr', 'rr.code', 'rotation_details.rotation_code')
                        ->join('coupons', 'coupons.id', 'rr.coupon_id')
                        ->where('coupons.code', $cart->coupon_code)
                        ->where('user_id', TM::getCurrentUserId())->select('rotation_details.id')->delete();
                }
                $customer = User::find($userId);
                $order    = Order::create([
                    'code'                           => $autoCode,
                    'order_type'                     => $input['order_type'] ?? null,
                    'status'                         => ORDER_STATUS_NEW,
                    'status_text'                    => $orderStatus->name,
                    'customer_id'                    => $customer->id ?? null,
                    'customer_point'                 => $customerPoint,
                    'customer_name'                  => $customer->name,
                    'customer_code'                  => $customer->code,
                    'customer_phone'                 => $customer->phone,
                    'session_id'                     => $session_id ?? null,
                    'note'                           => $cart->description,
                    'phone'                          => $input['phone'],
                    'shipping_address_phone'         => $input['phone'],
                    'shipping_address_full_name'     => $input['full_name'],
                    'shipping_address'               => $cart->address,
                    'shipping_address_id'            => $cart->shipping_address_id ?? null,
                    'shipping_address_ward_code'     => $ward->code ?? null,
                    'shipping_address_ward_type'     => $ward->type ?? null,
                    'shipping_address_ward_name'     => $ward_name ?? null,
                    'shipping_address_district_code' => $district->code ?? null,
                    'shipping_address_district_type' => $district->type ?? null,
                    'shipping_address_district_name' => $district_name ?? null,

                    'shipping_address_city_code' => $city->code ?? null,
                    'shipping_address_city_type' => $city->type ?? null,
                    'shipping_address_city_name' => $city_name ?? null,
                    'payment_method'             => $cart->payment_method ?? $input['payment_method'],
                    'payment_status'             => $cart->payment_status ?? $input['payment_status"'],
                    'shipping_method'            => $cart->shipping_method,
                    'shipping_method_code'       => $cart->shipping_method_code,
                    'shipping_method_name'       => $cart->shipping_method_name,
                    'shipping_service'           => $cart->shipping_service,
                    'shipping_service_name'      => $cart->service_name,
                    'extra_service'              => $cart->extra_service,
                    'saving'                     => $cart->saving ?? null,
                    'access_trade_id'            => $cart->access_trade_id ?? null,
                    'access_trade_click_id'      => $cart->access_trade_click_id ?? null,
                    'order_source'               => $cart->order_source ?? null,
                    'ship_fee'                   => $cart->ship_fee_down,
                    'ship_fee_start'             => $cart->ship_fee_start,
                    'estimated_deliver_time'     => $cart->estimated_deliver_time,
                    'lading_method'              => $input['lading_method'] ?? $cart->lading_method,
                    'total_weight'               => $cart->total_weight ?? 0,
                    'intersection_distance'      => $cart->intersection_distance ?? 0,
                    'invoice_city_code'          => $input['invoice_city_code'] ?? null,
                    'invoice_city_name'          => $input['invoice_city_name'] ?? null,
                    'invoice_district_code'      => $input['invoice_district_code'] ?? null,
                    'invoice_district_name'      => $input['invoice_district_name'] ?? null,
                    'invoice_ward_code'          => $input['invoice_ward_code'] ?? null,
                    'invoice_ward_name'          => $input['invoice_ward_name'] ?? null,
                    'invoice_street_address'     => $input['invoice_street_address'] ?? null,
                    'invoice_company_name'       => $input['invoice_company_name'] ?? null,
                    'invoice_company_email'      => $input['invoice_company_email'] ?? null,
                    'invoice_tax'                => $input['invoice_tax'] ?? null,
                    'invoice_company_address'    => $input['invoice_company_address'] ?? null,
                    'created_date'               => $cart->created_at,
                    'delivery_time'              => $cart->receiving_time,
                    'latlong'                    => $cart->ship_address_latlong,
                    'lat'                        => $lat_long[0] ?? 0,
                    'long'                       => $lat_long[1] ?? 0,
                    'coupon_code'                => $cart->coupon_code,
                    'total_discount'             => 0,
                    'original_price'             => $originalPrice,
                    'total_price'                => $totalPrice,
                    'sub_total_price'            => $subTotalPrice,
                    'is_freeship'                => $cart->is_freeship ?? 0,
                    'order_channel'              => $input['order_channel'],
                    'store_id'                   => $store_id,
                    'shipping_note'              => $cart->shipping_note,
                    'company_id'                 => $company_id,
                    'distributor_id'             => Arr::get($distributorToUser, 'id', null),
                    'distributor_code'           => Arr::get($distributorToUser, 'code', null),
                    'distributor_name'           => Arr::get($distributorToUser, 'name', null),
                    'distributor_email'          => Arr::get($distributorToUser, 'email', null),
                    'distributor_phone'          => Arr::get($distributorToUser, 'phone', null),
                    'distributor_postcode'       => Arr::get($distributorToUser, 'distributor_postcode', null),
                    'distributor_lat'            => Arr::get($distributorToUser, 'distributor_lat', null),
                    'distributor_long'           => Arr::get($distributorToUser, 'distributor_long', null),
                    'is_active'                  => 1,
                    'seller_id'                  => $input['seller_id'] ?? $cart->seller_id,
                    'seller_code'                => $input['seller_code'] ?? $cart->seller_code,
                    'seller_name'                => $input['seller_name'] ?? $cart->seller_name,
                    'total_info'                 => json_encode($cart->total_info),
                    'free_item'                  => !empty($cart->free_item) ? json_encode($cart->free_item) : null, 'transfer_confirmation' => $input['payment_method'] == 'bank_transfer' ? 0 : 1,
                    'outvat'                     => !empty($input['invoice_company_name']) ? 1 : 0,
                    'qr_scan'                    => $cart->qr_scan ?? 0,
                    'status_crm'                 => ORDER_STATUS_CRM_PENDING,
                    'created_by'                 => $userId
                ]);
                foreach ($cart->details as $detail) {
                    OrderDetail::create([
                        'order_id'           => $order->id,
                        'product_id'         => $detail->product_id,
                        'product_code'       => $detail->product_code,
                        'product_name'       => $detail->product_name,
                        'product_category'   => $detail->product_category,
                        'qty'                => $detail->quantity,
                        'price'              => $detail->price,
                        'discount'           => $detail->promotion_price ?? 0,
                        'special_percentage' => empty($detail->special_percentage) && !empty($detail->promotion_price) ? round(($detail->promotion_price / $detail->price) * 100) : round($detail->special_percentage),
                        'real_price'         => $detail->price,
                        'price_down'         => 0,
                        'total'              => $detail->total,
                        'note'               => $detail->note,
                        'status'             => ORDER_HISTORY_STATUS_PENDING,
                        'is_active'          => $is_active ?? 1,
                    ]);
                }

                // Update Sold Count
                //                 $this->orderModel->updateProductSold($order);
                if (!empty($cart->distributor_code)) {
                    $distributor = User::model()->where('code', $cart->distributor_code)->first();
                    if (!empty($distributor->qty_max_day)) {
                        $countOrderInDistributor           = Order::model()->where('distributor_code', $cart->distributor_code)->whereBetween('created_at', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')])->count();
                        $distributor->qty_remaining_single = $distributor->qty_max_day - $countOrderInDistributor;
                        $distributor->update();
                    }
                }
                if (!empty($cart->promotion_info)) {
                    foreach ($cart->promotion_info as $promotion) {
                        $this->orderModel->createPromotionTotal($promotion, $order, $cart);
                    }
                }
                //Save Info Customer
                $cusInfo = CustomerInformation::where([
                    'phone'    => $customer->phone,
                    'store_id' => $store_id
                ])->first();
                if ($cusInfo) {
                    $cusInfo->name           = $customer->name ?? null;
                    $cusInfo->phone          = $customer->phone ?? null;
                    $cusInfo->email          = $customer->email ?? null;
                    $cusInfo->address        = $input['street_address'] ?? null;
                    $cusInfo->city_code      = $input['city_code'] ?? null;
                    $cusInfo->store_id       = $store_id;
                    $cusInfo->district_code  = $input['district_code'] ?? null;
                    $cusInfo->ward_code      = $input['ward_code'] ?? null;
                    $cusInfo->full_address   = $input['shipping_address'] ?? null;
                    $cusInfo->street_address = $input['street_address'] ?? null;
                    $cusInfo->update();
                } else {
                    CustomerInformation::insert(
                        [
                            'name'          => $input['full_name'] ?? null,
                            'phone'         => $input['phone'] ?? null,
                            'email'         => $input['email'] ?? null,
                            'address'       => $input['street_address'] ?? null,
                            'city_code'     => $input['city_code'] ?? null,
                            'store_id'      => $store_id,
                            'district_code' => $input['district_code'] ?? null,
                            'ward_code'     => $input['ward_code'] ?? null,
                            'full_address'  => $input['shipping_address'] ?? null,
                        ]
                    );
                }
                $this->orderModel->updateOrderStatusHistory($order);
                $cart->details->each(function ($detail) {
                    $detail->delete();
                });
                $paramQuoteGrab    = $cart->log_quote_grab ?? null;
                $responseQuoteGrab = $cart->log_quote_response_grab ?? null;
                try {
                    $this->writeLogGrab($order->code, $paramQuoteGrab, $responseQuoteGrab);
                } catch (\Exception $exception) {

                }
                $cart->delete();
                if (!empty($order->coupon_code)) {
                    $couponHistory              = new CouponHistory();
                    $couponHistory->order_id    = $order->id;
                    $couponHistory->user_id     = $order->customer_id;
                    $couponHistory->coupon_code = $order->coupon_code;
                    $couponHistory->save();
                }
                //                // Send Email
                //                $company = Company::model()->where('id', $company_id)->first();
                //                $order = Order::with(['customer', 'distributor', 'store', 'details.product.unit'])->where('id', $order->id)->first();
                //                $customer_email = !empty($input['email']) ? $input['email'] : ($order->customer->email ?? null);
                //                $store_email = $order->store->email_notify;
                //                $distributor_email = $order->distributor->email ?? null;
                //                $customer_new_email = $order->customer->email ?? null;
                //                if (!empty($customer_email)) {
                //                    if ($customer_new_email == $customer_email) {
                //                        $this->dispatch(new SendCustomerMailNewOrderJob($customer_new_email, [
                //                            'logo' => $company->avatar,
                //                            'support' => $company->email,
                //                            'company_name' => $company->name,
                //                            'order' => $order,
                //                        ]));
                //                    } else {
                //                        $this->dispatch(new SendCustomerMailNewOrderJob($customer_new_email, [
                //                            'logo' => $company->avatar,
                //                            'support' => $company->email,
                //                            'company_name' => $company->name,
                //                            'order' => $order,
                //                        ]));
                //                        $this->dispatch(new SendCustomerMailNewOrderJob($customer_email, [
                //                            'logo' => $company->avatar,
                //                            'support' => $company->email,
                //                            'company_name' => $company->name,
                //                            'order' => $order,
                //                        ]));
                //                    }
                //                }
                //                if (!empty($store_email)) {
                //                    dispatch(new SendStoreMailNewOrderJob($store_email, [
                //                        'logo' => $company->avatar,
                //                        'support' => $company->email,
                //                        'company_name' => $company->name,
                //                        'order' => $order,
                //                    ]));
                //                }
                //
                //                if (!empty($distributor_email)) {
                //                    $this->dispatch(new SendHUBMailNewOrderJob($distributor_email, [
                //                        'logo' => $company->avatar,
                //                        'support' => $company->email,
                //                        'company_name' => $company->name,
                //                        'order' => $order,
                //                        'link_to' => TM::urlBase("/user/order/" . $order->id),
                //                    ]));
                //                }
                //                $this->sendNotifyConfirmOrder($request, $order);

                DB::commit();
            } catch (\Exception $ex) {
                DB::rollBack();
                $response = TM_Error::handle($ex);
                $errors   .= $response['message'];
                return $errors;
            }
            return $order;
        } else {
            try {
                $session_id = $session;
                $groupGuest = UserGroup::where(['company_id' => $company_id, 'code' => 'GUEST'])->first();
                DB::beginTransaction();
                $errors = "";
                // Create Temp User
                $user = User::model()
                    ->where('store_id', $store_id)
                    ->where('company_id', $company_id)
                    ->where('phone', "{$input['phone']}")->first();
                if ($user) {
                    if ($user->is_active == '1') {
                        return Message::get("unique_phone", Message::get("phone"));
                    }
                    $user->company_id = $company_id;
                    $user->store_id   = $store_id;
                    $user->email      = $user->email ?? $input['email'];
                    $user->name       = $input['full_name'];
                    $user->role_id    = USER_ROLE_GUEST_ID;
                    $user->type       = USER_TYPE_CUSTOMER;
                    $user->group_id   = $groupGuest->id;
                    $user->group_code = $groupGuest->code;
                    $user->group_name = $groupGuest->name;
                    $user->save();
                    $profile = Profile::model()->where('user_id', $user->id)->first();
                    if (!$profile) {
                        $profile             = new Profile();
                        $profile->user_id    = $user->id;
                        $profile->phone      = $input['phone'];
                        $profile->is_active  = 1;
                        $profile->created_by = $user->id;
                    }
                } else {
                    $user              = new User();
                    $user->phone       = $input['phone'];
                    $user->password    = "NOT-VERIFY-ACCOUNT";
                    $user->email       = $input['email'] ?? null;
                    $user->code        = $input['phone'];
                    $user->name        = $input['full_name'];
                    $user->role_id     = USER_ROLE_GUEST_ID;
                    $user->note        = "Mua hàng không đăng nhập";
                    $user->type        = USER_TYPE_CUSTOMER;
                    $user->register_at = date("Y-m-d H:i:s");
                    $user->company_id  = $company_id;
                    $user->store_id    = $store_id;
                    $user->group_id    = $userGroup->id ?? null;
                    $user->group_code  = $userGroup->code ?? null;
                    $user->group_name  = $userGroup->name ?? null;
                    $user->is_active   = 0;
                    $user->created_by  = null;
                    $user->save();
                    $profile             = new Profile();
                    $profile->user_id    = $user->id;
                    $profile->phone      = $input['phone'];
                    $profile->is_active  = 1;
                    $profile->created_by = $user->id;
                }

                $profile->email      = $user->email;
                $full                = explode(" ", $input['full_name']);
                $profile->full_name  = $input['full_name'];
                $profile->first_name = trim($full[count($full) - 1]);
                unset($full[count($full) - 1]);
                $profile->last_name     = trim(implode(" ", $full));
                $profile->address       = $input['street_address'] ?? null;
                $profile->city_code     = $input['city_code'] ?? null;
                $profile->district_code = $input['district_code'] ?? null;
                $profile->ward_code     = $input['ward_code'] ?? null;
                $profile->updated_by    = $user->id;
                $profile->save();


                $cart = Cart::with('details.product.priceDetail')->where(['session_id' => $session_id])->first();

                if (empty($cart)) {
                    return Message::get("V003", Message::get('carts'));
                }
                $orderStatus = OrderStatus::model()->where([
                    'company_id' => $user->company_id,
                    'code'       => ORDER_STATUS_NEW,
                ])->first();
                if (empty($orderStatus)) {
                    return Message::get("V002", "status");
                }

                $totalPrice = $originalPrice = $subTotalPrice = 0;
                //                $freeShip      = false;
                $customerPoint = null;

                foreach ($cart->details as $detail) {
                    $originalPrice += Arr::get(
                        $detail->product,
                        'priceDetail.price',
                        Arr::get($detail->product, 'price', 0)
                    );
                }

                foreach ($cart->total_info as $item) {
                    switch ($item['code']) {
                        case 'sub_total':
                            $subTotalPrice = $item['value'];
                            break;
                        case 'total':
                            $totalPrice = $item['value'];
                            break;
                        default:
                            //                            if (!empty($item['act_type']) && $item['act_type'] == 'free_shipping') {
                            //                                $freeShip = true;
                            //                            }

                            if (!empty($item['act_type']) && $item['act_type'] == 'accumulate_point') {
                                $customerPoint = $item['value'];
                            }
                            break;
                    }
                }

                $city                      = City::where('code', $input['city_code'])->first();
                $city_name                 = Arr::get($city, 'type') . " " . Arr::get($city, 'name');
                $district                  = District::where('code', $input['district_code'])->first();
                $district_name             = Arr::get($district, 'type') . " " . Arr::get($district, 'name');
                $ward                      = Ward::where('code', $input['ward_code'])->first();
                $ward_name                 = Arr::get($ward, 'type') . " " . Arr::get($ward, 'name');
                $input['shipping_address'] = "{$input['street_address']}, {$ward_name}, {$district_name}, {$city_name}";
                $autoCode                  = $this->getAutoOrderCode();
                $lat_long                  = $cart->ship_address_latlong ? explode(",", $cart->ship_address_latlong) : null;
                $distributorToUser         = null;
                //                if (!empty($input['distributor_code'])) {
                //                    $distributorToUser = User::model()
                //                        ->where([
                //                            'code'       => $input['distributor_code'],
                //                            'company_id' => $user->company_id,
                //                            'store_id'   => $user->store_id,
                //                        ])->first();
                //                }
                $order = Order::create([
                    'code'                           => $autoCode,
                    //                'order_type'                     => ORDER_TYPE_GROCERY,
                    'order_type'                     => ORDER_TYPE_GUEST,
                    'status'                         => ORDER_STATUS_NEW,
                    'status_text'                    => $orderStatus->name,
                    'customer_id'                    => $user->id,
                    'customer_name'                  => $user->name,
                    'customer_code'                  => $user->code,
                    'customer_email'                 => $input['email'] ?? null,
                    'customer_phone'                 => $user->phone,
                    'session_id'                     => $session_id ?? null,
                    'customer_point'                 => $customerPoint,
                    'note'                           => $input['note'] ?? null,
                    'phone'                          => $input['phone'],
                    'shipping_address'               => $input['shipping_address'] ?? null,
                    'shipping_address_id'            => $cart->shipping_address_id,
                    'shipping_address_ward_code'     => $ward->code ?? null,
                    'shipping_address_ward_type'     => $ward->type ?? null,
                    'shipping_address_ward_name'     => $ward_name ?? null,
                    'shipping_address_district_code' => $district->code ?? null,
                    'shipping_address_district_type' => $district->type ?? null,
                    'shipping_address_district_name' => $district_name ?? null,
                    'shipping_address_city_code'     => $city->code ?? null,
                    'shipping_address_city_type'     => $city->type ?? null,
                    'shipping_address_city_name'     => $city_name ?? null,
                    'payment_method'                 => $cart->payment_method ?? $input['payment_method'],
                    'payment_status'                 => $cart->payment_status ?? $input['payment_status'],
                    'shipping_method'                => $cart->shipping_method,
                    'shipping_method_code'           => $cart->shipping_method_code,
                    'shipping_method_name'           => $cart->shipping_method_name,
                    'shipping_service'               => $cart->shipping_service,
                    'shipping_note'                  => $cart->shipping_note,
                    'shipping_service_name'          => $cart->service_name,
                    'extra_service'                  => $cart->extra_service,
                    'saving'                         => $cart->saving ?? null,
                    'access_trade_id'                => $cart->access_trade_id ?? null,
                    'access_trade_click_id'          => $cart->access_trade_click_id ?? null,
                    'order_source'                   => $cart->order_source ?? null,
                    //                    'ship_fee'             => $input['ship_fee'] ?? $cart->ship_fee_down,
                    'ship_fee'                       => $cart->ship_fee_down ?? 0,
                    'ship_fee_start'                 => $cart->ship_fee_start,
                    'estimated_deliver_time'         => $cart->estimated_deliver_time,
                    'lading_method'                  => $input['lading_method'] ?? $cart->lading_method,
                    'total_weight'                   => $cart->total_weight ?? 0,
                    'intersection_distance'          => $cart->intersection_distance ?? 0,
                    'invoice_city_code'              => $input['invoice_city_code'] ?? null,
                    'invoice_city_name'              => $input['invoice_city_name'] ?? null,
                    'invoice_district_code'          => $input['invoice_district_code'] ?? null,
                    'invoice_district_name'          => $input['invoice_district_name'] ?? null,
                    'invoice_ward_code'              => $input['invoice_ward_code'] ?? null,
                    'invoice_ward_name'              => $input['invoice_ward_name'] ?? null,
                    'invoice_street_address'         => $input['invoice_street_address'] ?? null,
                    'invoice_company_name'           => $input['invoice_company_name'] ?? null,
                    'invoice_company_email'          => $input['invoice_company_email'] ?? null,
                    'invoice_tax'                    => $input['invoice_tax'] ?? null,
                    'invoice_company_address'        => $input['invoice_company_address'] ?? null,
                    'created_date'                   => $cart->created_at,
                    'delivery_time'                  => $cart->receiving_time,
                    'latlong'                        => $cart->ship_address_latlong,
                    'lat'                            => $lat_long[0] ?? 0,
                    'long'                           => $lat_long[1] ?? 0,
                    'coupon_code'                    => $cart->coupon_code,
                    //                    'total_discount'                 => $subTotalPrice - $totalPrice,
                    'total_discount'                 => 0,
                    'original_price'                 => $originalPrice,
                    'total_price'                    => $totalPrice,
                    'sub_total_price'                => $subTotalPrice,
                    'is_freeship'                    => $cart->is_freeship ?? 0,
                    'order_channel'                  => $input['order_channel'],
                    'distributor_id'                 => Arr::get($distributorToUser, 'id', null),
                    'distributor_code'               => Arr::get($distributorToUser, 'code', null),
                    'distributor_name'               => Arr::get($distributorToUser, 'name', null),
                    'distributor_email'              => Arr::get($distributorToUser, 'email', null),
                    'distributor_phone'              => Arr::get($distributorToUser, 'phone', null),
                    'distributor_postcode'           => Arr::get($distributorToUser, 'distributor_postcode', null),
                    'distributor_lat'                => Arr::get($distributorToUser, 'distributor_lat', null),
                    'distributor_long'               => Arr::get($distributorToUser, 'distributor_long', null),
                    'store_id'                       => $store_id,
                    'company_id'                     => $company_id,
                    'is_active'                      => 1,
                    'seller_id'                      => $input['seller_id'] ?? $cart->seller_id,
                    'seller_code'                    => $input['seller_code'] ?? $cart->seller_code,
                    'seller_name'                    => $input['seller_name'] ?? $cart->seller_name,
                    'total_info'                     => json_encode($cart->total_info),
                    'free_item'                      => !empty($cart->free_item) ? json_encode($cart->free_item) : null, 'transfer_confirmation' => $input['payment_method'] == 'bank_transfer' ? 0 : 1,
                    'outvat'                         => !empty($input['invoice_company_name']) ? 1 : 0,
                    'qr_scan'                        => $cart->qr_scan ?? 0,
                    'status_crm'                     => ORDER_STATUS_CRM_PENDING
                ]);
                foreach ($cart->details as $detail) {
                    OrderDetail::create([
                        'order_id'           => $order->id,
                        'product_id'         => $detail->product_id,
                        'product_code'       => $detail->product_code,
                        'product_name'       => $detail->product_name,
                        'product_category'   => $detail->product_category,
                        'qty'                => $detail->quantity,
                        'price'              => $detail->price,
                        'discount'           => $detail->promotion_price ?? 0,
                        'special_percentage' => empty($detail->special_percentage) && !empty($detail->promotion_price) ? round(($detail->promotion_price / $detail->price) * 100) : round($detail->special_percentage),
                        'real_price'         => $detail->price,
                        'price_down'         => 0,
                        'total'              => $detail->total,
                        'note'               => $detail->note,
                        'status'             => ORDER_HISTORY_STATUS_PENDING,
                        'is_active'          => 1,
                    ]);
                }
                if (!empty($cart->distributor_code)) {
                    $distributor = User::model()->where('code', $cart->distributor_code)->first();
                    if (!empty($distributor->qty_max_day)) {
                        $countOrderInDistributor           = Order::model()->where('distributor_code', $cart->distributor_code)->whereBetween('created_at', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')])->count();
                        $distributor->qty_remaining_single = $distributor->qty_max_day - $countOrderInDistributor;
                        $distributor->update();
                    }
                }
                // Update Sold Count
                $this->orderModel->updateProductSold($order);

                if (!empty($cart->promotion_info) && $cart->promotion_info != "[]") {
                    foreach ($cart->promotion_info as $promotion) {
                        $this->orderModel->createPromotionTotal($promotion, $order, $cart);
                    }
                }
                //Save Info Customer
                $cusInfo = CustomerInformation::where([
                    'phone'    => "{$input['phone']}",
                    'store_id' => $user->store_id
                ])->first();

                if ($cusInfo) {
                    $cusInfo->name          = $input['full_name'] ?? null;
                    $cusInfo->email         = $input['email'] ?? null;
                    $cusInfo->phone         = $input['phone'] ?? null;
                    $cusInfo->address       = $input['street_address'] ?? null;
                    $cusInfo->city_code     = $input['city_code'] ?? null;
                    $cusInfo->store_id      = $user->store_id ?? null;
                    $cusInfo->district_code = $input['district_code'] ?? null;
                    $cusInfo->ward_code     = $input['ward_code'] ?? null;
                    $cusInfo->full_address  = $input['shipping_address'] ?? null;
                    $cusInfo->update();
                } else {
                    CustomerInformation::insert(
                        [
                            'name'          => $input['full_name'] ?? null,
                            'email'         => $input['email'] ?? null,
                            'phone'         => $input['phone'] ?? null,
                            'address'       => $input['street_address'] ?? null,
                            'city_code'     => $input['city_code'] ?? null,
                            'store_id'      => $user->store_id ?? null,
                            'district_code' => $input['district_code'] ?? null,
                            'ward_code'     => $input['ward_code'] ?? null,
                            'full_address'  => $input['shipping_address'] ?? null,
                        ]
                    );
                }

                // Update users
                $updateUser = User::model()->where([
                    'phone'      => "{$input['phone']}",
                    'store_id'   => $user->store_id,
                    'company_id' => $user->company_id,
                ])->first();

                if ($updateUser) {
                    $last_name  = explode(" ", $input['full_name']);
                    $last_name  = array_pop($last_name);
                    $first_name = explode(" ", $input['full_name']);
                    $first_name = array_shift($first_name);
                    $short_name = "{$first_name} {$last_name}";
                    if (!empty($input['email'])) {
                        if ($updateUser->email != $input['email']) {
                            $chekEmail = User::model()->where([
                                'email'    => $input['email'],
                                'store_id' => $user->store_id
                            ])->first();
                            if (!empty($chekEmail)) {
                                throw new \Exception(Message::get("V007", "Enail: #{$input['email']}"));
                            }
                        }
                    }
                    $updateUser->name = $input['full_name'] ?? $updateUser->name;
                    // $updateUser->email = $input['email'] ?? $updateUser->email;

                    $codeSMS               = mt_rand(100000, 999999);
                    $updateUser->password  = password_hash($codeSMS, PASSWORD_BCRYPT);
                    $updateUser->is_active = 1;
                    $this->sendSMSCode(Message::get('SMS-REGISTER-ORDER', $codeSMS), $user->phone);
                    $updateUser->save();

                    $profile = Profile::model()->where('user_id', $updateUser->id)->first();
                    if (!empty($profile)) {
                        // $profile->email         = $input['email'] ?? $profile->email;
                        $profile->first_name    = $first_name ?? $profile->first_name;
                        $profile->last_name     = $last_name ?? $profile->last_name;
                        $profile->short_name    = $short_name ?? $profile->short_name;
                        $profile->full_name     = $input['full_name'] ?? $profile->full_name;
                        $profile->city_code     = $input['city_code'] ?? $profile->city_code;
                        $profile->ward_code     = $input['ward_code'] ?? $profile->ward_code;
                        $profile->district_code = $input['district_code'] ?? $profile->district_code;
                        $profile->address       = $input['address'] ?? $updateUser->profile->address;
                        $profile->save();
                    }
                }

                $cart->details->each(function ($detail) {
                    $detail->delete();
                });
                $cart->delete();
                if (!empty($order->coupon_code)) {
                    $couponHistory              = new CouponHistory();
                    $couponHistory->order_id    = $order->id;
                    $couponHistory->user_id     = $order->customer_id;
                    $couponHistory->price       = $order->total_price;
                    $couponHistory->coupon_code = $order->coupon_code;
                    $couponHistory->save();
                }
                //                // Send Email
                //                $company = Company::model()->where('id', $user->company_id)->first();
                //                $order = Order::with(['store', 'customer', 'distributor', 'details.product.unit'])->where('id', $order->id)->first();
                //                $customer_email = $input['email'] ?? null;
                //                $email_notify = $order->store->email_notify ?? null;
                //                $distributor_email = $order->distributor->email ?? null;
                //                $customer_new_email = $order->customer->email ?? null;
                //
                //                if (!empty($customer_email)) {
                //                    if ($customer_new_email == $customer_email) {
                //                        $this->dispatch(new SendCustomerMailNewOrderJob($customer_new_email, [
                //                            'logo' => $company->avatar,
                //                            'support' => $company->email,
                //                            'company_name' => $company->name,
                //                            'order' => $order,
                //                        ]));
                //                    } else {
                //                        $this->dispatch(new SendCustomerMailNewOrderJob($customer_new_email, [
                //                            'logo' => $company->avatar,
                //                            'support' => $company->email,
                //                            'company_name' => $company->name,
                //                            'order' => $order,
                //                        ]));
                //                        $this->dispatch(new SendCustomerMailNewOrderJob($customer_email, [
                //                            'logo' => $company->avatar,
                //                            'support' => $company->email,
                //                            'company_name' => $company->name,
                //                            'order' => $order,
                //                        ]));
                //                    }
                //                }
                //
                //                if (!empty($email_notify)) {
                //                    $this->dispatch(new SendStoreMailNewOrderJob($email_notify, [
                //                        'logo' => $company->avatar,
                //                        'support' => $company->email,
                //                        'company_name' => $company->name,
                //                        'order' => $order,
                //                    ]));
                //                }
                //                if (!empty($distributor_email)) {
                //                    $this->dispatch(new SendHUBMailNewOrderJob($distributor_email, [
                //                        'logo' => $company->avatar,
                //                        'support' => $company->email,
                //                        'company_name' => $company->name,
                //                        'order' => $order,
                //                        'link_to' => TM::urlBase("/user/order/" . $order->id),
                //                    ]));
                //                }
                DB::commit();
            } catch (\Exception $ex) {
                DB::rollBack();
                $response = TM_Error::handle($ex);
                return $response['message'];
            }
            return $order;
        }
    }

    /**
     * Send SMS
     *
     * @param $message
     * @param $phone
     * @return \Psr\Http\Message\StreamInterface|null
     */
    private function sendSMSCode($message, $phone)
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

    private function logPaymentRequest($orderCode, $type, $input = [])
    {

        LogPaymentRequest::create([
            'order_code'   => $orderCode ?? null,
            'reponse_json' => json_encode($input),
            'type'         => $type,
            'created_at'   => date('Y-m-d H:i:s')
        ]);
    }

    private function writeLogGrab($code, $quote, $response)
    {
        LogShippingOrder::insert([
            'order_code'           => $code,
            'type'                 => "QUOTE",
            'code_shipping_method' => null,
            'reponse_json'         => json_encode($response),
            'param_request'        => json_encode($quote),
        ]);
    }

    public function fakeStatus($orderCode)
    {
        $order = Order::where('code', $orderCode)->first();
        if (empty($order)) {
            throw new \Exception('Đơn hàng không tồn tại');
        }
        try {
            $statusDms     = array_flip(SYNC_STATUS_NAME_VIETTEL);
            $dataUpdateDMS = OrderSyncDMS::updateStatusDMS(array($order->code), "C", $order->status);
            if (!empty($dataUpdateDMS)) {
                $pushOrderStatusDms = OrderSyncDMS::callApiDms($dataUpdateDMS, "UPDATE-ORDER");
                if (!empty($pushOrderStatusDms['errors'])) {
                    foreach ($pushOrderStatusDms['errors'] as $item) {
                        \App\Supports\Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $item);
                    }
                } else {
                    if (!empty($pushOrderStatusDms)) {
                        \App\Supports\Log::logSyncDMS($order->code, null, $dataUpdateDMS ?? [], "UPDATE-STATUS", 1, $pushOrderStatusDms);
                    }
                    if (empty($pushOrderStatusDms)) {
                        \App\Supports\Log::logSyncDMS($order->code, "Connection Error", $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $pushOrderStatusDms);
                    }
                    //                    \App\Supports\Log::logSyncDMS($order->code, null, $dataUpdateDMS ?? [], "UPDATE-STATUS", 1, $pushOrderStatusDms);
                }

            }
            Order::where('code', $order->code)->update(['log_order_dms' => json_encode($dataUpdateDMS)]);
        } catch (\Exception $exception) {
            \App\Supports\Log::logSyncDMS($order->code, $exception->getMessage(), $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, null);
        }
    }

    public function fakeOrder($orderCode)
    {
        $order = Order::where('code', $orderCode)->first();
        if (empty($order)) {
            throw new \Exception('Đơn hàng không tồn tại');
        }
        try {
            $statusDms     = array_flip(SYNC_STATUS_NAME_VIETTEL);
            $dataUpdateDMS = OrderSyncDMS::updateStatusDMS(array($order->code), "C", $order->status);
            if (!empty($dataUpdateDMS)) {
                $pushOrderStatusDms = OrderSyncDMS::callApiDms($dataUpdateDMS, "CREATE-ORDER");
                if (!empty($pushOrderStatusDms['errors'])) {
                    foreach ($pushOrderStatusDms['errors'] as $item) {
                        \App\Supports\Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $dataUpdateDMS ?? [], "CREATE-ORDER", 0, $item);
                    }
                } else {
                    if (!empty($pushOrderStatusDms)) {
                        \App\Supports\Log::logSyncDMS($order->code, null, $dataUpdateDMS ?? [], "UPDATE-STATUS", 1, $pushOrderStatusDms);
                    }
                    if (empty($pushOrderStatusDms)) {
                        \App\Supports\Log::logSyncDMS($order->code, "Connection Error", $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $pushOrderStatusDms);
                    }
                    //                    \App\Supports\Log::logSyncDMS($order->code, null, $dataUpdateDMS ?? [], "CREATE-ORDER", 1, $pushOrderStatusDms);
                }

            }
            Order::where('code', $order->code)->update(['log_order_dms' => json_encode($dataUpdateDMS)]);
        } catch (\Exception $exception) {
            \App\Supports\Log::logSyncDMS($order->code, $exception->getMessage(), $dataUpdateDMS ?? [], "CREATE-ORDER", 0, null);
        }
    }
}

