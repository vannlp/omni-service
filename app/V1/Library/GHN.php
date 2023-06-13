<?php

namespace App\V1\Library;


use App\Batch;
use App\Cart;
use App\Distributor;
use App\District;
use App\Order;
use App\OrderDetail;
use App\OrderStatus;
use App\Product;
use App\ShippingOrder;
use App\ShippingOrderDetail;
use App\Store;
use App\Supports\Message;
use App\TM;
use App\Unit;
use App\Ward;
use App\Warehouse;
use App\WarehouseDetail;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use function JmesPath\search;

class GHN
{
    const STATUS
        = [
            'ready_to_pick'            => "Đang chờ nhân viên lấy hàng",
            'picking'                  => "Nhân viên đang lấy hàng",
            'cancel'                   => "Hủy đơn hàng",
            'money_collect_picking'    => "Đang thu tiền người gửi",
            'picked'                   => "Nhân viên đã lấy hàng",
            'storing'                  => "Hàng đang nằm ở kho",
            'transporting'             => 'Đang luân chuyển hàng',
            'sorting'                  => 'Đang phân loại hàng hóa',
            'delivering'               => 'Nhân viên đang giao cho người nhận',
            'money_collect_delivering' => 'Nhân viên đang thu tiền người nhận',
            'delivered'                => 'Nhân viên đã giao hàng thành công',
            'delivery_fail'            => 'Nhân viên giao hàng thất bại',
            'waiting_to_return'        => 'Đang đợi trả hàng về cho người gửi',
            'return'                   => 'Trả hàng',
            'return_transporting'      => 'Đang luân chuyển hàng trả',
            'return_sorting'           => 'Đang phân loại hàng trả',
            'returning'                => 'Nhân viên đang đi trả hàng',
            'return_fail'              => 'Nhân viên trả hàng thất bại',
            'returned'                 => 'Nhân viên trả hàng thành công',
            'exception'                => 'Đơn hàng ngoại lệ không nằm trong quy trình',
            'damage'                   => 'Hàng bị hư hỏng',
            'lost'                     => 'Hàng bị mất',
        ];

    public function __construct()
    {

    }

    static $shop_store_id = 80881;

