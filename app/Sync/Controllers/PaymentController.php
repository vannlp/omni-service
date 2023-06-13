<?php

namespace App\Sync\Controllers;

use App\LogPaymentRequest;
use App\Order;
use App\PaymentLogFail;
use App\PaymentVirtualAccount;
use App\Setting;
use App\Supports\Log;
use App\Supports\Message;
use App\Sync\Validators\PaymentVirtualAccountValidator;
use App\TM;
use App\User;
use App\V1\Library\OrderSyncDMS;
use App\VirtualAccount;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\V1\Library\Accesstrade;

#Key: 1cec66d819ca2d8475444d31377635778c50e75f55499ce8915725b6129f319d
class PaymentController
{
    public function __construct(Request $request)
    {

        $headers = $request->headers->all();
        if (empty($headers['authorization'][0])) {
            throw new \Exception(Message::get("V001", "Token"));
        }

        if (strlen($headers['authorization'][0]) != 64) {
            throw new \Exception(Message::get("token_invalid"));
        }

        if ($headers['authorization'][0] != env('VPB_SYNC_KEY', null)) {
            throw new \Exception(Message::get("token_invalid"));
        }
    }

    public function returnPaymentVirtualAccount(Request $request, PaymentVirtualAccountValidator $paymentVirtualAccountValidator)
    {
        $input = $request->all();
        try {
            $this->logPaymentRequest($input['virtualAccountNumber'] ?? null, PAYMENT_METHOD_BANK, $input);
        } catch (\Exception $exception) {
        }
        $paymentVirtualAccountValidator->validate($input);
        $order = Order::model()->where('virtual_account_code', $input['virtualAccountNumber'])->where('payment_status', '!=', 1)->first();

        if (empty($order)) {
            throw new \Exception('Đơn hàng không tồn tại!');
        }
        $collectAmmount = (int)$input['collectAmmount'];
        $priceOrder     = (int)($order->total_price - $order->total_discount);
        try {
            DB::beginTransaction();
            $param
                = [
                'type'                    => PAYMENT_METHOD_BANK,
                'order_id'                => $order->id,
                'master_account_number'   => $input['masterAccountNumber'],
                'virtual_account_number'  => $input['virtualAccountNumber'],
                'payer_name'              => $input['payerName'] ?? null,
                'collect_ammount'         => $input['collectAmmount'] ?? null,
                'transaction_date'        => $input['transactionDate'] ?? null,
                'value_date'              => $input['valueDate'] ?? null,
                'transaction_id'          => $input['transactionId'] ?? null,
                'transaction_description' => $input['transactionDescription'] ?? null,
            ];

            $paymentVirtualAccount = new PaymentVirtualAccount();
            $paymentVirtualAccount->create($param);
//            if (!empty($order->vpvirtualaccount)) {
//                $priceHistoryPayment = 0;
//                foreach ($order->vpvirtualaccount as $value) {
//                    $priceHistoryPayment += $value['collect_ammount'];
//                }
//            }
//            if ($priceHistoryPayment >= $priceOrder) {
            $order->payment_method       = PAYMENT_METHOD_BANK;
            $order->payment_code         = $input['transactionId'];
            $order->virtual_account_code = null;
            $order->save();
            $account           = VirtualAccount::model()->where('code', $input['virtualAccountNumber'])->first();
            $account->order_id = null;
//                $account->is_active = 1;
            $account->save();

            //            }
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
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            PaymentLogFail::create([
                'order_id' => $order->id ?? null,
                'type'     => PAYMENT_METHOD_BANK,
                'log'      => $ex
            ]);
            return $this->syncError("{$ex}");
        }
        try {
            if ($collectAmmount - $priceOrder == 0 || $collectAmmount - $priceOrder <= 1000 || $collectAmmount - $priceOrder >= -1000) {
                $order->status_crm = ORDER_STATUS_CRM_APPROVED;
            }
            if ($collectAmmount - $priceOrder == 0) {
                $order->payment_status = 1;
            }
            if ($collectAmmount - $priceOrder < 0) {
                $order->payment_status = 3;
            }
            if ($collectAmmount - $priceOrder > 0) {
                $order->payment_status = 2;
            }
            if ($collectAmmount - $priceOrder != 0) {
                $seller_id         = null;
                $settingAutoSeller = Setting::model()->select('data')->where(['code' => 'CRMAUTO', 'company_id' => 34])->first();
                if (!empty($settingAutoSeller) && !empty(json_decode($settingAutoSeller['data'])[0]->value) && json_decode($settingAutoSeller['data'])[0]->value == 1) {
                    $seller_id = $this->getAutoSeller(34);
                }
                $order->seller_id   = !empty($seller_id) ? $seller_id->id : null;
                $order->seller_code = !empty($seller_id) ? $seller_id->code : null;
                $order->seller_name = !empty($seller_id) ? $seller_id->name : null;
                $order->leader_id   = !empty($seller_id) ? $seller_id->parent_id : null;

            }
            $order->save();
        } catch (\Exception $ex) {

        }

//        if (env('DMS_SYNC_ENABLE', 0) == 1) {
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
//                    Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                }

            }
            Order::where('code', $order->code)->update(['log_order_dms' => json_encode($syncDMS)]);
        } catch (\Exception $exception) {
            Log::logSyncDMS($order->code, $exception->getMessage(), $syncDMS ?? [], "CREATE-ORDER", 0, null);
        }
