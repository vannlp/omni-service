<?php

/**
 * Date: 2021-01-06
 * Time: 15:46
 */

namespace App\V1\Library;

use App\AccessTradeSetting;
use App\Category;
use App\CheckActiveAccesstrade;
use App\LogAccesstradeOrder;
use App\Supports\Message;
use GuzzleHttp\Client;
use App\Order;
use App\OrderDetail;

class Accesstrade
{
    const CREATE = 'v1/create-conversion';
    const UPDATE = 'v1/update-conversion-status';
    const ACTIVE = 'IS_ACTIVE_ACCESSTRADE';
    public static final function handle($data, $method, $token)
    {
        if (!in_array($method, ['CREATE', 'UPDATE'])) {
            throw new \Exception(Message::get("V002", 'method'));
        }
        $uri    = $method == 'CREATE' ? self::CREATE : self::UPDATE;
        $client = new Client(['verify' => false]);
        try {
            $response = $client->post(env('ACCESS_TRADE_API') . $uri, [
                'headers' => [
                    'Content-Type'           => 'application/json',
                    'X-At-User'              => env('X_AT_USER_ACCESS_TRADE'),
                    'X-Network-Id'           => env('X_NETWORK_ID_ACCESS_TRADE'),
                    'X-At-User-Type'         => env('X_AT_USER_TYPE_ACCESS_TRADE'),
                    'X-At-User-Access-Token' => $token
                ],
                'body'    => json_encode($data),
            ]);
        } catch (\Exception $exception) {
            return ['status' => 'error', 'success' => false, 'message' => $exception->getMessage()];
        }
        $response = $response->getBody()->getContents() ?? null;
        $response = !empty($response) ? json_decode($response, true) : [];
        return $response;
    }

    // Tao moi don hang cho accesstrade
    public static final function create($order, $accesstrade_id, $click_id)
    {
        try {
            $check_active = CheckActiveAccesstrade::where('code', self::ACTIVE)->where('is_active', 1)->first();
            if (empty($check_active)) {
                return [];
            }

            $order = Order::model()->where(['id' => $order->id])->first();
            if (empty($order) || empty($order->id)) {
                throw new \Exception(Message::get("V070"));
            }

            $checkLog = LogAccesstradeOrder::where('order_id', $order->id)->first();
            if (!empty($checkLog)) {
                throw new \Exception(Message::get("V069"));
            }

            $checkAccesstrade   = AccesstradeSetting::where('id', $accesstrade_id)->first();
            if (empty($checkAccesstrade)) {
                throw new \Exception(Message::get("V068"));
            }

            $order_detail_temp    = [];
            $total_price_temp     = null;
            $total_discount_temp  = null;
            $category_name_temp   = null;
            $campaign_id          = $checkAccesstrade->campaign_id;
            $order_detail         = OrderDetail::model()->where('order_id', $order->id)->get();
            $accesstrade_settings = AccessTradeSetting::where('campaign_id', $campaign_id)->first();

            foreach ($order_detail as $key => $value) {
                $product_category = explode(',', $value->product->category_ids);
                if (in_array($accesstrade_settings->category_id, $product_category)) {
                    $value->product->category_ids  = $accesstrade_settings->category_id;
                    $category_name_temp            = Category::where('id', $accesstrade_settings->category_id)->first()->name;
                } else {
                    $value->product->category_ids  = 'other';
                    $category_name_temp            = 'other';
                }

                $order_detail_temp[] = [
                    'id'            => $value->id,
                    'sale_amount'   => (((float) $value->price * (float) $value->qty) - ((float) $value->discount * (float) $value->qty)),
                    'quantity'      => (float) $value->qty,
                    'price'         => $value->price,
                    'sku'           => $value->product->sku,
                    'currency'      => 'VND',
                    'discount'      => (float) $value->discount * (float) $value->qty,
                    'name'          => $value->product_name,
                    'category_id'   => $value->product->category_ids,
                    'category_name' => $category_name_temp,
                    'url'           => env('URL_NUTIFOOD_DEV') . 'product/' . $value->product->slug,
                ];
                $total_price_temp += (((float) $value->price * (float) $value->qty) - ((float) $value->discount * (float) $value->qty));
                $total_discount_temp += ((float) $value->discount * (float) $value->qty);
            }

            $action_time = round(microtime(true) * 1000);
            $currency = "VND";
            $total_discount = [
                'amount'   => (float) $total_discount_temp,
                'currency' => $currency,
            ];
            $total_sale_amount = [
                'amount'   => (float) $total_price_temp,
                'currency' => $currency,
            ];
            $data = [
                'click_id'          => $click_id,
                'campaign_id'       => $campaign_id,
                'order_id'          => $order->id,
                'action_time'       => $action_time,
                'total_discount'    => $total_discount,
                'total_sale_amount' => $total_sale_amount,
                'conversion_parts'  => $order_detail_temp
            ];

            $token         = $checkAccesstrade->key;
            $result        = self::handle($data, 'CREATE', $token);
            $conversion_id = $result['data']['conversion']['conversion_id'] ?? null;
            if (isset($result['status']) && $result['status'] == 'success') {
                $log_accesstrade    = new LogAccesstradeOrder();
                $log_accesstrade->create([
                    'order_id'      => $order->id,
                    'click_id'      => $click_id,
                    'campaign_id'   => $campaign_id,
                    'conversion_id' => $conversion_id,
                    'data'          => json_encode($result),
                    'status'        => ORDER_STATUS_PENDING,
                ]);

                $order->access_trade_id       = $accesstrade_id;
                $order->access_trade_click_id = $click_id;
                $order->conversion_id         = $conversion_id;
                $order->save();
                return ['status' => 'success', 'success' => true, 'data' => $result['data']];
            } else {
                throw new \Exception(Message::get("V066"));
            }
        } catch (\Exception $e) {
            return ['status' => 'error', 'success' => false, 'message' => $e->getMessage()];
        }
    }