    public static final function sendOrderGHN(Order $order)
    {
        try {
            if (empty($order) || empty($order->id)) {
                throw new \Exception(Message::get("V001", Message::get("orders")));
            }
            $order = Order::model()->with(['details.product.warehouse', 'store', 'customer.profile'])->where('id',
                $order->id)->first();
            if (empty($order)) {
                throw new \Exception(Message::get("V003", Message::get("orders")));
            }

            $weight_converts = ['GRAM' => 1, 'KG' => 1000];
            $order_details   = [];
            $warehouse = [];
            foreach ($order->details as $key => $detail) {
                $order_details[$detail->id] = $detail->toArray();
                array_push($warehouse, $detail->product);
                $warehouse[$key]['order_detail_id'] = $detail->id;
                $warehouse[$key]['warehouse_id'] = $detail->product->warehouse->warehouse_id;
                $warehouse[$key]['batch_id'] = $detail->product->warehouse->batch_id;
            }
            $cod_amont = $order->total_price;
            if (!empty($order->ship_fee) && !empty($order->total_price) && $order->ship_fee != 0) {
                $cod_amont = $order->total_price - (int)$order->ship_fee;
            }
            $products            = [];
            $shippingDetailParam = [];
            $totalWeigth         = 0;

            $allProduct   = Product::model()->select(['id', 'code', 'name'])
                ->whereIn('id', array_column($order_details, 'product_id'))
                ->get()->pluck(null, 'id')->toArray();
            $allUnit = Unit::model()->select(['id', 'code', 'name'])
                ->whereIn('id', array_column($warehouse, 'unit_id'))
                ->get()->pluck(null, 'id')->toArray();
            $allWarehouse = Warehouse::model()->select(['id', 'code', 'name'])
                ->whereIn('id', array_column($warehouse, 'warehouse_id'))
                ->get()->pluck(null, 'id')->toArray();
            $allBatch = Batch::model()->select(['id', 'code', 'name'])
                ->whereIn('id', array_column($warehouse, 'batch_id'))
                ->get()->pluck(null, 'id')->toArray();
            $now          = date("Y-m-d H:i:s");
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
                if ((int)$order_detail['qty'] + (int)$order_detail['shipped_qty'] > (int)$order_detail['qty']) {
                    throw new \Exception(Message::get("V013", 'ship_qty', 'shipped_qty'));
                }
                $inventory = WarehouseDetail::model()->select('quantity')->where([
                    'product_id'   => $order_detail['product_id'],
                    'warehouse_id' => $input_detail['warehouse_id'],
                    'batch_id'     => $input_detail['batch_id'],
                    'unit_id'      => $input_detail['unit_id'],
                    'company_id'   => TM::getCurrentCompanyId(),
                ])->first();
                if (empty($inventory) || $inventory->quantity < $order_detail['qty']) {
                    throw new \Exception(Message::get("V051", $item['code']));
                }
                if (empty($item['weight_class']) || !in_array($item['weight_class'], ['GRAM', 'KG'])) {
                    throw new \Exception(Message::get("V004",
                        "weight_class (" . Message::get("products") . " #{$order_detail['product_id']})", 'GRAM|KG'));
                }
                $totalWeigth           += $order_detail['qty'] * $item['weight'] * $weight_converts[$item['weight_class']];
                $products[]            = [
                    "name"     => $item['name'] ?? $order_detail['product_id'],
                    "weight"   => $order_detail['qty'] * $item['weight'] * $weight_converts[$item['weight_class']],
                    "quantity" => (int)$order_detail['qty'],
                    "price"    => $item['price'] ?? $order_detail['price'],
                    "length"   => round($order_detail['qty'] * $item['length']),
                    "width"    => round($order_detail['qty'] * $item['width']),
                    "height"   => round($order_detail['qty'] * $item['height']),
                ];
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
                    'created_by'      => TM::getCurrentUserId(),
                ];
            }
            $codeIndex = 0;
            $lastShip  = ShippingOrder::model()->where('ship_code', 'like',
                "{$order->code}-" . SHIPPING_PARTNER_TYPE_GHN . "-%")->orderBy('id', 'desc')->first();
            if (!empty($lastShip)) {
                $codeIndex = explode("-" . SHIPPING_PARTNER_TYPE_GHN . "-", $lastShip->ship_code);
                $codeIndex = (int)($codeIndex[1] ?? 0);
            }
            $shipCode  = $order->code . "-" . SHIPPING_PARTNER_TYPE_GHN . "-" . (str_pad(++$codeIndex, 2, '0',
                    STR_PAD_LEFT));
            $order_payment = 0;
            if($order->payment_method == 'CASH'){
                $order_payment = 2;
                if($order->is_freeship == 1){
                    $order_payment = 3;
                }
            }
            $pickMoney = $order_payment == 2 || $order_payment == 3 ? $cod_amont : 0;
            $ship_fee  = !empty($order->free_ship) && $order->free_ship == 1 ? 1 : 2;
            $param     = [

                // Shipping Info
                "to_phone"           => $order->shipping_address_phone,
                "to_name"            => $order->shipping_address_full_name,
                "to_address"         => $order->shipping_address,
                "to_ward_code"       => $order->getWard->code_ghn,
                "to_district_id"     => $order->getDistrict->code_ghn,

                // Return Info
                "return_address"     => $order->distributor->profile->address,
                "return_district_id" => $order->distributor->profile->district->code_ghn,
                "return_ward_code"   => $order->distributor->profile->ward->code_ghn,
                "return_phone"       => $order->distributor->phone,
                "client_order_code"  => $shipCode,
                // COD
                "cod_amount"         => $order_payment == 2 || $order_payment == 3 ? $cod_amont : 0,

                // More Info
                "note"               => $order->shipping_note,
                "weight"             => $totalWeigth,
                "service_type_id"    => (int)$order->shipping_service,
                "payment_type_id"    => $ship_fee,
                "required_note"      => 'KHONGCHOXEMHANG',
                //                "pick_shift"         => [!empty($input['pick_shift']) ? $input['pick_shift'] : 2],
                "items"              => $products,
                //                "deliver_work_shift" => $ship_at[0],
            ];

