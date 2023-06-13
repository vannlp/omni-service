<?php

namespace App\V1\Library;


use App\Batch;
use App\Cart;
use App\Distributor;
use App\Order;
use App\OrderDetail;
use App\OrderHistory;
use App\OrderStatus;
use App\ShippingOrder;
use App\ShippingOrderDetail;
use App\Store;
use App\Supports\Message;
use App\TM;
use App\Unit;
use App\Warehouse;
use App\WarehouseDetail;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Product;
use Predis\ClientException;

class GRAB
{
    const STATUS
        = [
            "FAILED"      => "Đơn đặt hàng chưa được phân bổ",
            "CANCELED"    => "Đơn đặt hàng đã hủy",
            "RETURNED"    => "Đơn đặt hàng được trả về",
            "IN_RETURN"   => "Đơn đặt hàng trả về",
            "ALLOCATING"  => "Đang chờ tài xế nhận đơn đặt hàng",
            "PICKING_UP"  => "Người lái xe đang xử lý đơn đặt hàng",
            "IN_DELIVERY" => "Vật phẩm giao được tài xế đến nhận",
            "COMPLETED"   => "Đơn hàng đã hoàn thành"
        ];
    const ERRORS = [
        "Code: 105. Reason: Distance SLA exceeded" => "Đã vượt quá khoảng cách Grab giao"
    ];
    public static $weight_converts = ['GRAM' => 1, 'KG' => 1000];

    public function __construct()
    {

    }

    public static function getToken($isCool)
    {
        try {
            if ($isCool == 0) {
                $param = [
                    'client_id'     => env("GRAB_CLIENT_ID"),
                    'client_secret' => env("GRAB_CLIENT_SECRET"),
                    "grant_type"    => "client_credentials",
                    "scope"         => "grab_express.partner_deliveries"
                ];
            }
            if ($isCool == 1) {
                $param = [
                    'client_id'     => env("GRAB_CLIENT_ID_COOL"),
                    'client_secret' => env("GRAB_CLIENT_SECRET_COOL"),
                    "grant_type"    => "client_credentials",
                    "scope"         => "grab_express.partner_deliveries"
                ];
            }

            $client   = new Client();
            $response = $client->post(!empty(env("GRAB_CLIENT_PRO")) ? "https://api.grab.com/grabid/v1/oauth2/token" : "https://api.stg-myteksi.com/grabid/v1/oauth2/token", [
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => json_encode($param),
            ]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];
        } catch (\Exception $exception) {
            return ['status' => 'error', 'success' => false, 'message' => $exception->getMessage()];
        }
        return $response['access_token'];
    } 

