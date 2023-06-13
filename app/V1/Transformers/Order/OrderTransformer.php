<?php

/**
 * User: Administrator
 * Date: 21/12/2018
 * Time: 10:01 PM
 */

namespace App\V1\Transformers\Order;


use App\Coupon;
use App\Image;
use App\MasterData;
use App\Order;
use App\OrderDetail;
use App\ProductComment;
use App\Supports\TM_Error;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;
use App\TM;
use Illuminate\Support\Facades\DB;

class OrderTransformer extends TransformerAbstract
{
    const UNIT_FORMAT
    = [
        PROMOTION_TYPE_AUTO       => 'đ',
        PROMOTION_TYPE_COMMISSION => 'đ',
        PROMOTION_TYPE_DISCOUNT   => 'đ',
        PROMOTION_TYPE_POINT      => 'điểm',
        PROMOTION_TYPE_FLASH_SALE => 'đ',
        PROMOTION_TYPE_CODE       => 'đ',
        PROMOTION_TYPE_GIFT       => 'đ',
    ];

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
            // if (!empty($folder_path)) {
            //     $folder_path = str_replace("/", ",", $folder_path);
            // } else {
            //     $folder_path = "uploads";
            // }
            // $folder_path      = url('/v0') . "/img/" . $folder_path;
            //            $allPriceDiscount += ($detail->discount ?? 0) * $detail->qty;
            $allPriceDiscount += $detail->discount ?? 0;
            $priceDown        = object_get($detail, 'product.price_down', 0);
            $total_price_down += $detail->price_down * $detail->qty;
            //            $totalOrderTmp    = ($detail->price - $detail->discount) * $detail->qty;
            $totalOrderTmp    = ($detail->price * $detail->qty) - ($detail->discount * $detail->qty);
            $totalPriceDetail += $totalOrderTmp;
            $totalOrder       += $detail->price * $detail->qty;
            $waitingQty       += $detail->waiting_qty;
            $detailQty        += $detail->qty;

            $is_comment = 0;
            if (!empty(TM::getCurrentUserId())) {
                $countProductComment = ProductComment::model()->where('product_id', $detail->product_id)
                    ->where('user_id', TM::getCurrentUserId())
                    ->where('type', PRODUCT_COMMENT_TYPE_RATE)
                    ->count();

                $countProductOrder = OrderDetail::where('product_id', $detail->product_id)->whereHas('order', function ($query) {
                    $query->where('customer_id', TM::getCurrentUserId());
                    $query->where('status', ORDER_STATUS_COMPLETED);
                })->count();

                if ($countProductComment != $countProductOrder) {
                    $is_comment = 1;
                }
            }

