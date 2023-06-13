<?php
/**
 * User: dai.ho
 * Date: 27/01/2021
 * Time: 10:36 AM
 */

namespace App\Sync\Transformers;

use App\Image;
use App\Order;
use App\Supports\TM_Error;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class OrderTransformer extends TransformerAbstract
{
    public function transform(Order $order)
    {
        $details          = $order->details;
        $orderDetails     = [];
        $total_price_down = 0;
        $allPriceDiscount = 0;
        $totalPriceDetail = 0;
        $totalOrder       = 0;
        $waitingQty       = 0;
        $detailQty        = 0;
        foreach ($details as $detail) {
            $fileCode = object_get($detail, 'product.file.code');
            $allPriceDiscount += ($detail->discount ?? 0) * $detail->qty;
            $priceDown        = object_get($detail, 'product.price_down', 0);
            $total_price_down += $detail->price_down * $detail->qty;
            $totalOrderTmp    = ($detail->price - $detail->discount) * $detail->qty;
            $totalPriceDetail += $totalOrderTmp;
            $totalOrder       += $detail->price * $detail->qty;
            $waitingQty       += $detail->waiting_qty;
            $detailQty        += $detail->qty;
            $orderDetails[]   = [
                'id'                 => $detail->id,
                'order_id'           => $detail->order_id,
                'product_id'         => $detail->product_id,
                'product_code'       => object_get($detail, 'product.code'),
                'product_name'       => object_get($detail, 'product.name'),
                'product_unit_id'    => object_get($detail, 'product.getUnit.id'),
                'product_unit_code'  => object_get($detail, 'product.getUnit.code'),
                'product_unit_name'  => object_get($detail, 'product.getUnit.name'),
                'thumbnail'          => !empty($fileCode) ? env('get_file_url') . $fileCode : null,
                'product_price_down' => $priceDown,
                'product_down_rate'  => $detail->price != 0 ? $priceDown * 100 / $detail->price : 0,
                'product_down_from'  => object_get($detail, 'product.down_from'),
                'product_down_to'    => object_get($detail, 'product.down_to'),
                'qty'                => $detail->qty,
                'shipped_qty'        => $detail->shipped_qty,
                'waiting_qty'        => $detail->waiting_qty,
                'price'              => $detail->price,
                'price_formatted'    => number_format($detail->price) . " đ",
                'price_down'         => $detail->price_down,
                'real_price'         => $detail->real_price,
                'total'              => $totalOrderTmp ?? 0,
                'total_formatted'    => number_format($totalOrderTmp) . " đ",
                'note'               => $detail->note,
                'discount'           => $detail->discount,
                'status'             => $detail->status,
                'status_name'        => ORDER_STATUS_NAME[$detail->status] ?? null,
                'next_status'        => NEXT_STATUS_ORDER[$detail->status] ?? null,
                'next_status_name'   => !empty(NEXT_STATUS_ORDER[$detail->status]) ? ORDER_STATUS_NAME[NEXT_STATUS_ORDER[$detail->status]] : null,
            ];
        }
        $img = [];
        if ($order->image_ids) {
            $images = explode(",", $order->image_ids);
            $images = Image::model()->whereIn('id', $images)->get();

            foreach ($images as $image) {
                $img[] = [
                    "id"  => $image->id,
                    "url" => !empty($image->url) ? url('/v0') . "/images/" . $image->url : null,
                ];
            }
        }

        $promotion_totals = $order->promotionTotals->map(function ($item) {
            return [
                'id'                 => $item->id,
                'code'               => $item->promotion_code,
                'title'              => $item->promotion_name,
                'promotion_act_type' => $item->promotion_act_type,
                'promotion_type'     => $item->promotion_type,
                'value'              => $item->value,
                'text'               => number_format($item->value)
            ];
        });
        $totals           = $promotion_totals->toArray();
        $totals[]         = [
            "code"  => "sub_total",
            "title" => "Tạm tính",
            "text"  => number_format($order->sub_total_price ?? $order->total_price) . " đ",
            "value" => $order->sub_total_price ?? $order->total_price,
        ];
        $totals[]         = [
            "code"  => "total",
            "title" => "Thành tiền",
            "text"  => number_format($order->total_price - $order->total_discount) . " đ",
            "value" => $order->total_price - $order->total_discount,
        ];

        $total_info = json_decode($order->total_info, true) ?? [];
        // dd($total_info );
        foreach($total_info as $key => $val) {
            if($val['code'] == 'coupon_admin') {
                $totals[] = $val;
            }
            if($val['code'] == 'discount_product_admin') {
                $totals[] = $val;
            }
        }

        $orderStatus = $order->getOrderStatus($order->status);
        $equal       = $detailQty == $waitingQty ? true : false;
        try {
            return [
                'id'                   => $order->id,
                'code'                 => $order->code,
                'order_type'           => $order->order_type,
                'status'               => $order->status,
                'status_name'          => !empty($orderStatus->name) ? $orderStatus->name : ORDER_STATUS_NAME[$order->status] ?? null,
                'next_status'          => null,
                'next_status_name'     => null,
                'customer_id'          => $order->customer_id,
                'promotion_totals'     => $order->promotionTotals->map(function ($item) {
                    return [
                        'id'                 => $item->id,
                        'promotion_code'     => $item->promotion_code,
                        'promotion_name'     => $item->promotion_name,
                        'promotion_act_type' => $item->promotion_act_type,
                        'promotion_type'     => $item->promotion_type,
                        'value'              => $item->value,
                        'value_formatted'    => number_format($item->value) . " đ"
                    ];
                }),
                'customer_name'        => object_get($order, 'customer.name') ?? $order->customer_name,
                'customer_code'        => object_get($order, 'customer.code') ?? $order->customer_code,
                'customer_email'       => object_get($order, 'customer.email') ?? $order->customer_email,
                'customer_phone'       => object_get($order, 'customer.phone') ?? $order->customer_phone,
                'customer_point'       => $order->customer_point,
                'customer_star'        => $order->customer_star,
                'comment_for_customer' => $order->comment_for_customer,
                'partner_id'           => $order->partner_id,
                'seller_id'            => Arr::get($order, 'seller_id', null),
                'seller_name'          => Arr::get($order, 'seller.profile.full_name', null),
                'partner_name'         => object_get($order, 'partner.profile.full_name'),
                'partner_code'         => object_get($order, 'partner.code'),
                'partner_email'        => object_get($order, 'partner.email'),
                'partner_phone'        => object_get($order, 'partner.phone'),
                'partner_point'        => $order->partner_point,
                'partner_star'         => $order->partner_star,
                'comment_for_partner'  => $order->comment_for_partner,
                'note'                 => $order->note,
                'shipping_address_id'  => $order->shipping_address_id,
                'street_id'            => $order->street_id,
                'street_address'       => (!empty($order->shipping_address_id) ? object_get($order,
                    'getShippingAddress.street_address') : !empty($order->street_address))
                    ? $order->street_address
                    : Arr::get($order,
                        'getShippingInfoByPhone.street_address', null),

                'shipping_address_ward'      => trim(object_get($order, 'getWard.type') . " " . object_get($order,
                        'getWard.name')),
                'shipping_address_ward_code' => $order->shipping_address_ward_code,

                'shipping_address_district'      => trim(object_get($order,
                        'getDistrict.type') . " " . object_get($order,
                        'getDistrict.name')),
                'shipping_address_district_code' => $order->shipping_address_district_code,

                'shipping_address_city'      => trim(object_get($order, 'getCity.type') . " " . object_get($order,
                        'getCity.name')),
                'shipping_address_city_code' => $order->shipping_address_city_code,

                'shipping_address_phone'     => (!empty($order->shipping_address_id) ? object_get($order,
                    'getShippingAddress.phone',
                    null) : !empty($order->shipping_address_phone))
                    ? $order->shipping_address_phone
                    : Arr::get($order,
                        'getShippingInfoByPhone.phone', null),
                'shipping_address_full_name' => (!empty($order->shipping_address_id) ? object_get($order,
                    'getShippingAddress.full_name',
                    null) : !empty($order->shipping_address_full_name))
                    ? $order->shipping_address_full_name
                    : Arr::get($order,
                        'getShippingInfoByPhone.name', null),
                'order_channel'              => $order->order_channel,
                'shipping_address'           => $order->shipping_address ?? $order->street_address,
                'coupon_code'                => $order->coupon_code,
                'sub_total_price'            => $order->sub_total_price,
                'total_price'                => $totalOrder - $allPriceDiscount ?? 0,
                'total_price_formatted'      => number_format($totalOrder - $allPriceDiscount ?? 0) . " đ",
                'total_discount'             => $allPriceDiscount,
                'payment_status'             => $order->payment_status,
                'payment_method'             => $order->payment_method,
                'payment_method_name'        => PAYMENT_METHOD_NAME[$order->payment_method] ?? null,
                'transfer_confirmation'      => $order->transfer_confirmation,
                'shipping_method'            => $order->shipping_method,
                'shipping_method_name'       => object_get($order, 'getShippingMethod.name', null),
                'updated_date'               => !empty($order->updated_date) ? date("d-m-Y",
                    strtotime($order->updated_date)) : null,
                'created_date'               => date("d-m-Y H:i", strtotime($order->created_date)),
                'completed_date'             => !empty($order->completed_date) ? date("d-m-Y H:i",
                    strtotime($order->completed_date)) : null,
                'delivery_time'              => !empty($order->delivery_time) ? date("Y-m-d H:i",
                    strtotime($order->delivery_time)) : null,
                'latlong'                    => $order->latlong,
                'lat'                        => (float)$order->lat,
                'long'                       => (float)$order->long,
                'canceled_reason'            => Arr::get($order, 'canceled_reason', null),
                'district_code'              => $order->district_code,
                'district_fee'               => $order->district_fee,
                'geometry'                   => [
                    'latitude'  => (float)$order->lat,
                    'longitude' => (float)$order->long,
                ],
                'images'                     => $img,
                'approver'                   => $order->approver,
                'approver_name'              => object_get($order, 'approverUser.profile.full_name'),
                'total_price_down'           => $total_price_down,
                'partner_ship_fee'           => $order->partner_ship_fee,
                'partner_revenue_total'      => $order->partner_revenue_total,
                'partner_income'             => $order->partner_revenue_total + $order->partner_ship_fee,
                'shine_revenue_total'        => $order->shine_revenue_total,
                'shine_income'               => $order->shine_revenue_total + (empty($order->partner_ship_fee) ? $order->district_fee : 0),
                'details'                    => $orderDetails,
                'totals'                     => $totals,
                'discount'                   => $order->discount,
                'equal'                      => $equal,

                'distributor_id'     => $order->distributor_id,
                'distributor_code'   => object_get($order, 'distributor.code'),
                'distributor_name'   => object_get($order, 'distributor.name'),
                'distributor_email'  => object_get($order, 'distributor.email'),
                'distributor_phone'  => object_get($order, 'distributor.phone'),
                'distributor_status' => $order->distributor_status,

                'status_histories' => $order->statusHistories,

                'created_at' => date("d-m-Y H:i", strtotime($order->created_at)),
                'created_by' => object_get($order, 'created_by.profile.full_name'),
                'updated_at' => date("d-m-Y", strtotime($order->updated_at)),
                'updated_by' => object_get($order, 'updated_by.profile.full_name'),
                'free_item_admin' => $order->free_item_admin ? json_decode($order->free_item_admin) : []
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
