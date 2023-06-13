<?php

namespace App\V1\Library;

use App\CheckActiveAccesstrade;
use App\Order;
use App\Product;
use App\PromotionTotal;
use App\Supports\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class OrderSyncDMS
{
    const ACTIVE             = 'IS_ACTIVE_ORDER_DMS';
    const ACTIVE_DISTRIBUTOR = 'IS_DEMO_DISTRIBUTOR';

    public static function dataOrder(array $order, $status)
    {
        if (empty(self::checkStatus())) {
            return [];
        }
        try {
            $vat          = 8;
            $orders       = Order::model()->whereIn('code', $order)->get();

            foreach ($orders as $order) {
                $order_detail         = [];
                // $subTotal             = 0;
                $disCountProduct      = 0;
                $totalDiscountProduct = 0;
                $totalDetail          = 0;
                $discountCombo        = 0;
                $combo        = [];
                $freeItem = [];
                foreach ($order->details as $detail) {
                    $spValue              = Arr::get($detail, 'product.specification.value', 0); //Quy cách sản phẩm trên detail
                    $totalOrderTmp        = ($detail->price * $detail->qty) - ($detail->discount * $detail->qty);
                    $totalDetail          += $detail->qty;
                    $isCombo              = Arr::get($detail, 'product.is_combo', 0);
                    $isOdd                = Arr::get($detail, 'product.is_odd', 0);
                    $isComboMulti         = Arr::get($detail, 'product.is_combo_multi', 0);
                    $productName          = Arr::get($detail, 'product.name', '');
                    $productCode          = Arr::get($detail, 'product.code');
                    $productCodeComboParent     = Arr::get($detail, 'product.combo_code_from', '');
                    $productNameComboParent     = Arr::get($detail, 'product.combo_name_from', '');
                    $productNamePush      = empty($isCombo) ? $productName : "$productNameComboParent($productName)";
                    $productCodePush      = empty($isCombo) ? $productCode : $productCodeComboParent;

                    if (empty($isCombo) && empty($isOdd) && empty($isComboMulti)) {
                        $disCountProduct      = $detail->discount * $detail->qty;
                        $totalDiscountProduct += $disCountProduct;
                        $detail_order         = [
                            "productCode"   => $productCodePush,
                            "productName"   => $productNamePush,
                            "quantity"      => (int)$detail->qty * $spValue,
                            "convfact"      => (int)$spValue,
                            "price"         => (float)$detail->price,
                            "discount"      => (float)$detail->discount,
                            "totalDiscount" => (float)($detail->discount * $detail->qty),
                            //                        "amount"          => (float)round($totalOrderTmp ?? 0),
                            "amount"        => (float)round($detail->price * $detail->qty ?? 0),
                            "amountNotVat"  => (float)(round(($detail->price * $detail->qty) - (($detail->price * $detail->qty) * ($vat / 100) ?? 0))),
                            "priceNotVat"   => (float)($detail->price - (($detail->price) * $vat / 100)),
                            "vat"           => $vat,
                            "total"         => (float)round($totalOrderTmp ?? 0),
                            "isFreeItem"    => 0,
                            "programName"   => null,
                            "barcode"       => Arr::get($detail, 'product.barcode'),

                        ];
                        // $subTotal             += ($detail->qty ?? 0) * ($detail->price ?? 0);
                        array_push($order_detail, $detail_order);
                    }
                    if ($isCombo) {
                        $spComboValue = Arr::get($detail, 'product.getProductCombo.specification.value', 0); // Quy cách combo sản phẩm gốc
                        $priceDefault = Arr::get($detail, 'product.getProductCombo.price', 0);
                        $priceOriginCombo   = Arr::get($detail, 'combo_price_from', 0); // Giá của sản phẩm gốc khi đặt hàng
                        $priceOriginCombo   = $priceOriginCombo > 0 ? $priceOriginCombo : $priceDefault;
                        $priceOriginCombo   = $priceOriginCombo / $spComboValue; // Giá 1 item gốc . Giá gốc / quy cách
                        $quantity           = (int)$detail->qty * $spValue;
                        $discountCombo      = $priceOriginCombo * $quantity - ($detail->qty * ($detail->price / $spValue));
                        $totalDiscountProduct += $discountCombo;
                        $totalTmp           = ($priceOriginCombo * $quantity) - ($detail->discount * $detail->qty);
                        $combo[] = [
                            'promotionCode'  => null,
                            'promotionName'  => "Chiết khấu theo " . $productName,
                            'promotionPrice' => round($discountCombo),
                            'type'           => 1
                        ];
                        $detail_order         = [
                            "productCode"   => $productCodePush,
                            "productName"   => $productNamePush,
                            "quantity"      => (int)$detail->qty * $spValue,
                            "convfact"      => (int)$spComboValue,
                            "price"         => (float)round($priceOriginCombo * $spValue),
                            "discount"      => (float)$detail->discount,
                            "totalDiscount" => (float)($detail->discount / $spValue * $quantity),
                            "amount"        => (float)round($priceOriginCombo * $quantity),
                            "amountNotVat"  => (float)(round(($priceOriginCombo * $quantity) - (($priceOriginCombo * $quantity) * ($vat / 100) ?? 0))),
                            "priceNotVat"   => (float)round(($priceOriginCombo * $spValue - (($priceOriginCombo * $spValue) * $vat / 100))),
                            "vat"           => $vat,
                            "total"         => (float)round($totalTmp ?? 0),
                            "isFreeItem"    => 0,
                            "programName"   => null,
                            "barcode"       => Arr::get($detail, 'product.getProductCombo.barcode'),

                        ];
                        // $subTotal             += ($quantity ?? 0) * ($priceOriginCombo ?? 0);
                        array_push($order_detail, $detail_order);
                    }
                    if ($isOdd) {
                        $disCountProduct      = $detail->discount * $detail->qty;
                        $totalDiscountProduct += $disCountProduct;
                        $productSpecificationParent     = Arr::get($detail, 'product.combo_specification_from', 0);
                        $specificationOdd = $productSpecificationParent != 0 ? $productSpecificationParent : $spValue;
                        $detail_order         = [
                            "productCode"   => $productCodePush,
                            "productName"   => $productNamePush,
                            "quantity"      => (int)$detail->qty * $spValue,
                            "convfact"      => (int)$specificationOdd,
                            "price"         => (float)$detail->price,
                            "discount"      => (float)$detail->discount,
                            "totalDiscount" => (float)($detail->discount * $detail->qty),
                            "amount"        => (float)round($detail->price * $detail->qty ?? 0),
                            "amountNotVat"  => (float)(round(($detail->price * $detail->qty) - (($detail->price * $detail->qty) * ($vat / 100) ?? 0))),
                            "priceNotVat"   => (float)($detail->price - (($detail->price) * $vat / 100)),
                            "vat"           => $vat,
                            "total"         => (float)round($totalOrderTmp ?? 0),
                            "isFreeItem"    => 0,
                            "programName"   => null,
                            "barcode"       => Arr::get($detail, 'product.barcode'),

                        ];
                        // $subTotal             += ($detail->qty ?? 0) * ($detail->price ?? 0);
                        array_push($order_detail, $detail_order);
                    }

                    if ($isComboMulti) {
                        $productCombo = Arr::get($detail, 'product.product_combo', null);
                        if (!empty($productCombo)) {
                            $totalProductCombo = array_sum(array_column($productCombo, 'price')); // Tổng giá sản phẩm trong combo
                            $priceOrigin = $totalProductCombo; // Giá gốc
                            $priceSale = $detail->price; // Giá bán của combo
                            $discountPrice = $priceOrigin - $priceSale; // Giảm giá
                            $percentDiscount = $discountPrice / $priceOrigin; // Tỉ lệ giảm giá
                            foreach ($productCombo as $key => $value) {
                                $priceOriginCombo   = (float)$value['price']; // Giá của 1 item gốc
                                $discountComboMultiFirst = $key == 0 ? (float)($detail->discount * $detail->qty) : 0.0;
                                $price = (float)round($priceOriginCombo - ($priceOriginCombo * $percentDiscount));
                                $amount = (float)round($detail->qty * $price);
                                $discountCombo      = $discountComboMultiFirst;
                                $totalDiscountProduct += $discountCombo;
                                $totalTmp           = (float)round($detail->qty * ($priceOriginCombo - ($priceOriginCombo * $percentDiscount))) - $discountComboMultiFirst;
                                $discount = round($priceOriginCombo * $detail->qty - $amount);
                                $combo[] = [
                                    'promotionCode'  => null,
                                    'promotionName'  => "Chiết khấu theo " . $value['product_name'],
                                    'promotionPrice' => $discount,
                                    'type'           => 1
                                ];
                                $detail_order         = [
                                    "productCode"   => $value['product_code'],
                                    "productName"   => $value['product_name'] . ("($detail->product_name)"),
                                    "quantity"      => (int)$detail->qty * (int)$value['specification'],
                                    "convfact"      => (int)$value['specification_from'],
                                    "price"         => $priceOriginCombo,
                                    "discount"      => $key == 0 ? (float)$detail->discount : 0.0,
                                    "totalDiscount" => $discountComboMultiFirst,
                                    "amount"        => $amount,
                                    "amountNotVat"  => $amount - $amount * ($vat / 100),
                                    "priceNotVat"   => $priceOriginCombo - $priceOriginCombo * ($vat / 100),
                                    "vat"           => $vat,
                                    "total"         => (float)round($totalTmp ?? 0),
                                    "isFreeItem"    => 0,
                                    "programName"   => null,
                                    "barcode"       => $value['barcode'],
                                ];
                                array_push($order_detail, $detail_order);
                            }
                        }
                    }
                }
                if (!empty(json_decode($order->free_item)) && $order->free_item != '[]') {
                    foreach (json_decode($order->free_item) as $key => $item) {
                        foreach ($item->text as $prod) {
                            if (!empty($prod->qty_gift)) {
                                if (!empty($prod->unit_code) && Str::upper($prod->unit_code) != 'COMBO') {
                                    $checkProdOdd = Product::where('code', $prod->product_code)->select('is_odd', 'combo_specification_from')->first();
                                    if (!empty($checkProdOdd->is_odd)) {
                                        $convfact = $checkProdOdd->combo_specification_from ?? 1;
                                    }
                                    $order_detail[] = [
                                        'productCode'   => $prod->product_code,
                                        'productName'   => $prod->title_gift ?? $prod->product_name,
                                        'quantity'      => ($item->value ?? 1) * ($prod->specification_value ?? 1),
                                        'price'         => 0,
                                        'convfact'      => $convfact,
                                        'discount'      => 0,
                                        'totalDiscount' => 0,
                                        'amount'        => 0,
                                        'amountNotVat'  => 0,
                                        'priceNotVat'   => 0,
                                        'vat'           => 0,
                                        'total'         => 0,
                                        "isFreeItem"    => 1,
                                        "programName"   => $item->title,
                                        "barcode"       => $prod->barcode ?? null,
                                    ];
                                }

                                if (!empty($prod->unit_code) && Str::upper($prod->unit_code) == 'COMBO') {
                                    $productCombo = Product::where('code', $prod->product_code)->value('product_combo');
                                    foreach ($productCombo ?? [] as $product) {
                                        $order_detail[] = [
                                            'productCode'   => $product['product_code'],
                                            'productName'   => ($product['product_name'] ?? $prod->product_name) . (Str::startsWith(Str::upper($prod->product_name),'COMBO') ? "($prod->product_name)" : "(Combo $prod->product_name)"),
                                            'quantity'      => ($item->value ?? 1) * ($product['specification'] ?? 1),
                                            'price'         => 0,
                                            'convfact'      => $product['specification_from'],
                                            'discount'      => 0,
                                            'totalDiscount' => 0,
                                            'amount'        => 0,
                                            'amountNotVat'  => 0,
                                            'priceNotVat'   => 0,
                                            'vat'           => 0,
                                            'total'         => 0,
                                            "isFreeItem"    => 1,
                                            "programName"   => $item->title ?? null,
                                            "barcode"       => $product['barcode'] ?? null,
                                        ];
                                    }
                                }
                            } else {
                                if (!empty($prod->unit_code) && Str::upper($prod->unit_code) != 'COMBO') {
                                    $order_detail[] = [
                                        'productCode'   => $prod->product_code,
                                        'productName'   => $prod->title_gift ?? $prod->product_name,
                                        'quantity'      => ($item->value ?? 1) * ($prod->specification_value ?? 1),
                                        'price'         => 0,
                                        'convfact'      => $prod->specification ?? 1,
                                        'discount'      => 0,
                                        'totalDiscount' => 0,
                                        'amount'        => 0,
                                        'amountNotVat'  => 0,
                                        'priceNotVat'   => 0,
                                        'vat'           => 0,
                                        'total'         => 0,
                                        "isFreeItem"    => 1,
                                        "programName"   => $item->title,
                                        "barcode"       => $prod->barcode ?? null,
                                    ];
                                }
                                if (!empty($prod->unit_code) && Str::upper($prod->unit_code) == 'COMBO') {
                                    $productCombo = Product::where('code', $prod->product_code)->value('product_combo');
                                    foreach ($productCombo ?? [] as $product) {
                                        $order_detail[] = [
                                            'productCode'   => $product['product_code'],
                                            'productName'   => $product['product_name'] ?? $prod->product_name,
                                            'quantity'      => ($item->value ?? 1) * ($product['specification'] ?? 1),
                                            'price'         => 0,
                                            'convfact'      => $product['specification_from'],
                                            'discount'      => 0,
                                            'totalDiscount' => 0,
                                            'amount'        => 0,
                                            'amountNotVat'  => 0,
                                            'priceNotVat'   => 0,
                                            'vat'           => 0,
                                            'total'         => 0,
                                            "isFreeItem"    => 1,
                                            "programName"   => $item->title ?? null,
                                            "barcode"       => $product['barcode'] ?? null,
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }

                $totalAmountNotVat = $order->sub_total_price - (($order->sub_total_price - $order->ship_fee) * ($vat / 100));
                $promption         = $order->promotionTotals->map(function ($item) use ($totalDiscountProduct) {
                    return [
                        'promotionCode'  => $item->promotion_code,
                        'promotionName'  => $item->promotion_name,
                        'promotionPrice' => round($item->value),
                        "type"           => $item->promotion_act_type == "order_sale_off" ? 2 : 1
                    ];
                });

                if (!empty($order->total_info)) {
                    $total_info  = json_decode($order->total_info);
                    $coupon_ship = array_search('coupon_delivery', array_column($total_info, 'code'));

                    if (isset($coupon_ship) && is_integer($coupon_ship) == true) {
                        $promption[] = [
                            'promotionCode'  => $order->coupon_delivery_code,
                            'promotionName'  => $total_info[$coupon_ship]->title ?? null,
                            'promotionPrice' => $total_info[$coupon_ship]->value ?? null,
                            "type"           => 3
                        ];
                    }
                }

                if (!empty($combo)) {
                    foreach ($combo as $disCom) {
                        $promption[] = $disCom;
                    }
                }

                // $detail_free_items = [];
                // foreach ($freeItem as $value) {
                //     if ($value['isFreeItem'] == 0) {
                //         continue;
                //     }
                //     if (!empty($detail_free_items[$value['productCode']])) {
                //         $detail_free_items[$value['productCode']]['quantity'] += $value['quantity'];
                //     } else {
                //         $detail_free_items[$value['productCode']] = $value;
                //     }
                // }
                // if (!empty($detail_free_items)) {
                //     foreach ($detail_free_items as $value) {
                //         array_push($order_detail, $value);
                //     }
                // }

                $param[] = [
                    "orderNumber"     => $order->code,
                    "orderDate"       => date('d-m-Y H:i:s', strtotime($order->created_at)),
                    // "orderDate"       => '29-07-2022 13:59:52',
                    "shortCode"       => $order->customer_code,
                    "customerName"    => $order->customer_name,
                    "phone"           => $order->customer_phone,
                    "shippingName"    => $order->customer_phone,
                    "shippingPhone"   => $order->shipping_address_phone,
                    "address"         => $order->shipping_address,
                    "shopCode"        => $order->distributor->code,
                    "shopName"        => $order->distributor->name,
                    "status"          => $order->status,
                    "sumAmount"       => (int)$order->sub_total_price,
                    "sumDiscount"     => (int)$totalDiscountProduct,
                    "total"           => (int)$order->total_price,
                    "sumAmountNotVat" => (int)$totalAmountNotVat,
                    "totalDetail"     => (int)$totalDetail,
                    "paymentType"     => $order->payment_method == PAYMENT_METHOD_CASH ? 1 : 2,
                    "deliveryType"    => $order->shipping_method_code == SHIPPING_PARTNER_TYPE_DEFAULT ? 1 : 2,
                    "deliveryPartner" => SHIPPING_PARTNER_TYPE_NAME[$order->shipping_method_code],
                    "transportFee"    => $order->ship_fee_start ?? 0,
                    "orderType"       => $order->shipping_method_code == SHIPPING_PARTNER_TYPE_DEFAULT ? 1 : 2,
                    "orderSource"     => "1",
                    "orderNote"       => $order->description ?? null,
                    "customerType"    => "2",
                    "dataStatus"      => $status,
                    'promotion'       => $promption,
                    "details"         => $order_detail,
                ];
            }
            return $param;
        } catch (\Exception $exception) {
        }
    }

    public static function updateStatusDMS(array $order, $status, $statusOrder)
    {
        if (empty(self::checkStatus())) {
            return [];
        }
        $order_update = Order::model()->whereIn('code', $order);
        $orders       = $order_update->get();
        $param        = [];
        foreach ($orders as $order) {
            $param[] = [
                "orderNumber"  => $order->code,
                "status"       => $statusOrder,
                "shopCode"     => $order->distributor->code,
                "customerName" => $order->customer_name,
                "customerCode" => $order->customer_code,
                "deliveryNote" => !empty($order->shipping_note) ? $order->shipping_note : "",
                "deliveryDate" => !empty($order->delivery_time) ? date('d-m-Y H:i:s', strtotime($order->delivery_time)) : "",
                "deliveryType" => $order->shipping_method_code == SHIPPING_PARTNER_TYPE_DEFAULT ? "1" : "2",
            ];
        }
        return $param;
    }

    public static function callApiDms(array $param, $type)
    {
        if (empty(self::checkStatus())) {
            return [];
        }

        if (!empty(self::checkDistributor())) {
            if ($type == 'CREATE-ORDER') {
                $flag = false;
                foreach ($param as $item) {
                    if (!empty($item['shopCode']) && !in_array($item['shopCode'], ['phcm0005', 'nppdaotao'])) {
                        $flag = true;
                        break;
                    }
                }
                if ($flag) {
                    return [];
                }
            }

            if ($type == 'UPDATE-ORDER') {
                $flag = false;
                foreach ($param as $item) {
                    if (!empty($item['shopCode']) && !in_array($item['shopCode'], ['phcm0005', 'nppdaotao'])) {
                        $flag = true;
                        break;
                    }
                }
                if ($flag) {
                    return [];
                }
            }
        }
        try {
            $client = new Client();
            Log::logSyncDMS("REQ $type", null, $param ?? [], $type, 1);
            if ($type == "CREATE-ORDER") {
                $response = $client->post(env("API_SYNC_DMS") . "so-syn", [
                    'headers' => ['Content-Type' => 'application/json'],
                    'body'    => json_encode($param),
                    //                    'timeout'         => 30, // Response timeout
                    //                    'connect_timeout' => 30, // Connection timeout
                ]);
                $response = $response->getBody()->getContents() ?? null;
                $response = !empty($response) ? json_decode($response, true) : [];
                Log::logSyncDMS("RES $type", null, $param ?? [], $type, 1, $response);
                return $response;
            }
            if ($type == "UPDATE-ORDER") {
                $response = $client->post(env("API_SYNC_DMS") . "order-change-status", [
                    'headers' => ['Content-Type' => 'application/json'],
                    'body'    => json_encode($param),
                    //                    'timeout'         => 30, // Response timeout
                    //                    'connect_timeout' => 30, // Connection timeout
                ]);
                $response = $response->getBody()->getContents() ?? null;
                $response = !empty($response) ? json_decode($response, true) : [];
                Log::logSyncDMS("RES $type", null, $param ?? [], $type, 1, $response);
                return $response;
            }
        } catch (RequestException  $e) {
            Log::logSyncDMS('GuzzleHttp_Error', $e->getMessage(), $param ?? [], $type, 1, null);
        }
    }

    private static function checkStatus()
    {
        $check_active = CheckActiveAccesstrade::where('code', self::ACTIVE)->where('is_active', 1)->first();
        if (empty($check_active)) {
            return [];
        }
        return 1;
    }

    private static function checkDistributor()
    {
        $check_active = CheckActiveAccesstrade::where('code', self::ACTIVE_DISTRIBUTOR)->where('is_active', 1)->first();
        if (empty($check_active)) {
            return [];
        }
        return 1;
    }
}
