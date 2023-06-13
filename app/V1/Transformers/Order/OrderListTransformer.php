<?php


namespace App\V1\Transformers\Order;


use App\Image;
use App\Order;
use App\Setting;
use App\Supports\TM_Error;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class OrderListTransformer extends TransformerAbstract
{
    protected $order_source;

    public function __construct()
    {
        $setting_order_source = Setting::where('code', 'CONFIG-ORDER-SOURCE')->first();
        $data = $setting_order_source->data ? json_decode($setting_order_source->data, true) : null;
        $this->order_source = array_map(function($item) {
            return [
                'key' => $item['key'],
                'name' => $item['name']
            ];
        }, $data);
    }

    public function transform(Order $order)
    {
        $details          = $order->details;
        $allPriceDiscount = 0;
        $totalOrder       = 0;
        foreach ($details as $detail) {
            $allPriceDiscount += ($detail->discount ?? 0) * $detail->qty;
            $totalOrder       += $detail->price * $detail->qty;
        }
        $orderStatus = object_get($order, 'getStatus.name');
//            $payment_history = $order->vpvirtualaccount ?? [];
        $payment_amount = $order->vpvirtualaccount->collect_ammount ?? null;
        if(!in_array($order->payment_method,[PAYMENT_METHOD_BANK,PAYMENT_METHOD_VPB,PAYMENT_METHOD_CASH])){
            if($order->payment_status == 1){
                $payment_amount = $order->total_price - $order->total_discount ?? null;
            }
        }
        $order_source_name = null;
        foreach($this->order_source as $os) {
            if(!empty($order->order_source)){
                if($os['key'] == $order->order_source) {
                    $order_source_name = $os['name'];
                    break;
                }
            }
        }
        try {
            return [
                'id'                    => $order->id,
                'code'                  => $order->code,
                'order_type'            => $order->order_type,
                'code_shipping'         => array_get($order, 'shippingOrder.code_type_ghn'),
                'status'                => $order->status,
                'payment_amount'        => !empty($payment_amount) ? (string)$payment_amount : null,
                'qr_scan'               => $order->qr_scan,
                // 'status_name'           =>U !empty($orderStatus) ? $orderStatus : ORDER_STATS_NAME[$order->status],
                'status_name'           => $order->status_text,
                'status_text'           => $order->status_text,
                'customer_id'           => $order->customer_id,
                'order_channel'         => $order->order_channel,
                'customer_name'         => object_get($order, 'customer.name') ?? $order->customer_name,
                'customer_phone'        => object_get($order, 'customer.phone') ?? $order->customer_phone,
                'customer_group_name'   => object_get($order, 'customer.group_name'),
                'customer_group_code'   => object_get($order, 'customer.group_code'),
                'total_price_formatted' => number_format($order->total_price - $order->total_discount ?? 0) . " đ",
                'payment_status'        => $order->payment_status,
                'transfer_confirmation' => $order->transfer_confirmation,
                'intersection_distance'      => number_format(round($order->intersection_distance ?? 0)) . " km",
                'distributor_name'      => object_get($order, 'distributor.name'),
                'created_at'            => date("d-m-Y H:i", strtotime($order->created_at)),
                'updated_at'            => date("d-m-Y H:i", strtotime($order->updated_at)),
                'seller_id'             => object_get($order, 'seller_id', null),
                'seller_code'           => Arr::get($order, 'seller.code', null),
                'seller_name'           => Arr::get($order, 'seller.profile.full_name', null),
                'leader_id'             => object_get($order, 'leader_id', null),
                'leader_code'           => Arr::get($order, 'leader.code', null),
                'leader_name'           => Arr::get($order, 'leader.profile.full_name', null),
                'status_crm'            => $order->status_crm,
                'is_active'             => $order->is_active,
                'outvat'                => $order->outvat,
                'pr_existing_money'     => number_format(!empty($payment_amount) ? (float)$payment_amount - (float)($order->total_price-$order->total_discount) : 0). " đ",
                'order_source'          => !empty($order->order_source) ? $order_source_name : null
            ];
        }
        catch (\Exception $ex) {
            // dd($ex);
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