            $orderDetails[] = [
                'id'                        => $detail->id,
                'order_id'                  => $detail->order_id,
                'product_id'                => $detail->product_id,
                'product_code'              => object_get($detail, 'product.code'),
                'product_name'              => object_get($detail, 'product.name'),
                'product_unit_id'           => object_get($detail, 'product.getUnit.id'),
                'product_unit_code'         => object_get($detail, 'product.getUnit.code'),
                'product_unit_name'         => object_get($detail, 'product.getUnit.name'),
                'thumbnail'                 => !empty($fileCode) ? env('GET_FILE_URL') . $fileCode : null,
                'product_price_down'        => $priceDown,
                'product_down_rate'         => $detail->price != 0 ? $priceDown * 100 / $detail->price : 0,
                'product_down_from'         => object_get($detail, 'product.down_from'),
                'product_down_to'           => object_get($detail, 'product.down_to'),
                'qty'                       => $detail->qty,
                'qty_sale'                  => !empty($detail->qty_sale) ? (int)$detail->qty_sale : null,
                'slug'                      => object_get($detail, 'product.slug'),
                'shipped_qty'               => $detail->shipped_qty,
                'waiting_qty'               => $detail->waiting_qty,
                'price'                     => round($detail->price),
                'price_formatted'           => number_format(round($detail->price)) . " đ",
                'promotion_price'           => round($detail->discount > 0 ? $detail->price - $detail->discount : 0),
                'promotion_price_formatted' => number_format(round($detail->discount > 0 ? $detail->price - $detail->discount : 0)) . " đ",
                'price_down'                => $detail->price_down,
                'special_percentage'        => $detail->special_percentage ?? null,
                'real_price'                => $detail->real_price,
                'total'                     => round($totalOrderTmp ?? 0),
                'total_formatted'           => number_format(round($totalOrderTmp)) . " đ",
                'note'                      => $detail->note,
                'discount'                  => round($detail->discount),
                'discount_formatted'        => number_format(round($detail->discount)),
                'status'                    => $detail->status,
                'status_name'               => ORDER_STATUS_NAME[$detail->status] ?? null,
                //                'next_status'               => NEXT_STATUS_ORDER[$detail->status] ?? null,
                //                'next_status_name'          => !empty(NEXT_STATUS_ORDER[$detail->status]) ? ORDER_STATUS_NAME[NEXT_STATUS_ORDER[$detail->status]] : null,
                'is_comment'                => $is_comment,
                'commented'                 => $detail->commented,
                'item_value'         => $detail->item_value ?? null,
                'item_type'          => $detail->item_type ?? null,
            ];
        }
        //        if($order->payment_status==1 && $order->payment_method==PAYMENT_METHOD_BANK){
        $payment_history = $order->vpvirtualaccount ?? [];
        //            $payment_amount = $order->vpvirtualaccount->collect_ammount ?? null;
        //            $checkOverPayment = $payment_amount-(round($order->total_price - $order->total_discount));
        //            if($checkOverPayment > 0){
        //                $isPaymentBankTransfer = 2;
        //            }
        //            if($checkOverPayment < 0){
        //                $isPaymentBankTransfer = 3;
        //            }
        //        }
        $rated_shipping = 0;
        $check_comment_order = ProductComment::model()->where([
            'order_id' => $order->id,
            'type' => PRODUCT_COMMENT_TYPE_RATESHIPPING
        ])->first();
        if (!empty($check_comment_order)) {
            $rated_shipping = 1;
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

        // $promotion_totals = $order->promotionTotalsNotFlashSale->map(function ($item) {
        //     return [
        //         'id'                 => $item->id,
        //         'code'               => $item->promotion_code,
        //         'title'              => $item->promotion_name,
        //         'promotion_act_type' => $item->promotion_act_type,
        //         'promotion_type'     => $item->promotion_type,
        //         'value'              => round($item->value),
        //         'text'               => "-" .number_format(round($item->value)) . " " . self::UNIT_FORMAT[$item->promotion_type ?? PROMOTION_TYPE_AUTO],
        //         // 'value'              => $text = $item->promotion_type == "DISCOUNT" ? -1 * round($item->value) : round($item->value),
        //         // 'text'               => "-" .number_format(round($text)) . " " . self::UNIT_FORMAT[$item->promotion_type ?? PROMOTION_TYPE_AUTO],
        //     ];
        // });

        $promotion_totals = $order->promotionTotals->map(function ($item) {
            return [
                'id'                 => $item->id,
                'code'               => $item->promotion_code,
                'title'              => $item->promotion_name,
                'promotion_act_type' => $item->promotion_act_type,
                'promotion_type'     => $item->promotion_type,
                'value'              => round($item->value),
                'text'               => "-" . number_format(round($item->value)) . " " . self::UNIT_FORMAT[$item->promotion_type ?? PROMOTION_TYPE_AUTO],
            ];
        });

        $totals           = $promotion_totals->toArray();


        $key_point = array_search('TICH-DIEM', array_column($totals ?? [], 'code'));
        if (isset($key_point) && is_integer($key_point) == true) {
            $promotion_point = $totals[$key_point];
            unset($totals[$key_point]);
        }
        // array_unshift($totals, [
        //     "code"  => "sub_total",
        //     "title" => "Tổng tiền hàng",
        //     "text"  => number_format(round($totalPriceDetail)) . " đ",
        //     "value" => round($totalPriceDetail),
        // ]);

        array_unshift($totals, [
            "code"  => "sub_total",
            "title" => "Tổng tiền hàng",
            "text"  => number_format(round($order->sub_total_price)) . " đ",
            "value" => round($order->sub_total_price),
        ]);

        // $totals[]         = [
        //     "code"  => "sub_total",
        //     "title" => "Tạm tính",
        //     "text"  => number_format($order->sub_total_price ?? $order->total_price) . " đ",
        //     "value" => $order->sub_total_price ?? $order->total_price,
        // ];

        /// coupon
        if (!empty($order->total_info)) {
            $total_info = $order->total_info ? json_decode($order->total_info, true) : [];
            $coupon_key = array_search('coupon', array_column($total_info ?? [], 'code'));
            if (isset($coupon_key) && is_integer($coupon_key) == true) {
                $totals[] = $total_info[$coupon_key];
            }

            $voucher_key = array_search('voucher', array_column($total_info ?? [], 'code'));
            if (isset($voucher_key) && is_integer($voucher_key) == true) {
                $totals[] = $total_info[$voucher_key];
            }
            $coupon_ship = array_search('coupon_delivery', array_column($total_info ?? [], 'code'));

            if (isset($coupon_ship) && is_integer($coupon_ship) == true) {
                array_splice($totals, 1, 0,  array($total_info[$coupon_ship]));
            }

            $fee_ship = array_search('fee_ship', array_column($total_info ?? [], 'code'));
            if (isset($fee_ship) && is_integer($fee_ship) == true) {
                array_splice($totals, 1, 0,  array($total_info[$fee_ship]));
                //                $totals[] = $total_info[$fee_ship];

            }
        }
        $totals[] = [
            "code"  => "total",
            "title" => "Thành tiền",
            "text"  => number_format(round($order->total_price - $order->total_discount)) . " đ",
            "value" => round($order->total_price - $order->total_discount),
        ];
        if ($order->saving) {
            $totals[] = [
                "code"  => "saving",
                "title" => "Tiết kiệm",
                "text"  => number_format(round($order->saving)) . " đ",
                "value" => round($order->saving),
            ];
        }
        if ($order->point) {
            $totals[] = [
                "code"  => "point",
                "title" => "Điểm sử dụng",
                "text"  => number_format(round($order->point)),
                "value" => round($order->point),
            ];
        }
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
        if (!empty($promotion_point)) {
            $totals[] = $promotion_point;
        }

        if($order->discount_admin_input_type){
            if($order->discount_admin_input_type == DISCOUNT_ADMIN_TYPE_MONEY &&  $order->discount_admin_input > 0) {
                $totals[] = [
                    'code'  => 'money',
                    'title' => 'Giảm tiền cố định',
                    'text'  => number_format($order->discount_admin_input) . ' đ',
                    'value' => $order->discount_admin_input ?? 0
                ];
            }
            if($order->discount_admin_input_type == DISCOUNT_ADMIN_TYPE_PERCENTAGE) {
                $total_info = json_decode($order->total_info, true);
                foreach($total_info as $tf) {
                    if($tf['code'] == 'percentage'){
                        $totals[] = $tf;
                        break;
                    }
                }
                
                // $money_percentage = $this->cartSubTotal * ($order->discount_admin_input / 100);
                
            }
        }
        // if(!empty(json_decode($order->free_item)) && $order->free_item != '[]'){
        //     foreach(json_decode($order->free_item) as $key => $item){
        //         foreach($item->text as $prod){
        //             $freeitem[] =
        //                 [
        //                     'is_exchange' => $item->is_exchange,
        //                     'code' => $item->code,
        //                     'title' => $item->title,
        //                     'act_type' => $item->act_type,
        //                     'text' => $prod,
        //                     'value' => $item->value
        //                 ];
        //         }
        //     };
        // }

        if (!empty(json_decode($order->free_item)) && $order->free_item != '[]') {
            foreach (json_decode($order->free_item) as $key => $item) {
                foreach ($item->text as $prod) {
                    if (!empty($prod->qty_gift)) {
                        $freeitem[] =
                            [
                                'code' => $item->code,
                                'title' => $item->title,
                                'act_type' => $item->act_type,
                                'text' => $prod,
                                'value' => $prod->qty_gift * $item->value
                            ];
                    } else {
                        $freeitem[] =
                            [
                                'code' => $item->code,
                                'title' => $item->title,
                                'act_type' => $item->act_type,
                                'text' => $prod,
                                'value' => $item->value
                            ];
                    }
                }
            };
        }

        $equal = $detailQty == $waitingQty ? true : false;

        try {
            // dd($order->free_item_admin);

            return [
                'id'                         => $order->id,
                'code'                       => $order->code,
                'order_type'                 => $order->order_type,
                'status'                     => $order->status,
                'status_name'                => ORDER_STATUS_NAME[$order->status],
                'status_grab'                => array_get($order, 'shippingOrder.status_shipping_method'),
                'customer_id'                => $order->customer_id,
                'qr_scan'                    => $order->qr_scan,
                'promotion_totals'           => $order->promotionTotals->map(function ($item) {
                    return [
                        'id'                 => $item->id,
                        'promotion_code'     => $item->promotion_code,
                        'promotion_name'     => $item->promotion_name,
                        'promotion_act_type' => $item->promotion_act_type,
                        'promotion_type'     => $item->promotion_type,
                        'value'              => round($item->value),
                        'value_formatted'    => number_format(round($item->value)) . " " . self::UNIT_FORMAT[$item->promotion_type ?? PROMOTION_TYPE_AUTO],
                    ];
                }),
                'code_shipping'                => array_get($order, 'shippingOrder.code_type_ghn'),
                'customer_name'              => object_get($order, 'customer.name') ?? $order->customer_name,
                'customer_code'              => object_get($order, 'customer.code') ?? $order->customer_code,
                'customer_email'             => object_get($order, 'customer.email') ?? $order->customer_email,
                'customer_phone'             => object_get($order, 'customer.phone') ?? $order->customer_phone,
                'customer_lat'               => object_get($order, 'customer_lat'),
                'customer_long'              => object_get($order, 'customer_long'),
                'customer_postcode'          => object_get($order, 'customer_postcode'),
                'customer_point'             => $order->customer_point,
                'customer_star'              => $order->customer_star,
                'comment_for_customer'       => $order->comment_for_customer,
                'partner_id'                 => $order->partner_id,
                //                'seller_id'            => Arr::get($order, 'seller_id', null),
                //                'seller_name'          => Arr::get($order, 'seller.profile.full_name', null),
                //                'partner_name'         => object_get($order, 'partner.name'),
                //                'partner_code'         => object_get($order, 'partner.code'),
                //                'partner_email'        => object_get($order, 'partner.email'),
                //                'partner_phone'        => object_get($order, 'partner.phone'),
                //                'partner_point'        => $order->partner_point,
                //                'partner_star'         => $order->partner_star,
                //                'comment_for_partner'  => $order->comment_for_partner,
                'note'                       => $order->note,
                'shipping_address_id'        => $order->shipping_address_id,
                'street_id'                  => $order->street_id,
                'street_address'             => !empty($order->street_address) ? $order->street_address : Arr::get($order, 'getShippingInfoByPhone.street_address', null),
                'shipping_address_ward'      => $order->shipping_address_ward_name,
                'shipping_address_ward_code' => $order->shipping_address_ward_code,

                'shipping_address_district'      => $order->shipping_address_district_name,
                'shipping_address_district_code' => $order->shipping_address_district_code,

                'shipping_address_city'      => $order->shipping_address_city_name,
                'shipping_address_city_code' => $order->shipping_address_city_code,

                'shipping_address_phone'     => !empty($order->shipping_address_phone) ? $order->shipping_address_phone : Arr::get($order, 'getShippingInfoByPhone.phone', null),
                'shipping_address_full_name' => !empty($order->shipping_address_full_name) ? $order->shipping_address_full_name : Arr::get($order, 'getShippingInfoByPhone.name', null),
                'order_channel'              => $order->order_channel,
                'shipping_address'           => $order->shipping_address ?? $order->street_address,
                'coupon_code'                => $order->coupon_code,
                'point'                      => $order->point,
                'sub_total_price'            => round($order->sub_total_price),
                'total_price'                => round($order->total_price - $order->total_discount),
                //                'total_price_formatted'      => number_format($totalOrder - $allPriceDiscount ?? 0) . " đ",
                'total_price_formatted'      => number_format(round($order->total_price - $order->total_discount)) . " đ",
                'total_discount'             => round($allPriceDiscount),
                'payment_status'             => $order->payment_status,
                'payment_method'             => $order->payment_method,
                'payment_code'               => $order->payment_code ?? null,
                'payment_method_name'        => PAYMENT_METHOD_NAME[$order->payment_method] ?? null,
                'payment_history'            => $payment_history ?? [],
                'payment_amount'             => $payment_amount ?? null,
                'shipping_method'            => $order->shipping_method,
                'shipping_method_code'       => $order->shipping_method_code,
                'shipping_method_name'       => $order->shipping_method_name,
                'shipping_service'           => $order->shipping_service,
                'shipping_service_name'      => $order->shipping_service_name,
                'extra_service'              => $order->shipping_service,
                'ship_fee'                   => $order->ship_fee,
                'estimated_deliver_time'     => $order->estimated_deliver_time,
                'shipping_note'              => $order->shipping_note,
                'is_free_ship'               => $order->is_freeship,
                'intersection_distance'      => number_format(round($order->intersection_distance ?? 0)) . " km",
                'saving'                     => (int)$order->saving ?? 0,
                'saving_fortmated'           => number_format(round($order->saving ?? 0)) . " đ",
                'outvat'                     => $order->outvat,
                'invoice_city_code'          => $order->invoice_city_code,
                'invoice_city_name'          => $order->invoice_city_name,
                'invoice_district_code'      => $order->invoice_district_code,
                'invoice_district_name'      => $order->invoice_district_name,
                'invoice_ward_code'          => $order->invoice_ward_code,
                'invoice_ward_name'          => $order->invoice_ward_name,
                'invoice_street_address'     => $order->invoice_street_address,
                'invoice_company_name'       => $order->invoice_company_name,
                'invoice_company_email'      => $order->invoice_company_email,
                'invoice_tax'                => $order->invoice_tax,
                'invoice_company_address'    => $order->invoice_company_address,
                'tracking_url'               => $order->shippingOrder->tracking_url ?? null,

                'updated_date'          => !empty($order->updated_date) ? date(
                    "d-m-Y",
                    strtotime($order->updated_date)
                ) : null,
                'created_date'          => date("d-m-Y H:i", strtotime($order->created_date)),
                'completed_date'        => !empty($order->completed_date) ? date(
                    "d-m-Y H:i",
                    strtotime($order->completed_date)
                ) : null,
                'order_date'            => !empty($order->completed_date) ? date(
                    "d-m-Y H:i",
                    strtotime($order->completed_date)
                ) : null,
                'delivery_time'         => !empty($order->delivery_time) ? date(
                    "Y-m-d H:i",
                    strtotime($order->delivery_time)
                ) : null,
                'latlong'               => $order->latlong,
                'lat'                   => (float)$order->lat,
                'long'                  => (float)$order->long,
                'canceled_reason'       => Arr::get($order, 'canceled_reason', null),
                'canceled_reason_admin' => Arr::get($order, 'canceled_reason_admin', null),
                'cancel_reason'         => Arr::get($order, 'cancel_reason', null),
                'district_code'         => $order->district_code,
                'district_fee'          => $order->district_fee,
                'geometry'              => [
                    'latitude'  => (float)$order->lat,
                    'longitude' => (float)$order->long,
                ],
                'images'                => $img,
                'approver'              => $order->approver,
                'approver_name'         => object_get($order, 'approverUser.name'),
                'total_price_down'      => $total_price_down,
                'partner_ship_fee'      => $order->partner_ship_fee,
                'partner_revenue_total' => $order->partner_revenue_total,
                'partner_income'        => $order->partner_revenue_total + $order->partner_ship_fee,
                'shine_revenue_total'   => $order->shine_revenue_total,
                'shine_income'          => $order->shine_revenue_total + (empty($order->partner_ship_fee) ? $order->district_fee : 0),
                'details'               => $orderDetails,
                'totals'                => $totals,
                'discount'              => $order->discount,
                'equal'                 => $equal,
                'rated_shipping'        => $rated_shipping,

                'distributor_id'       => $order->distributor_id,
                'distributor_code'     => object_get($order, 'distributor.code'),
                'distributor_name'     => object_get($order, 'distributor.name'),
                'distributor_email'    => object_get($order, 'distributor.email'),
                'distributor_phone'    => object_get($order, 'distributor.phone'),
                'distributor_lat'      => object_get($order, 'distributor_lat'),
                'distributor_long'     => object_get($order, 'distributor_long'),
                'distributor_postcode' => object_get($order, 'distributor_postcode'),
                'distributor_status'   => $order->distributor_status,

                //                'sub_distributor_id'    => $order->sub_distributor_id ?? null,
                //                'sub_distributor_code'  => object_get($order, 'sub_distributor.code', null),
                //                'sub_distributor_name'  => object_get($order, 'sub_distributor.name', null),
                //                'sub_distributor_email' => object_get($order, 'sub_distributor.email', null),
                //                'sub_distributor_phone' => object_get($order, 'sub_distributor.phone', null),

                'status_histories'          => $order->statusHistories,
                'status_text'               => $order->status_text,
                'delivery_status_histories' => $order->shippingStatusHistories,
                'seller_id'                 => object_get($order, 'seller_id', null),
                'seller_code'               => Arr::get($order, 'seller.code', null),
                'seller_name'               => Arr::get($order, 'seller.profile.full_name', null),
                'leader_id'                 => object_get($order, 'leader_id', null),
                'leader_code'               => Arr::get($order, 'leader.code', null),
                'leader_name'               => Arr::get($order, 'leader.profile.full_name', null),
                'status_shipping'           => Arr::get($order, 'status_shipping', null),
                'failed_reason'             => Arr::get($order, 'failed_reason', null),
                'status_crm'                => $order->status_crm,
                'crm_check'                 => $order->crm_check,
                'crm_description'           => $order->crm_description,
                'description'               => $order->description ?? null,
                'free_item'                 => $freeitem ?? [],
                'created_at' => date("d-m-Y H:i", strtotime($order->created_at)),
                'created_by' => object_get($order, 'created_by.name'),
                'updated_at' => date("d-m-Y", strtotime($order->updated_at)),
                'updated_by' => object_get($order, 'updated_by.name'),
                'free_item_admin' => $order->free_item_admin ? json_decode($order->free_item_admin) : [],
                
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
