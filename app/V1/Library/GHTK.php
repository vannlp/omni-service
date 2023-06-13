<?php
/**
 * User: dai.ho
 * Date: 26/06/2020
 * Time: 10:24 AM
 */

namespace App\V1\Library;


use App\Batch;
use App\Order;
use App\OrderDetail;
use App\OrderStatus;
use App\Product;
use App\ShippingOrder;
use App\ShippingOrderDetail;
use App\Supports\Message;
use App\TM;
use App\Unit;
use App\Warehouse;
use App\WarehouseDetail;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class GHTK
{
    const STATUS = [
        -1  => "Hủy đơn hàng",
        1   => "Chưa tiếp nhận",
        2   => "Đã tiếp nhận",
        3   => "Đã lấy hàng/Đã nhập kho",
        4   => "Đã điều phối giao hàng/Đang giao hàng",
        5   => "Đã giao hàng/Chưa đối soát",
        6   => "Đã đối soát",
        7   => "Không lấy được hàng",
        8   => "Hoãn lấy hàng",
        9   => "Không giao được hàng",
        10  => "Delay giao hàng",
        11  => "Đã đối soát công nợ trả hàng",
        12  => "Đã điều phối lấy hàng/Đang lấy hàng",
        13  => "Đơn hàng bồi hoàn",
        20  => "Đang trả hàng (COD cầm hàng đi trả)",
        21  => "Đã trả hàng (COD đã trả xong hàng)",
        123 => "Shipper báo đã lấy hàng",
        127 => "Shipper (nhân viên lấy/giao hàng) báo không lấy được hàng",
        128 => "Shipper báo delay lấy hàng",
        45  => "Shipper báo đã giao hàng",
        49  => "Shipper báo không giao được giao hàng",
        410 => "Shipper báo delay giao hàng",
    ];

    /**
     * GHTK constructor.
     */
    public function __construct()
    {

    }

    public static final function sendOrder(Order $order, Request $request)
    {
        $input = $request->all();

        try {
            if (empty($order) || empty($order->id)) {
                throw new \Exception(Message::get("V001", Message::get("orders")));
            }

            $order = Order::model()->with(['details.product', 'store', 'customer.profile'])->where('id',
                $order->id)->first();
            if (empty($order)) {
                throw new \Exception(Message::get("V003", Message::get("orders")));
            }
            $weight_converts = ['KG' => 1, 'GRAM' => 0.001];
            $receive_at = [1, 2, 3]; // 1 => "Lấy hàng buổi sáng", 2 => "Lấy hàng buổi chiều", 3 => "Lấy hàng buổi tối"
            // $ship_at = [1, 2, 3]; // 1 => "Giao hàng buổi sáng", 2 => "Giao hàng buổi chiều", 3 => "Giao hàng buổi tối"

            $order_details = [];
            foreach ($order->details as $detail) {
                $order_details[$detail->id] = $detail->toArray();
            }

            $products = [];
            $shippingDetailParam = [];

            $transport = 'fly';

            if (!empty($input['transport']) && in_array($input['transport'], ['fly', 'road'])) {
                $transport = $input['transport'];
            }

            $allProduct = Product::model()->select(['id', 'code', 'name'])
                ->whereIn('id', array_column($order_details, 'product_id'))
                ->get()->pluck(null, 'id')->toArray();
            $allUnit = Unit::model()->select(['id', 'code', 'name'])
                ->whereIn('id', array_column($input['details'], 'unit_id'))
                ->get()->pluck(null, 'id')->toArray();
            $allWarehouse = Warehouse::model()->select(['id', 'code', 'name'])
                ->whereIn('id', array_column($input['details'], 'warehouse_id'))
                ->get()->pluck(null, 'id')->toArray();
            $allBatch = Batch::model()->select(['id', 'code', 'name'])
                ->whereIn('id', array_column($input['details'], 'batch_id'))
                ->get()->pluck(null, 'id')->toArray();
            $now = date("Y-m-d H:i:s");
            foreach ($input['details'] as $input_detail) {
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
                if ((int)$input_detail['ship_qty'] + (int)$order_detail['shipped_qty'] > (int)$order_detail['qty']) {
                    throw new \Exception(Message::get("V013", 'ship_qty', 'shipped_qty'));
                }

                $inventory = WarehouseDetail::model()->select('quantity')->where([
                    'product_id'   => $order_detail['product_id'],
                    'warehouse_id' => $input_detail['warehouse_id'],
                    'batch_id'     => $input_detail['batch_id'],
                    'unit_id'      => $input_detail['unit_id'],
                    'company_id'   => TM::getCurrentCompanyId(),
                ])->first();
                if (empty($inventory) || $inventory->quantity < $input_detail['ship_qty']) {
                    throw new \Exception(Message::get("V051", $item['code']));
                }

                if (empty($item['weight_class']) || !in_array($item['weight_class'], ['GRAM', 'KG'])) {
                    throw new \Exception(Message::get("V004",
                        "weight_class (" . Message::get("products") . " #{$order_detail['product_id']})", 'GRAM|KG'));
                }
                $products[] = [
                    "name"     => $item['name'] ?? $order_detail['product_id'],
                    "weight"   => number_format($input_detail['ship_qty'] * $item['weight'] * $weight_converts[$item['weight_class']],
                        2),
                    "quantity" => $input_detail['ship_qty'],
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
                    'ship_qty'        => $input_detail['ship_qty'],
                    'shipped_qty'     => ($input_detail['ship_qty'] + $order_detail['shipped_qty']),
                    'price'           => $order_detail['price'],
                    'total_price'     => $order_detail['total'],
                    'discount'        => $order_detail['discount'],
                    'created_at'      => $now,
                    'created_by'      => TM::getCurrentUserId(),
                ];
            }
            if (empty($order->shipping_address_full_name) || empty($order->shipping_address_city_name) || empty($order->shipping_address_district_name) || empty($order->shipping_address_phone)) {
                throw new \Exception(Message::get("V003", Message::get("receiver_address")));
            }
            if (empty($order->store->address) || empty($order->store->city_name) || empty($order->store->district_name) || empty($order->store->contact_phone)) {
                throw new \Exception(Message::get("V003", Message::get("pick_address")));
            }
            $lastShip = ShippingOrder::model()->where('ship_code', 'like',
                "{$order->code}-" . SHIPPING_PARTNER_TYPE_GHTK . "-%")->orderBy('id', 'desc')->first();

            $codeIndex = 0;
            if (!empty($lastShip)) {
                $codeIndex = explode("-" . SHIPPING_PARTNER_TYPE_GHTK . "-", $lastShip->ship_code);
                $codeIndex = (int)($codeIndex[1] ?? 0);
            }
            $shipCode = $order->code . "-" . SHIPPING_PARTNER_TYPE_GHTK . "-" . (str_pad(++$codeIndex, 2, '0',
                    STR_PAD_LEFT));

            $pickMoney = Arr::get($input, 'pick_money', 0) > 0 ? Arr::get($input, 'pick_money', 0) : 0;
            $param = [
                "products" => $products,
                "order"    => [
                    // Store Info
                    "id"              => $shipCode,
                    "pick_name"       => $order->store->name,
                    "pick_money"      => $pickMoney,
                    "pick_address"    => $order->store->address,
                    "pick_province"   => $order->store->city_type . " " . $order->store->city_name,
                    "pick_district"   => $order->store->district_type . " " . $order->store->district_name,
                    "pick_ward"       => $order->store->ward_type . " " . $order->store->ward_name,
                    "pick_tel"        => $order->store->contact_phone,

                    // Shipping Info
                    "tel"             => $order->shipping_address_phone,
                    "name"            => $order->shipping_address_full_name,
                    "address"         => $order->street_address,
                    "province"        => $order->shipping_address_city_type . " " . $order->shipping_address_city_name,
                    "district"        => $order->shipping_address_district_type . " " . $order->shipping_address_district_name,
                    "ward"            => $order->shipping_address_ward_type . " " . $order->shipping_address_ward_name,
                    "hamlet"          => "Khác",
                    "email"           => "h.sydai@gmail.com",//$order->customer->email,

                    // Return Info
                    "return_name"     => $order->store->name,
                    "return_address"  => $order->store->address,
                    "return_province" => $order->store->city_type . " " . $order->store->city_name,
                    "return_district" => $order->store->district_type . " " . $order->store->district_name,
                    "return_ward"     => $order->store->ward_type . " " . $order->store->ward_name,
                    "return_tel"      => $order->store->contact_phone,
                    "return_email"    => "h.sydai@gmail.com",//$order->store->email,

                    // More Info
                    "is_freeship"     => (int)$input['free_ship'], // 1: free ship | 0: not free
                    "weight_option"   => "kilogram", // gram | kilogram
                    "pick_work_shift" => $receive_at[0],
                    "note"            => Arr::get($input, 'description', ''),
                    "transport"       => $transport,
                    //                "deliver_work_shift" => $ship_at[0],
                ],
            ];

            $client = new Client();
            $response = $client->post(env("GHTK_END_POINT") . "/services/shipment/order/?ver=1.5", [
                'headers' => ['Content-Type' => 'application/json', 'Token' => env("GHTK_TOKEN")],
                'body'    => json_encode($param),
            ]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];

            if (empty($response['success'])) {
                throw new \Exception($response['message'] ?? "Some thing went Wrong!");
            }

            // Update Order
            $order->shipping_info_code = $response['order']['label'] ?? null;
            $order->shipping_info_json = !empty($response['order']) ? json_encode($response['order']) : null;
            $order->status = OrderStatus::SHIPPING;
            $order->save();

            // Create Shipping Order
            $shippingOrder = new ShippingOrder();
            $shippingOrder->type = "GHTK";
            $shippingOrder->code = $response['order']['label'];
            $shippingOrder->name = $response['order']['label'];
            $shippingOrder->partner_id = $response['order']['partner_id'];
            $shippingOrder->status = $response['order']['status_id'];
            $shippingOrder->status_text = self::STATUS[$response['order']['status_id']] ?? null;
            $shippingOrder->description = Arr::get($input, 'description', '');
            $shippingOrder->ship_fee = $response['order']['fee'];
            $shippingOrder->estimated_pick_time = $response['order']['estimated_pick_time'];
            $shippingOrder->estimated_deliver_time = $response['order']['estimated_deliver_time'];
            $shippingOrder->pick_money = $pickMoney;
            $shippingOrder->transport = $transport;
            $shippingOrder->order_id = $order->id;
            $shippingOrder->ship_code = $shipCode;
            $shippingOrder->company_id = TM::getCurrentCompanyId();
            $shippingOrder->result_json = json_encode($response['order']);
            $shippingOrder->created_at = date("Y-m-d H:i:s");
            $shippingOrder->created_by = TM::getCurrentUserId();
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

        } catch (\Exception $exception) {
            return ['status' => 'error', 'success' => false, 'message' => $exception->getMessage()];
        }

        return ['status' => 'success', 'success' => true, "data" => $response];
//        $input['id'] = $shippingOrder->id;
//        $input['details'] = ShippingOrderDetail::model()->where('shipping_order_id',
//            $shippingOrder->id)->get()->toArray();
//        return ['status' => 'success', 'success' => true, "data" => $input];
    }

    public static final function getShipFee(Request $request)
    {
        $input = $request->all();
        try {
            $requiredField = ['from_city', 'from_district', 'to_city', 'to_district', 'weight'];
            foreach ($requiredField as $field) {
                if (empty($input[$field])) {
                    throw new \Exception(Message::get("V001", $field));
                }
            }

            $param = [
                "pick_address"  => $input['from_address'] ?? null,
                "pick_province" => $input['from_city'],
                "pick_district" => $input['from_district'],
                "province"      => $input['to_city'],
                "district"      => $input['to_district'],
                "address"       => $input['to_address'] ?? null,
                "weight"        => $input['weight'],
            ];

            $client = new Client();
            $response = $client->get(env("GHTK_END_POINT") . "/services/shipment/fee", [
                'headers' => ['Content-Type' => 'application/json', 'Token' => env("GHTK_TOKEN")],
                'query'   => $param,
            ]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];

            if (empty($response['success'])) {
                throw new \Exception($response['message'] ?? "Some thing went Wrong!");
            }
        } catch (\Exception $exception) {
            return ['status' => 'error', 'success' => false, 'message' => $exception->getMessage()];
        }

        return ['status' => 'success', 'success' => true, "data" => $response['fee']];
    }

    public static final function getOrderStatus($shipping_code)
    {
        try {
            $client = new Client();
            $response = $client->get(env("GHTK_END_POINT") . "/services/shipment/v2/$shipping_code", [
                'headers' => ['Content-Type' => 'application/json', 'Token' => env("GHTK_TOKEN")],
            ]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];
            if (empty($response['success'])) {
                throw new \Exception($response['message'] ?? "Some thing went Wrong!");
            }
        } catch (\Exception $exception) {
            return ['status' => 'error', 'success' => false, 'message' => $exception->getMessage()];
        }

        return ['status' => 'success', 'success' => true, "data" => $response['order']];
    }

    public static final function cancelOrder($shipping_code)
    {
        try {
            $client = new Client();
            $response = $client->get(env("GHTK_END_POINT") . "/services/shipment/cancel/$shipping_code", [
                'headers' => ['Content-Type' => 'application/json', 'Token' => env("GHTK_TOKEN")],
            ]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];
            if (empty($response['success'])) {
                throw new \Exception($response['message'] ?? "Some thing went Wrong!");
            }
        } catch (\Exception $exception) {
            return ['status' => 'error', 'success' => false, 'message' => $exception->getMessage()];
        }

        return ['status' => 'success', 'success' => true, "data" => $response];
    }
}