    public static function getShipFee(Request $request, $store, $service = null)
    {
        $input  = $request->all();
        $weight = 0;
        $price  = 0;
        try {
            if (!empty($input['cart_id'])) {
                $packages = [];
                $cart     = Cart::with('details')->where('id', $input['cart_id'])->first();
                if (empty($cart)) {
                    throw new \Exception(Message::get("V003", $input['cart_id']));
                }
//                if(!empty($input['type'])){
//                    (int)$price = $cart->total_info[array_search('total',array_column($cart->total_info, 'code'))]['value'];
//                   if($price< 5000000){
//                       $price = $cart->ship_fee+5000;
//                   }
//                   if($price > 5000000){
//                       $price = $cart->ship_fee+15000;
//                   }
//                   return $price;
//                }
                $weight_free_item = 0;
                $weight_gift_item = 0;
                if ($cart->free_item && $cart->free_item != "[]") {
                    foreach ($cart->free_item as $item) {
                        foreach ($item['text'] as $value) {
                            $packages[]       = [
                                'name'        => $value['title_gift'] ?? $value['product_name'], //. "- Sản phẩm quà tặng",
                                "description" => $value['title_gift'] ?? $value['product_name'], //. "- Sản phẩm quà tặng",
                                'quantity'    => !empty($value['qty_gift']) ? (int)$value['qty_gift'] : 1,
                                'price'       => 0,
                                'dimensions'  => [
                                    'height' => 0,
                                    'width'  => 0,
                                    'depth'  => 0,
                                    'weight' => round($value['weight'] * self::$weight_converts[($value['weight_class'])]) ?? 1
                                ]
                            ];
                            $weight_free_item += $value['weight'] ?? 1 * ($value['qty_gift'] ?? 1) * self::$weight_converts[($value['weight_class'])];
                        }
                    }
                }
                $weight1 = $cart->details->map(function ($detail) {
                    return [
                        'id'     => $detail->product_id,
                        'qty'    => $detail['quantity'],
                        'weigth' => $detail['quantity'] * $detail->product->weight * self::$weight_converts[($detail->product->weight_class)],
                    ];
                });
                $isCool  = 0;
                foreach ($weight1 as $key) {
                    $product = Product::model()->where('id', $key['id'])->first();
                    if ($product->is_cool == 1 && $isCool != 1) {
                        $isCool = 1;
                    }
                    $packages[]       = [
                        'name'        => $product['name'],
                        'description' => $product['name'],
                        'quantity'    => (int)$key['qty'],
                        'price'       => $product['price'],
                        "dimensions"  => [
                            'height' => 0,
                            'width'  => 0,
                            'depth'  => 0,
                            'weight' => round($product['weight'] * self::$weight_converts[($product['weight_class'])])
                        ]
                    ];
                    $weight_free_item += round($product['weight'] * $key['qty'] * self::$weight_converts[($product['weight_class'])]);
                    // if ($product['gift_item'] && $product['gift_item'] != "[]") {
                    //     foreach (json_decode($product['gift_item']) as $value) {
                    //         if (!empty($value->weight)) {
                    //             $packages[]       = [
                    //                 'name'        => $value->product_name . "- Sản phẩm quà tặng",
                    //                 'description' => $value->product_name . "- Sản phẩm quà tặng",
                    //                 'quantity'    => 1,
                    //                 'price'       => 0,
                    //                 "dimensions"  => [
                    //                     'height' => 0,
                    //                     'width'  => 0,
                    //                     'depth'  => 0,
                    //                     'weight' => (int)$value->weight
                    //                 ]
                    //             ];
                    //             $weight_free_item += $value->weight * self::$weight_converts[$value->weight_class];
                    //         }
                    //     }
                    // }
                }
                $address_origin = $cart->address;
                $cityOrigin     = $cart->getCity->grab_code;
                if (!empty($cart->distributor_code)) {
                    if(!empty($cart->getUserDistributor->type_delivery_hub) && $cart->getUserDistributor->type_delivery_hub != SHIPPING_PARTNER_TYPE_GRAB){
                        return [];
                    }
                    $distributor         = Distributor::model()->where('code', $cart->distributor_code)->first();
                    if($cart->getUserDistributor->is_transport != 1){
                        return [];
                    }
                    $cityDestinationCode = $distributor->users->profile->city->grab_code;
                    $address             = $distributor->users->profile->address . ", " . $distributor->users->profile->ward->full_name . ", " . $distributor->users->profile->district->full_name . ", " . $distributor->users->profile->city->full_name;
//                    $lat_long_destination = self::getLatLong($address);
                }
                if (empty($cart->distributor_code)) {
                    $store               = Store::model()->where('id', $store)->first();
                    $cityDestinationCode = $store->city->grab_code;
                    $address             = $store->address . ", " . $store->ward->full_name . ", " . $store->district->full_name . ", " . $store->city->full_name;
                }
            }
//            if (!empty($input['order_id'])) {
//                $order = Order::with("details.product")->where('id', $input['order_id'])->first();
//                if (empty($order)) {
//                    throw new \Exception(Message::get("V003", $input['order_id']));
//                }
//                if ($order->free_item && $order->free_item != "[]") {
//                    foreach ($order->free_item as $item) {
//                        foreach ($item['text'] as $value) {
//                            $packages[]       = [
//                                'name'        => $value['product_name'] . "- Sản phẩm quà tặng",
//                                "description" => $value['product_name'] . "- Sản phẩm quà tặng",
//                                'quantity'    => !empty($value['qty_gift']) ? (int)$value['qty_gift'] : 1,
//                                'price'       => 100000,
//                                'dimensions'  => [
//                                    'height' => 0,
//                                    'width'  => 0,
//                                    'depth'  => 0,
//                                    'weight' => round($value['weight'] * self::$weight_converts[($value['weight_class'])])
//                                ]
//                            ];
//                            $weight_free_item += $value['weight'] * ($value['qty_gift'] ?? 1) * self::$weight_converts[($value['weight_class'])];
//                        }
//                    }
//                }
//                foreach ($order->details as $input_detail) {
//                    $product    = Product::model()->where('id', $input_detail['product_id'])->first();
//                    $packages[] = [
//                        'name'        => $product['name'],
//                        'description' => $product['name'],
//                        'quantity'    => (int)$input_detail['qty'],
//                        'price'       => $product['price'],
//                        "dimensions"  => [
//                            'height' => 0,
//                            'width'  => 0,
//                            'depth'  => 0,
//                            'weight' => round($product['weight'] * self::$weight_converts[($product['weight_class'])])
//                        ]
//                    ];
//                    if ($product['gift_item'] && $product['gift_item'] != "[]") {
//                        foreach (json_decode($product['gift_item']) as $value) {
//                            if (!empty($value->weight)) {
//                                $packages[]       = [
//                                    'name'        => $value['product_name'] . "- Sản phẩm quà tặng",
//                                    'description' => $value['product_name'] . "- Sản phẩm quà tặng",
//                                    'quantity'    => 1,
//                                    'price'       => 100000,
//                                    "dimensions"  => [
//                                        'height' => 0,
//                                        'width'  => 0,
//                                        'depth'  => 0,
//                                        'weight' => 100
//                                    ]
//                                ];
//                                $weight_gift_item += $value['weight'] * self::$weight_converts[$value['weight_class']];
//                            }
//                        }
//                    }
//                    $weight += $input_detail['qty'] * $input_detail->product['weight'] * self::$weight_converts[$input_detail->product['weight_class']];
//                }
//                $address_origin  = $order->shipping_address;
//                $lat_long_origin = self::getLatLong($address_origin);
//                if (!empty($order->distributor_code)) {
//                    $distributor          = Distributor::model()->where('code', $order->distributor_code)->first();
//                    $address              = $distributor->users->profile->address . ", " . $distributor->users->profile->ward->full_name . ", " . $distributor->users->profile->district->full_name . ", " . $distributor->users->profile->city->full_name;
//                    $lat_long_destination = self::getLatLong($address);
//                }
//                if (empty($order->distributor_code)) {
//                    $store                = Store::model()->where('id', $store)->first();
//                    $address              = $store->address . ", " . $store->ward->full_name . ", " . $store->district->full_name . ", " . $store->city->full_name;
//                    $lat_long_destination = self::getLatLong($address . ", " . "Việt Nam");
//                }
//                if (!empty($input['distributor_code'])) {
//                    $distributor          = Distributor::model()->where('code', $input['distributor_code'])->first();
//                    $address              = $distributor->users->profile->address . ", " . $distributor->users->profile->ward->full_name . ", " . $distributor->users->profile->district->full_name . ", " . $distributor->users->profile->city->full_name;
//                    $lat_long_destination = self::getLatLong($address);
//                }
//            }
            if ($weight_free_item >= 30000 || empty($cityOrigin) || empty($cityDestinationCode)) {
                return [];
            }
            if ($cityOrigin != $cityDestinationCode) {
                return [];
            }
            if ($isCool == 1 && !in_array($cityOrigin, ["HNI", "SGN"])) {
                return [];
            }
            $param    = [
                "serviceType" => $service,
                "packages"    => $packages,
                "origin"      => [
                    "address"     => $address,
                    "coordinates" => [
                        "latitude"  => 0,
                        "longitude" => 0
                    ],
                    "citycode"    => $cityOrigin
                ],
                "destination" => [
                    "address"     => $address_origin,
                    "coordinates" => [
                        "latitude"  => 0,
                        "longitude" => 0
                    ],
                    "citycode"    => $cityDestinationCode
                ]
            ];
            $token    = self::getToken($isCool);
            $client   = new Client();
            try {
                $response = $client->post(env("GRAB_END_POINT") . "/deliveries/quotes", [
                    'headers' => ['Content-Type' => 'application/json', 'Authorization' => "Bearer " . $token],
                    'body'    => json_encode($param),
                ]);
            }catch (\GuzzleHttp\Exception\ClientException $exception){
                $errors = (json_decode($exception->getResponse()->getBody()->getContents()));
                return [
                    'errors' => self::ERRORS[$errors->arg] ?? $errors->arg
                ];
            }
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];
            return [
                'price' => $response['quotes'][0]['amount'],
                'time'  => $response['quotes'][0]['estimatedTimeline']['dropoff'],
                'distance' => $response['quotes'][0]['distance'] ?? 0,
                'param' => $param,
                'response' => $response
            ];

        } catch (\RequestException  $exception) {
            return $exception->getRequest() . "\n";
        }
    }

    public static final function sendOrder(Order $order,$check = null)
    {
        $price    = 0;
        $weight   = 0;
        $group_id = 0;
        $packages = [];
        try {
            if (empty($order) || empty($order->id)) {
                throw new \Exception(Message::get("V001", Message::get("orders")));
            }
            $order           = Order::model()->with(['details.product.warehouse', 'store', 'customer.profile'])->where('id',
                $order->id)->first();
            $sender_customer = [
                "firstName"  => "CUSTOMER",
                "lastName"   => $order->customer_name,
                "email"      => !empty($order->customer_email) ? trim($order->customer_email) : trim($order->customer->email),
                "phone"      => $order->shipping_address_phone ?? $order->customer_phone,
                "smsEnabled" => true
            ];
            $address_origin  = $order->shipping_address;
            $cityCode        = $order->getCity->grab_code;
//            $lat_long_origin = self::getLatLong($address_origin);
            if (!empty($order->free_item) && $order->free_item != "[]") {
                foreach (json_decode($order->free_item) as $item) {
                    foreach ($item->text as $value) {
                        $packages[] = [
                            'name'        => $value->title_gift ?? $value->product_name, //. "- Sản phẩm quà tặng",
                            "description" => $value->title_gift ?? $value->product_name, //. "- Sản phẩm quà tặng",
                            'quantity'    => !empty($value->qty_gift) ? (int)$value->qty_gift : 1,
                            'price'       => 1,
                            'dimensions'  => [
                                'height' => 0,
                                'width'  => 0,
                                'depth'  => 0,
                                'weight' => $value->weight * self::$weight_converts[($value->weight_class)]
                            ]
                        ];
//                        $weight_free_item += $value['weight'] * ($value['qty_gift'] ?? 1) * self::$weight_converts[($value['weight_class'])];
                    }
                }
            }
            if (!empty($order->distributor_code)) {
                $distributor          = Distributor::model()->where('code', $order->distributor_code)->first();
                $address              = $distributor->users->profile->address . ", " . $distributor->users->profile->ward->full_name . ", " . $distributor->users->profile->district->full_name . ", " . $distributor->users->profile->city->full_name;
//                $lat_long_destination = self::getLatLong($address);
                $sender               = [
                    "firstName"  => "NPP",
                    "lastName"   => $distributor->name,
                    "email"      =>trim($distributor->users->email),
                    "phone"      =>$distributor->users->phone,
                    "smsEnabled" => true,
                    "instruction" => "Tài Xế vui lòng gọi cho khách hàng trước khi giao"
                ];
            }
            if (empty($order->distributor_code)) {
                $store                = Store::find(58);
                $address              = $store->address . ", " . $store->ward->full_name . ", " . $store->district->full_name . ", " . $store->city->full_name;
//                $lat_long_destination = self::getLatLong($address);
                $sender               = [
                    "firstName"  => "STORE",
                    "lastName"   => $store->name,
                    "email"      => trim($store->email),
                    "phone"      => $store->contact_phone,
                    "smsEnabled" => true
                ];
            }
            $cod_amount = (int)$order->total_price;
//            if (!empty($order->ship_fee) && !empty($order->total_price) && $order->ship_fee != 0) {
//                $cod_amount = $order->total_price - (int)$order->ship_fee;
//            }
            // Get Ward
            $weight_converts = ['KG' => 1000, 'GRAM' => 1];
            $order_details   = [];
            $warehouse       = [];
            foreach ($order->details as $key => $detail) {
                try {


                $order_details[$detail->id] = $detail->toArray();
                array_push($warehouse, $detail->product);
                $warehouse[$key]['order_detail_id'] = $detail->id;
                $warehouse[$key]['warehouse_id']    = $detail->product->warehouse->warehouse_id;
                $warehouse[$key]['batch_id']        = $detail->product->warehouse->batch_id;
                }catch (\Exception $exception){

                }
            }
            $products            = [];
            $shippingDetailParam = [];

            $allProduct   = Product::model()->select(['id', 'code', 'name'])
                ->whereIn('id', array_column($order_details, 'product_id'))
                ->get()->pluck(null, 'id')->toArray();
            $allUnit      = Unit::model()->select(['id', 'code', 'name'])
                ->whereIn('id', array_column($warehouse, 'unit_id'))
                ->get()->pluck(null, 'id')->toArray();
            $allWarehouse = Warehouse::model()->select(['id', 'code', 'name'])
                ->whereIn('id', array_column($warehouse, 'warehouse_id'))
                ->get()->pluck(null, 'id')->toArray();
            $allBatch     = Batch::model()->select(['id', 'code', 'name'])
                ->whereIn('id', array_column($warehouse, 'batch_id'))
                ->get()->pluck(null, 'id')->toArray();
            $now          = date("Y-m-d H:i:s");
            $isCool = 0;
            foreach ($warehouse as $input_detail) {
                if (empty($order_details[$input_detail['order_detail_id']])) {
                    throw new \Exception(Message::get("V003",
                        Message::get("order_details") . " #{$input_detail['order_detail_id']}"));
                }

                $order_detail = $order_details[$input_detail['order_detail_id']];
                if (empty($order_detail['product'])) {
                    throw new \Exception(Message::get("V003",
                        Message::get("products") . " #{$order_detail['product_id']}"));
                }

                $item = $order_detail['product'];
//                if ((int)$order_detail['qty'] + (int)$order_detail['shipped_qty'] > (int)$order_detail['qty']) {
//                    throw new \Exception(Message::get("V013", 'ship_qty', 'shipped_qty'));
//                }

                $inventory = WarehouseDetail::model()->select('quantity')->where([
                    'product_id'   => $order_detail['product_id'],
                    'warehouse_id' => $input_detail['warehouse_id'],
                    'batch_id'     => $input_detail['batch_id'],
                    'unit_id'      => $input_detail['unit_id']
                ])->first();

                if (empty($inventory) || $inventory->quantity < $order_detail['qty']) {
                    throw new \Exception(Message::get("V051", $item['code']));
                }
                // WarehouseDetail::model()->where([
                //     'product_id'   => $order_detail['product_id'],
                //     'warehouse_id' => $input_detail['warehouse_id'],
                //     'batch_id'     => $input_detail['batch_id'],
                //     'unit_id'      => $input_detail['unit_id'],
                //     'company_id'   => TM::getCurrentCompanyId(),
                // ])->update(['quantity'=>$inventory->quantity - $order_detail['qty']]);
                if (empty($item['weight_class']) || !in_array($item['weight_class'], ['GRAM', 'KG'])) {
                    throw new \Exception(Message::get("V004",
                        "weight_class (" . Message::get("products") . " #{$order_detail['product_id']})", 'GRAM|KG'));
                }
                if($item['is_cool'] == 1 && $isCool !=1){
                    $isCool = 1;
                }
                $packages[] = [
                    'name'        => $item['name'],
                    'description' => $item['name'],
                    'quantity'    => (int)$order_detail['qty'],
                    'price'       => $order_detail['price'],
                    "dimensions"  => [
                        'height' => 0,
                        'width'  => 0,
                        'depth'  => 0,
                        'weight' => round($item['weight'] * self::$weight_converts[($item['weight_class'])])
                    ]
                ];
//                 if ($item['gift_item'] && $item['gift_item'] != "[]") {

//                     foreach (json_decode($item['gift_item']) as $value) {
//                         if (!empty($value->weight)) {
//                             $packages[] = [
//                                 'name'        => $value->product_name . "- Sản phẩm quà tặng",
//                                 'description' => $value->product_name . "- Sản phẩm quà tặng",
//                                 'quantity'    => 1,
//                                 'price'       => 1,
//                                 "dimensions"  => [
//                                     'height' => 0,
//                                     'width'  => 0,
//                                     'depth'  => 0,
//                                     'weight' => 100
//                                 ]
//                             ];
// //                            $weight_gift_item += $value['weight'] * self::$weight_converts[$value['weight_class']];
//                         }
//                     }
//                 }

                $price                 += $item['price'] ?? $order_detail['price'];
                $weight                += $order_detail['qty'] * $item['weight'] * $weight_converts[$item['weight_class']];
                $shippingDetailParam[] = [
                    'order_detail_id' => $order_detail['id'],
                    'product_id'      => $order_detail['product_id'],
                    'product_code'    => $allProduct[$order_detail['product_id']]['code'] ?? null,
                    'product_name'    => $allProduct[$order_detail['product_id']]['name'] ?? null,
                    'unit_id'         => $input_detail['unit_id'],
                    'unit_code'       => $allUnit[$input_detail['unit_id']]['code'] ?? null,
                    'unit_name'       => $allUnit[$input_detail['unit_id']]['name'] ?? null,
                    'warehouse_id'    => $input_detail['warehouse_id'],
                    'warehouse_code'  => $allWarehouse[$input_detail['warehouse_id']]['code'] ?? null,
                    'warehouse_name'  => $allWarehouse[$input_detail['warehouse_id']]['code'] ?? null,
                    'batch_id'        => $input_detail['batch_id'],
                    'batch_code'      => $allBatch[$input_detail['batch_id']]['code'] ?? null,
                    'batch_name'      => $allBatch[$input_detail['batch_id']]['name'] ?? null,
                    'qty'             => $order_detail['qty'],
                    'ship_qty'        => $order_detail['qty'],
                    'shipped_qty'     => ($order_detail['qty'] + $order_detail['shipped_qty']),
                    'price'           => $order_detail['price'],
                    'total_price'     => $order_detail['total'],
                    'discount'        => $order_detail['discount'],
                    'created_at'      => $now,
                    'created_by'      => 1,
                ];
            }
            $lastShip = ShippingOrder::model()->where('ship_code', 'like',
                "{$order->code}-" . SHIPPING_PARTNER_TYPE_GRAB . "-%")->orderBy('id', 'desc')->first();

            $codeIndex = 0;
            if (!empty($lastShip)) {
                $codeIndex = explode("-" . SHIPPING_PARTNER_TYPE_GRAB . "-", $lastShip->ship_code);
                $codeIndex = (int)($codeIndex[1] ?? 0);
            }
            $shipCode = $order->code . "-" . SHIPPING_PARTNER_TYPE_GRAB . "-" . (str_pad(++$codeIndex, 2, '0',
                    STR_PAD_LEFT));
            $date     = date("d/m/Y H:i:s");
            if ($order->payment_method != 'CASH') {
                $order_payment = 4;
                if ($order->is_freeship == 1) {
                    $order_payment = 1;
                }
            }
            if ($order->payment_method == 'CASH') {
                $order_payment = 2;
                if ($order->is_freeship == 1) {
                    $order_payment = 3;
                }
            }
            $param    = [
                "merchantOrderID" => $shipCode,
                "serviceType"     => "INSTANT",
                "paymentMethod"   => "CASHLESS",
                "cashOnDelivery"  => [
                    "amount" => $order_payment == 2 || $order_payment == 3 ? $cod_amount : 0
                ],
                "packages"        => $packages,
                "origin"          => [
                    "address"     => $address,
                    "coordinates" => [
                        "latitude"  => 0,
                        "longitude" => 0
                    ],
                    "citycode"      => $cityCode
                ],
                "destination"     => [
                    "address"     => $address_origin,
                    "coordinates" => [
                        "latitude"  => 0,
                        "longitude" => 0
                    ],
                    "citycode"      => $cityCode
                ],
                "recipient"       => $sender_customer,
                "sender"          => $sender,

            ];
            // die;
            $token = self::getToken($isCool);
            $client   = new Client();
            $response = $client->post(env("GRAB_END_POINT") . "/deliveries", [
                'headers' => ['Content-Type' => 'application/json', 'Authorization' => "Bearer " . $token],
                'body'    => json_encode($param),
            ]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];
            if ($response['status'] == 'ALLOCATING') {
                // Update Order
                $order->shipping_info_code = $response['deliveryID'] ?? null;
                $order->shipping_info_json = !empty($response) ? json_encode($response) : null;
                $order->status             = OrderStatus::SHIPPING;
                $order->status_text        = "Sẵn sàng giao hàng";
                $order->save();
                if($check != 1){
                    OrderHistory::insert([
                        'order_id'   => $order->id,
                        'status'     => OrderStatus::SHIPPING,
                        'created_at' => date("Y-m-d H:i:s", time()),
                        'created_by' => 1,
                    ]);
                    VNP::updateOrderStatusHistory($order);
                }
                // Create Shipping Order
                $shippingOrder                = new ShippingOrder();
                $shippingOrder->type          = "GRAB";
                $shippingOrder->code          = $order->code;
                $shippingOrder->name          = $order->code;
                $shippingOrder->param_push_shipping = json_encode($param);
                $shippingOrder->count_push_shipping = 1;
                $shippingOrder->code_type_ghn = $response['deliveryID'] ?? null;
                // $shippingOrder->partner_id = $response['partner_id'];
                $shippingOrder->status      = 'SHIPPING';
                $shippingOrder->status_text = 'Sẵn sàng giao hàng';
                $shippingOrder->description = $order->shipping_note;
                $shippingOrder->shipping_order_cool = $isCool;
                // $shippingOrder->ship_fee = $response['fee'];
                $shippingOrder->estimated_pick_time    = date('Y-m-d H:i:s', strtotime($response['quote']['estimatedTimeline']['pickup']));
                $shippingOrder->estimated_deliver_time = date('Y-m-d H:i:s', strtotime($response['quote']['estimatedTimeline']['dropoff']));
                $shippingOrder->order_id               = $order->id;
                $shippingOrder->ship_code              = $shipCode;
                $shippingOrder->count_print            = 0;
                $shippingOrder->company_id             = 34;
                $shippingOrder->result_json            = json_encode($response) ?? null;
                $shippingOrder->created_at             = date("Y-m-d H:i:s");
                $shippingOrder->created_by             = 1;
                $shippingOrder->save();

                // Update Order Detail
                foreach ($shippingDetailParam as $key => $item) {
                    OrderDetail::model()->where('id', $item['order_detail_id'])
                        ->update(['shipped_qty' => $item['shipped_qty']]);
                    $shippingDetailParam[$key]['shipping_order_id'] = $shippingOrder->id;
                    unset($shippingDetailParam[$key]['order_detail_id']);
                }

                // Create Shipping Order Detail
                ShippingOrderDetail::insert($shippingDetailParam);
            }
        } catch (\Exception $exception) {
            throw new \Exception("Không tạo được lệnh giao hàng đơn hàng #" .$exception->getMessage());
        }
        return ['status' => 'success', 'success' => true, 'shipping_orders' => $shippingOrder, 'warehouse' => $warehouse];
    }

    public static function cancelOrder($shipping_code,$isCool)
    {
        try {
            $token = self::getToken($isCool);
            $client   = new Client();
            $response = $client->delete(env("GRAB_END_POINT") . "/deliveries/$shipping_code", [
                'headers' => ['Content-Type' => 'application/json', 'Authorization' => "Bearer " . $token],
            ]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];

        } catch (\RequestException  $exception) {
            return $exception->getRequest() . "\n";
        }
        return ['status' => 'success', 'success' => true];
    }
    public static function getDetailGrab($shippingCode,$client = null){
        $shippingOrder = ShippingOrder::model()->where('code',$shippingCode)->where('type',SHIPPING_PARTNER_TYPE_GRAB);
        if($client == 1){
            $shippingOrder = $shippingOrder->whereHas('order',function ($q){
                $q->where('customer_id',TM::getCurrentUserId());
            });
        }
        $shippingOrder = $shippingOrder->first();
            if(empty($shippingOrder)){
                return [];
            }
            $token = self::getToken($shippingOrder->shipping_order_cool);
            $client   = new Client();
            $response = $client->get(env("GRAB_END_POINT") . "/deliveries/$shippingOrder->code_type_ghn", [
                'headers' => ['Content-Type' => 'application/json', 'Authorization' => "Bearer " . $token],
            ]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];
            if(!empty($response)){
                $response['status_text'] = GRAB::STATUS[$response['status']];
                $response['shipping_method_name'] = SHIPPING_PARTNER_TYPE_GRAB;
            }
            return $response;

    }
    public static function getLatLong($address)
    {
        return [
            'lat' => 10.771423,
            'lng' => 106.698471
        ];
//        $client = new Client();
//        $response = $client->post("api.nutifoodshop.com/v0/google-map/geocoding?address=$address", [
//            'headers' => ['Content-Type' => 'application/json'],
//        ]);
//        $response = $response->getBody()->getContents() ?? null;
//        $response = !empty($response) ? json_decode($response, true) : [];
//        return $response['results'][0]['geometry']['location'] ?? null;
    }
}