//        }

        return $this->syncSuccess('U');
    }


    /**
     * @param array $errors
     * @param string $msg
     * @param int $code
     * @return string[]
     */
    private function pushOrder($code)
    {
        try {
            $orders       = explode(',', $code);
            $order        = Order::with(['details'])->whereIn('code', $orders)->get();
            $order_detail = [];
            // $token = $this->getTokenDMSOrder();
            foreach ($order as $od) {
                foreach ($od->details as $detail) {
                    $product      = Product::find($detail->product_id);
                    $detail_order = [
                        "itemCode"      => $detail->product_code,
                        "itemShortName" => $detail->product_name,
                        "qtyBook"       => $detail->qty * $product->specification->value,
                        "salesOff"      => $detail->special_percentage,
                        "costUnit"      => $detail->price / $product->specification->value,
                        "amount"        => $detail->total
                    ];
                    array_push($order_detail, $detail_order);
                }
                $param    = [
                    "orderNumber"     => $od->code,
                    "orderType"       => 'NTS',
                    "orderDate"       => date('d-m-Y H:i:s', strtotime($od->created_at)),
                    "status"          => 'W',
                    "outName"         => $od->customer_name,
                    "address"         => $od->shipping_address,
                    "province"        => $od->getCity->code,
                    "district"        => $od->getDistrict->code,
                    "ward"            => $od->getDistrict->code . '_' . $od->getWard->code,
                    "phone"           => $od->customer_phone,
                    "paymentMethod"   => $od->payment_method != "CASH" ? 'CK' : 'TM',
                    "payemtStatus"    => $od->payment_status != 1 ? "0" : "1",
                    "note"            => $od->note ?? null,
                    "createDate"      => date('d-m-Y H:i:s', time()),
                    "createdBy"       => "NutiFoodShop",
                    "modifyDate"      => null,
                    "modifiedBy"      => null,
                    "shippingService" => !empty($od->shipping_method_code) != "DEFAULT" ? $od->shipping_method_code : "HUB",
                    "shippingOption"  => $od->shipping_note ?? null,
                    "distributorCode" => $od->distributor->group_code == "HUB" ? $od->distributor->distributor_center_code : $od->distributor_code,
                    "deliveryFee"     => $od->ship_fee ?? null,
                    "hubCode"         => $od->distributor->group_code != "DISTRIBUTOR" ? $od->distributor->name : null,
                    "saleOrderLines"  => $order_detail
                ];
                $client   = new Client();
                $response = $client->post(env("DMS_ORDER") . "/SaleOrder", [
                    'headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . env("TOKEN_DMS_ORDER")],
                    'body'    => json_encode($param)
                ]);
            }
            $response = !empty($response) ? $response->getBody()->getContents() : null;
            $status   = !empty($response) ? $response : "Đồng bộ thành công!!";
            return ['status' => $status];
        } catch (\Exception $ex) {
            return $this->responseError('Đồng bộ không thành công!!!');
        }
    }

    private function syncError($type, $errors = [], $msg = 'Something went wrong!', $code = 400)
    {
        $errors = !is_array($errors) ? [$errors] : $errors;
        return response()->json([
            'dataStatus'  => $type,
            'isSuccess'   => false,
            'errorMsg'    => $msg,
            'status_code' => $code,
            'message'     => $msg,
            'errors'      => $errors,
        ], $code);
    }

    /**
     * @param array $data
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    private function getAutoSeller($company_id)
    {

        $seller = User::model()->select('id', 'parent_id', 'code', 'name')->withCount('countOrder as order_count')->where(['company_id' => $company_id, 'is_active' => 1])
            ->whereHas('role', function ($q) {
                $q->where('code', USER_ROLE_SELLER);
            })
            ->orderBy('order_count', 'asc')
            ->first();
        return $seller;
    }

    private function syncSuccess($type, array $data = [], $code = 200)
    {
        return response()->json([
            'dataStatus'  => $type,
            'isSuccess'   => true,
            'errorMsg'    => '',
            'status_code' => $code,
            'message'     => 'Successfully!',
            'data'        => $data,
        ]);
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
}