    // cap nhat lai trang thai cho don hang accesstrade 
    public static final function update($order, $status, $reason)
    {
        try {
            $check_active = CheckActiveAccesstrade::where('code', 'IS_ACTIVE_ACCESSTRADE')->where('is_active', 1)->first();
            if (empty($check_active) || $check_active->is_active != 1) {
                return [];
            }

            $order = Order::model()->where(['id' => $order->id])->first();
            if (empty($order) || empty($order->id)) {
                throw new \Exception(Message::get("V070"));
            }

            if (!in_array($status, ['PENDING', 'APPROVED', 'REJECTED', 'HOLD', 'PRE_APPROVED'])) {
                throw new \Exception(Message::get("V002", 'Status'));
            }

            $log_accesstrade = LogAccesstradeOrder::model()->where('conversion_id', $order->conversion_id)->first();
            if (empty($log_accesstrade)) {
                throw new \Exception(Message::get("V067"));
            }

            // APPROVED ROI KHONG CHO APPROVED NUA
            if (($status == 'PRE_APPROVED' || $status == 'APPROVED') && $log_accesstrade->status == 'APPROVED') {
                return [];
            }

            // REJECTED ROI KHONG CHO REJECTED NUA
            if ($status == 'REJECTED' && $log_accesstrade->status == 'REJECTED') {
                return [];
            }

            $data = [
                "conversion_id" => $order->conversion_id,
                "status"        => strtolower($status),
                "reason"        => $reason,
            ];

            $token  = AccesstradeSetting::where('id', $order->access_trade_id)->first()->key;
            if ($token == null) {
                throw new \Exception(Message::get("V068"));
            }

            $result = self::handle($data, 'UPDATE', $token);
            if ($result['status'] == 'success') {
                $log_accesstrade->update([
                    'data'   => json_encode($result),
                    'status' => $status,
                ]);
                return ['status' => 'success', 'success' => true, 'data' => $result['data']];
            } else {
                throw new \Exception(Message::get("V066"));
            }
        } catch (\Exception $e) {
            return ['status' => 'error', 'success' => false, 'message' => $e->getMessage()];
        }
    }
}