            $client    = new Client();
            if (!empty($order->distributor_id)) {
                $distribute = $order->distributor->phone;
                $outletGHN  = $client->post(env("GHN_END_POINT") . "/shiip/public-api/v2/shop/all", [
                    'headers' => ['Content-Type' => 'application/json', 'Token' => env("GHN_TOKEN")],
                ]);
                $outletGHN  = $outletGHN->getBody()->getContents() ?? null;
                $outletGHN  = !empty($outletGHN) ? json_decode($outletGHN, true) : [];
                $listStore  = array_column($outletGHN['data']['shops'], 'phone');
                $key        = array_search($distribute, $listStore);
                $shop_id    = $outletGHN['data']['shops'][$key];

            }
            $response = $client->post(env("GHN_END_POINT") . "/shiip/public-api/v2/shipping-order/create", [
                'headers' => ['Content-Type' => 'application/json', 'Token' => env("GHN_TOKEN"), 'ShopId' => !empty($shop_id) ? $shop_id['_id'] : static::$shop_store_id
                ],
                'body'    => json_encode($param),
            ]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];
            if (empty($response['message']) && $response['message'] != 'Success') {
                throw new \Exception(json_decode($response['code_message_value']));
            }
            // Update Order
            $order->shipping_info_code = $response['data']['order_code'] ?? null;
            $order->shipping_info_json = !empty($response['data']) ? json_encode($response['data']) : null;
            $order->status             = OrderStatus::SHIPPING;
            $order->save();

