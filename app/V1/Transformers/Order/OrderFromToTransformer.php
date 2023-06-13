<?php

namespace App\V1\Transformers\Order;

use App\Order;
use App\Product;
use App\PromotionTotal;
use App\Supports\TM_Error;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class OrderFromToTransformer extends TransformerAbstract
{
    public function transform(Order $order)
    {
        try {
            $order_detail = [];
//            $total        = 0;
            $subTotal = 0;
            $totalDis = 0;
            foreach ($order->details as $detail) {
                // if ($detail->special_percentage != 0 && $detail->discount != 0) {
                //     $special_percentage = $detail->special_percentage;
                //     if (empty($order->saving)) {
                //         $total += $detail->discount;
                //     }
                // } else {
                $special_percentage = 100 - ((($detail->total / $detail->qty) / $detail->price) * 100);
//                    if (empty($order->saving)) {
//                        $total += round((($special_percentage / 100) * $detail->price), 4);
//                    }
                // }
                $unitCode = Arr::get($detail, 'product.getUnit.code', "HOP");
                $unitName = Arr::get($detail, 'product.getUnit.name', "Hộp");
                $unitCode = $unitCode == "HOPGIAY" ? "HOP" : $unitCode;
                $spValue       = Arr::get($detail, 'product.specification.value', 0);
                $totalOrderTmp = ($detail->price * $detail->qty) - ($detail->discount * $detail->qty);
                $detail_order  = [
                    "itemCode"        => Arr::get($detail, 'product.code'),
                    "itemShortName"   => Arr::get($detail, 'product.name'),
                    "bookQty"         => $detail->qty * $spValue,
                    "discountPercent" => round($special_percentage, 4),
                    "unitCost"        => $unitCost = $spValue != 0 ? $detail->price / $spValue : 0,
                    "productUnitCode" => $unitCode,
                    "productUnitName" => $unitName,
                    "totalCost"       => round($totalOrderTmp ?? 0),//$totalCost = ($unitCost * ($detail->qty * $spValue)) - (($special_percentage / 100) * $unitCost * ($detail->qty * $spValue))
                ];
//                $subTotal     += $detail->total;
                $subTotal += ($detail->qty ?? 0) * ($detail->price ?? 0);
                array_push($order_detail, $detail_order);
            }
            if (!empty(json_decode($order->free_item)) && $order->free_item != '[]') {
                foreach (json_decode($order->free_item) as $key => $item) {
                    foreach ($item->text as $prod) {
                        if (!empty($prod->qty_gift)) {
                            $order_detail[]
                                = [
                                'itemCode'        => $prod->product_code,
                                //                                    'itemShortName' => $prod->product_name,
                                'itemShortName'   => $prod->title_gift ?? $prod->product_name,
                                //                                    'bookQty' => $prod->qty_gift * $item->value,
                                'bookQty'         => ($item->value ?? 1) * ($prod->specification_value ?? 1),
                                'discountPercent' => 0,
                                'unitCost'        => 0,
                                'productUnitCode' => $prod->unit_code,
                                'productUnitName' => $prod->unit_name,
                                'totalCost'       => 0
                            ];
                        } else {
                            $order_detail[]
                                = [
                                'itemCode'        => $prod->product_code,
                                'itemShortName'   => $prod->title_gift ?? $prod->product_name,
                                'bookQty'         => ($item->value ?? 1) * ($prod->specification_value ?? 1),
                                'discountPercent' => 0,
                                'unitCost'        => 0,
                                'productUnitCode' => $prod->unit_code,
                                'productUnitName' => $prod->unit_name,
                                'totalCost'       => 0
                            ];
                        }
                    }
                };
            }
            $totalDis       = PromotionTotal::model()->where('order_id', $order->id)->where('promotion_act_type', 'order_sale_off')->sum('value');
            $totalDisInLine = PromotionTotal::model()->where('order_id', $order->id)->where('promotion_act_type', 'sale_off_on_products')->sum('value');

            // $dataTotalInfo = json_decode($order->total_info, true);
            // $orderSaleOff  = array_filter($dataTotalInfo, function ($f) {
            //     if (!empty($f['act_type']) && $f['act_type'] == 'order_sale_off') {
            //         return $f;
            //     }
            // });
            // if (!empty($orderSaleOff)) {
            //     $totalDis = (int)array_values($orderSaleOff)[0]['value'];
            // }

            $param = [
                "orderNumber"     => $order->code,
                "orderType"       => !empty($order->order_type) ? $order->order_type : $order->customer->group_code,
                "orderChannel"    => $order->order_channel ?? null,
                "orderDate"       => date('d-m-Y H:i:s', strtotime($order->created_at)),
                "status"          => $order->status,
                "statusCRM"       => $order->status_crm,
                "canceled_reason" => $order->canceled_reason,
                "customerName"    => $order->customer_name,
                "address"         => $order->shipping_address,
                "province"        => $order->getCity->code,
                "district"        => $order->getDistrict->code,
                "ward"            => $order->getDistrict->code . '_' . $order->getWard->code,
                "phone"           => $order->customer_phone,
                "paymentMethod"   => $order->payment_method,
                "paymentStatus"   => (string)$order->payment_status,
                "note"            => $order->note ?? null,
                "creationDate"    => date('d-m-Y H:i:s', strtotime($order->created_at)),
                "creationBy"      => "NutiFoodShop",
                "lastUpdateDate"  => date('d-m-Y H:i:s', strtotime($order->updated_at)) ?? null,
                "lastUpdateBy"    => $order->updated_by->name ?? null,
                "einvoice"        => !empty($order->invoice_company_name) ? "Y" : "N",
                "shippingService" => $order->shipping_method_code != "DEFAULT" ? $order->shipping_method_code : "HUB",
                "shippingOption"  => $order->shipping_note ?? null,
                "distributorCode" => $order->distributor->group_code == "HUB" ? $order->distributor->distributor_center_code : $order->distributor_code,
                "deliveryFee"     => (int)$order->ship_fee ?? null,
                "hubCode"         => $order->distributor->group_code != "DISTRIBUTOR" ? $order->distributor->code : null,
                "subTotalPrice"   => round($subTotal - $totalDisInLine),
                "totalPrice"      => (int)$order->total_price,//$subTotal + (int)$order->ship_fee -  $totalDis ?? 0,
                "totalDiscount"   => $totalDis,
                "saleOrderLines"  => $order_detail
            ];
            return $param;
        }
        catch (\Exception $exception) {
            $response = TM_Error::handle($exception);
            throw new \Exception($response['message']);
        }
    }

}