            // Create Shipping Order
            $shippingOrder                = new ShippingOrder();
            $shippingOrder->type          = "GHN";
            $shippingOrder->code          = $order->code;
            $shippingOrder->name          = $order->code;
            $shippingOrder->code_type_ghn = $response['data']['order_code'];
//            $shippingOrder->partner_id = $response['order']['partner_id'];
            $shippingOrder->status      = 'SHIPPING';
            $shippingOrder->status_text = 'Sẵn sàng giao hàng';
            $shippingOrder->description = $order->shipping_note;
            $shippingOrder->ship_fee    = $response['data']['total_fee'];
//            $shippingOrder->estimated_pick_time = $response['order']['estimated_pick_time'];
            $shippingOrder->estimated_deliver_time = $response['data']['expected_delivery_time'];
            $shippingOrder->pick_money             = $pickMoney;
            $shippingOrder->transport              = $order->order_service;
            $shippingOrder->order_id               = $order->id;
            $shippingOrder->ship_code              = $shipCode;
            $shippingOrder->count_print            = 0;
            $shippingOrder->company_id             = TM::getCurrentCompanyId();
            $shippingOrder->result_json            = json_encode($response['data']);
            $shippingOrder->created_at             = date("Y-m-d H:i:s");
            $shippingOrder->created_by             = TM::getCurrentUserId();
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
        catch (\GuzzleHttp\Exception\RequestException  $exception) {
            return ["status" => 'error', 'order' => $order->code, 'message' => 'Không tạo được lệnh giao hàng đơn hàng #'.$order->code.''];
        }
        return ['status' => 'success', 'success' => true, 'shipping_orders' => $shippingOrder, 'warehouse' =>$warehouse];
    }

    public static final function getShipFeeGHN(Request $request, $store_id)
    {
        $input       = $request->all();
        $totalWeight = 0;
        try {

            if (!empty($input['cart_id'])) {
                $product_cart = Cart::model()->where('id', $input['cart_id'])->first();
                if (empty($product_cart)) {
                    throw new \Exception(Message::get("V003", $input['cart_id']));
                }
                $to_ward_code     = $product_cart->getWard->code_ghn;
                $to_district_code = $product_cart->getDistrict->code_ghn;
                if (!empty($product_cart->distributor_phone)) {
                    $distribute_phone = static::CheckPhoneDistributor($product_cart->distributor_phone);
                }
                $weight = $product_cart->details->map(function ($detail) {
                    $weight_converts = ['GRAM' => 1, 'KG' => 1000];
                    return [
                        'weigth' => $detail['quantity'] * $detail->product->weight * $weight_converts[($detail->product->weight_class)],
                    ];
                });
                foreach ($weight as $key) {
                    $totalWeight += $key['weigth'];
                }

            }
            if (!empty($input['order_id'])) {
                $weight_converts = ['GRAM' => 1, 'KG' => 1000];
                $order           = Order::model()->with(['details.product', 'store', 'customer.profile'])->where('id',
                    $input['order_id'])->first();
                if (empty($order)) {
                    throw new \Exception(Message::get("V003", $input['order_id']));
                }
                $ward             = Ward::select('code_ghn')->where('code', $order->shipping_address_ward_code)->first();
                $to_ward_code     = $ward->code_ghn;
                $district         = District::select('code_ghn')->where('code', $order->shipping_address_district_code)->first();
                $to_district_code = $district->code_ghn;
                foreach ($order->details as $input_detail) {
                    $totalWeight += $input_detail['qty'] * $input_detail->product['weight'] * $weight_converts[$input_detail->product['weight_class']];
                }
                if (!empty($order->distributor_phone)) {
                    $distribute_phone = static::CheckPhoneDistributor($order->distributor_phone);
                }
                if (!empty($input['distributor_code'])) {
                    $distributor      = Distributor::model()->where('code', $input['distributor_code'])->first();
                    $distribute_phone = static::CheckPhoneDistributor($distributor->users->phone);
                }
            }
            $param    = [
                "to_ward_code"    => (int)$to_ward_code,
                "to_district_id"  => (int)$to_district_code,
                "service_type_id" => !empty($input['ORDER_SERVICE']) ? (int)$input['ORDER_SERVICE'] : (!empty($order) ? (int)$order->shipping_service : 2),
                "weight"          => $totalWeight,
            ];

            $client   = new Client();
            $response = $client->get(env("GHN_END_POINT") . "/shiip/public-api/v2/shipping-order/fee", [
                'headers' => ['Content-Type' => 'application/json', 'Token' => env("GHN_TOKEN"), 'ShopId' => !empty($distribute_phone) ? $distribute_phone['_id'] : static::$shop_store_id],
                'body'    => json_encode($param),
            ]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];
        }
        catch (\RequestException  $exception) {
            return $exception->getRequest() . "\n";
        }
        return $response['data']['total'];
    }

    public static final function getService(Request $request, $store)
    {
        $input  = $request->all();
        $client = new Client();
        try {
            if (empty($input['cart_id'])) {
                throw new \Exception(Message::get("V001", 'cart_id'));
            }
//            if (empty($input['cart_id'])) {
//                $response = $client->get(env("GHN_END_POINT") . "/shiip/public-api/v2/shift/date", [
//                    'headers' => ['Content-Type' => 'application/json', 'Token' => env("GHN_TOKEN")],
//                ]);
//                $response = $response->getBody()->getContents() ?? null;
//                $response = !empty($response) ? json_decode($response, true) : [];
//                return $response;
//            }
            $product_cart = Cart::model()->where('id', $input['cart_id'])->first();
            if (empty($product_cart)) {
                throw new \Exception(Message::get("V003", $input['cart_id']));
            }
            $to_district_code = $product_cart->getDistrict->code_ghn;
            if (!empty($product_cart->distributor_code)) {
                $from_district_code = $product_cart->getDistrictDistributor->code_ghn;
                $distribute_phone   = static::CheckPhoneDistributor($product_cart->distributor_phone);
            }
            if (empty($product_cart->distributor_code)) {
                $store              = Store::find($store);
                $from_district_code = $store->district->code_ghn;
            }
            $param    = [
                'shop_id'       => !empty($distribute_phone) ? $distribute_phone['_id'] : static::$shop_store_id,
                "to_district"   => (int)$to_district_code,
                "from_district" => (int)$from_district_code,
            ];
            $response = $client->get(env("GHN_END_POINT") . "/shiip/public-api/v2/shipping-order/available-services", [
                'headers' => ['Content-Type' => 'application/json', 'Token' => env("GHN_TOKEN")],
                'body'    => json_encode($param),
            ]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];
        }
        catch (\GuzzleHttp\Exception\RequestException  $exception) {
            return [];
        }
        $data = [];
        foreach ($response['data'] as $item) {
            $data[] = [
                'service_type_id' => $item['service_type_id'],
                'short_name'      => $item['short_name'],
                'fee_service'     => null
            ];
        }
        return $data;
    }

    public static final function getTimeShip(Request $request, $store)
    {
        $input  = $request->all();
        $client = new Client();
        try {
            if (empty($input['cart_id'])) {
                throw new \Exception(Message::get("V001", 'cart_id'));
            }
            $product_cart = Cart::model()->where('id', $input['cart_id'])->first();
            if (empty($product_cart)) {
                throw new \Exception(Message::get("V003", $input['cart_id']));
            }
            $to_district_code = $product_cart->getDistrict->code_ghn;
            $to_ward_code     = $product_cart->getWard->code_ghn;
            if (empty($product_cart->distributor_phone)) {
                $store              = Store::find($store);
                $from_district_code = $store->district->code_ghn;
                $from_ward_code     = $store->ward->code_ghn;
            }
            if (!empty($product_cart->distributor_phone)) {
                $from_district_code = $product_cart->getDistrictDistributor->code_ghn;
                $from_ward_code     = $product_cart->getWardDistributor->code_ghn;
                $distribute_phone   = static::CheckPhoneDistributor($product_cart->distributor_phone);
            }
            $param    = [
                'shop_id'          => !empty($distribute_phone) ? $distribute_phone['_id'] : static::$shop_store_id,
                "to_district_id"   => (int)$to_district_code,
                "to_ward_code"     => ($to_ward_code),
                "from_district_id" => (int)$from_district_code,
                "from_ward_code"   => ($from_ward_code),
                "service_id"       => !empty($input['ORDER_SERVICE']) ? (int)$input['ORDER_SERVICE'] : 2,
            ];
            $response = $client->get(env("GHN_END_POINT") . "/shiip/public-api/v2/shipping-order/leadtime", [
                'headers' => ['Content-Type' => 'application/json', 'Token' => env("GHN_TOKEN"), 'ShopId' => !empty($distribute_phone) ? $distribute_phone['_id'] : static::$shop_store_id],
                'body'    => json_encode($param),

            ]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];

        }
        catch (\RequestException  $exception) {
            return json_encode($exception->getRequest()) . "\n";
        }
        return ['status' => 'success', 'success' => true, "data" => $response['data']];
    }


    public static final function cancelOrder($shipping_code, ShippingOrder $shippingOrder)
    {
        try {
            $client           = new Client();
            $check_distribute = $shippingOrder->order->distributor->phone;
            if (!empty($check_distribute)) {
                $shop_id = static::CheckPhoneDistributor($check_distribute);
            }
            $param    = [
                'order_codes' => [$shipping_code],
            ];
            $response = $client->get(env("GHN_END_POINT") . "/shiip/public-api/v2/switch-status/cancel", [
                'headers' => ['Content-Type' => 'application/json', 'Token' => env("GHN_TOKEN"), 'ShopId' => !empty($shop_id) ? $shop_id['_id'] : static::$shop_store_id],
                'body'    => json_encode($param),
            ]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];
            if (empty($response['message']) && $response['message'] != 'Success') {
                throw new \Exception($response['message'] ?? "Some thing went Wrong!");
            }
        }
        catch (\Exception $exception) {
            return ['status' => 'error', 'success' => false, 'message' => $exception->getMessage()];
        }
        return $response;
    }

    public static final function viewDetail($shipping_code)
    {
        try {
            $client   = new Client();
            $param    = [
                'order_code' => $shipping_code,
            ];
            $response = $client->get(env("GHN_END_POINT") . "/shiip/public-api/v2/shipping-order/detail", [
                'headers' => ['Content-Type' => 'application/json', 'Token' => env("GHN_TOKEN")],
                'body'    => json_encode($param)
            ]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];
            if (empty($response['message']) && $response['message'] != 'Success') {
                throw new \Exception($response['message'] ?? "Some thing went Wrong!");
            }
        }
        catch (\Exception $exception) {
            return ['status' => 'error', 'success' => false, 'message' => $exception->getMessage()];
        }
        return $response;
    }

    public static final function return($shipping_code)
    {
        try {
            $client   = new Client();
            $param    = [
                'order_codes' => [$shipping_code],
            ];
            $response = $client->get(env("GHN_END_POINT") . "/shiip/public-api/v2/switch-status/return", [
                'headers' => ['Content-Type' => 'application/json', 'Token' => env("GHN_TOKEN")],
                'body'    => json_encode($param)
            ]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];
            if (empty($response['message']) && $response['message'] != 'Success') {
                throw new \Exception($response['message'] ?? "Some thing went Wrong!");
            }
        }
        catch (\Exception $exception) {
            return ['status' => 'error', 'success' => false, 'message' => $exception->getMessage()];
        }
        return $response;
    }

    static function CheckPhoneDistributor($phone)
    {
        $client     = new Client();
        $outletGHN  = $client->post(env("GHN_END_POINT") . "/shiip/public-api/v2/shop/all", [
            'headers' => ['Content-Type' => 'application/json', 'Token' => env("GHN_TOKEN")],
        ]);
        $outletGHN  = $outletGHN->getBody()->getContents() ?? null;
        $outletGHN  = !empty($outletGHN) ? json_decode($outletGHN, true) : [];
        $shop_phone = array_column($outletGHN['data']['shops'], 'phone');
        $key        = array_search($phone, $shop_phone);
        $shop_id    = $outletGHN['data']['shops'][$key];
        return $shop_id;
    }
}
