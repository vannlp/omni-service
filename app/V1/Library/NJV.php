<?php
/**
 * User: TrungThanh
 * Date: 22/06/2021
 * Time: 10:24 AM
 */

namespace App\V1\Library;


use App\Batch;
use App\Order;
use App\Distributor;
use App\Cart;
use App\Store;
use App\ShipFeeNJV;
use App\OrderDetail;
use App\OrderStatus;
use App\Product;
use App\Setting;
use App\ShippingOrder;
use App\ShippingOrderDetail;
use App\Supports\Message;
use App\TM;
use App\Unit;
use App\Warehouse;
use App\WarehouseDetail;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NJV
{

    /**
     * Ninja Van constructor.
     */
    public function __construct()
    {

    }

    public static function getApiToken()
    {
        try {
            $param = [
                'client_id'        => env("NJV_CLIENT_ID"),
                'client_secret'    => env("NJV_CLIENT_SECRET"),
                'grant_type'       => 'client_credentials'
            ];

            $client = new Client();
            $response = $client->post(env("NJV_END_POINT") . "/VN/2.0/oauth/access_token", [
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

    public static function getShipFee(Request $request, $store)
    {
        $input = $request->all();
        $weight = 0;
        try {
            if(!empty($input['cart_id'])){
                $cart  = Cart::with('details.product')->where('id',$input['cart_id'])->first();
                if (empty($cart)) {
                    throw new \Exception(Message::get("V003", $input['cart_id']));
                }
                $totalWeight = $cart->details->map(function ($detail) {
                    $weight_converts = ['GRAM' => 0.001, 'KG' => 1];
                    return [
                        'weigth' => $detail['quantity'] * $detail->product->weight * $weight_converts[($detail->product->weight_class)],
                    ];
                });
                foreach ($totalWeight as $key) {
                    $weight += $key['weigth'];
                }

                $weight = round($weight);
                $price = array_sum(array_column($cart->details->toArray(), 'price'));
                
                // if(empty($cart->customer_city_code)){
                //     throw new \Exception(Message::get("V003", 'customer'));
                // }
                if(!empty($cart->distributor_code)){
                    $senderProvice = $cart->getCityDistributor->name;
                    $senderArea = $cart->getCityDistributor->description;
                    $senderDistrict = $cart->getDistrictDistributor->name;
                    $senderWard = $cart->getWardDistributor->name;
                }
                $receiverProvince = $cart->getCity->name;
                $receiverArea = $cart->getCity->description;
                $receiverDistrict = $cart->getDistrict->name;
                $receiverWard = $cart->getWard->name;
                
                if(empty($cart->distributor_code)){
                    $store = Store::find($store);
                    $senderProvice = $store->city_name;
                    $senderArea = $store->city->description;
                    $senderDistrict = $store->district_type." ".$store->district_name;
                    $senderWard = $store->ward_type." ".$store->ward_name;
                }
            }
            

            if(!empty($input['order_id'])){
                $order = Order::with("details.product","addressDistributor.getCity")->where('id',$input['order_id'])->first();
                if (empty($order)) {
                    throw new \Exception(Message::get("V003", $input['order_id']));
                }
                $weight_converts = ['GRAM' => 0.001, 'KG' => 1];
                foreach ($order->details as $input_detail) {
                    $weight  += $input_detail['qty'] * $input_detail->product['weight'] * $weight_converts[$input_detail->product['weight_class']];
                }
                $price = $order->total_price;
                if(!empty($order->distributor_code)) {
                    $senderProvice  = $order->addressDistributor->getCity->name;
                    $senderDistrict = $order->addressDistributor->getDistrict->name;
                    $senderWard = $order->addressDistributor->getWard->name;
                }

                if(empty($order->distributor_code)){
                    $store = Store::find(TM::getCurrentStoreId());
                    $senderProvice = $store->city_name;
                    $senderArea = $store->city->description;
                    $senderDistrict = $store->district_type." ".$store->district_name;
                    $senderWard = $store->ward_type." ".$store->ward_name;
                }
                if(!empty($input['distributor_code'])){
                    $distributor = Distributor::model()->where('code',$input['distributor_code'])->first();
                    $senderProvice = $distributor->getCity->name;
                    $senderArea = $store->getCity->description;
                    $senderDistrict = $distributor->getDistrict->name;
                    $senderWard = $distributor->getWard->name;
                }
                $receiverProvince = $order->getCity->name;
                $receiverArea = $store->getCity->description;
                $receiverDistrict = $order->getDistrict->name;
                $receiverWard = $order->getWard->name;
            }
            if($weight == 0){
                $weight = 1;
            }
            $region = "Liên vùng gần";
            $time = "2 - 4 ngày";
            if(($senderArea == "MN" && $receiverArea == "MB") || ($senderArea == "MB" && $receiverArea == "MN")){
                $region = "Liên vùng xa";
                // $time = "2 - 4 ngày";
            }
            if($senderArea == $receiverArea){
                $region = "Nội vùng";
                $time = "khoảng 2 ngày";
            }
            if($senderProvice == $receiverProvince){
                $region = 'Nội tỉnh';
                $time = "ngày hôm sau";
            }
            if($weight < 6){
                $result = ShipFeeNJV::where("type_ship",$region)->where("weight",$weight)->first();
                $response = $result->price_ship;
            }
            if($weight >= 6){
                $result = ShipFeeNJV::where("type_ship",$region)->where("weight",5)->first();
                if($region == "Nội tỉnh"){
                    $response = $result->price_ship + (2100 * ($weight - 5));
                }
                if($region == "Nội vùng"){
                    $response = $result->price_ship + (3400 * ($weight - 5));
                }
                if($region == "Liên vùng gần"){
                    $response = $result->price_ship + (4600 * ($weight - 5));
                }
                if($region == "Liên vùng xa"){
                    $response = $result->price_ship + (6100 * ($weight - 5));
                }
            }

        } catch (\RequestException  $exception) {
            return $exception->getRequest() . "\n";
        }

        return ['fee' => $response, 'time' => $time];
    }

    public static final function sendOrder(Order $order, $token)
    {
        try {
            if (empty($order) || empty($order->id)) {
                throw new \Exception(Message::get("V001", Message::get("orders")));
            }
            $order = Order::model()->with(['details.product.warehouse', 'store', 'customer.profile'])->where('id',
                $order->id)->first();
            if(!empty($order->distributor_code)){
                $order = Order::model()->with(['details.product', 'addressDistributor', 'store', 'customer.profile'])->where('id',
                $order->id)->first();
            }
            if (empty($order)) {
                throw new \Exception(Message::get("V003", Message::get("orders")));
            }
            $cod_amount = $order->total_price; 
            if (!empty($order->ship_fee) && !empty($order->total_price) && $order->ship_fee != 0){
                $cod_amount = $order->total_price - (int)$order->ship_fee;
            }
            $weight_converts = ['KG' => 1, 'GRAM' => 0.001];
            $order_details = [];
            $warehouse = [];
            foreach ($order->details as $key => $detail) {
                $order_details[$detail->id] = $detail->toArray();
                array_push($warehouse, $detail->product);
                $warehouse[$key]['order_detail_id'] = $detail->id;
                $warehouse[$key]['warehouse_id'] = $detail->product->warehouse->warehouse_id;
                $warehouse[$key]['batch_id'] = $detail->product->warehouse->batch_id;
            }

            $products = [];
            $shippingDetailParam = [];

            $allProduct = Product::model()->select(['id', 'code', 'name'])
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
            $now = Carbon::now('Asia/Ho_Chi_Minh');

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
                
                $products[]            = [
                    "name"     => $item['name'] ?? $order_detail['product_id'],
                    "weight"   => number_format($order_detail['qty'] * $item['weight'] * $weight_converts[$item['weight_class']],
                        2),
                    "quantity" => (int)$order_detail['qty'],
                    "price"    => $item['price'] ?? $order_detail['price'],
                    "length"   => $order_detail['qty'] * $item['length'],
                    "width"    => $order_detail['qty'] * $item['width'],
                    "height"   => $order_detail['qty'] * $item['height'],
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
            $lastShip = ShippingOrder::model()->where('ship_code', 'like',
                "{$order->code}-" . SHIPPING_PARTNER_TYPE_NJV . "-%")->orderBy('id', 'desc')->first();

            $codeIndex = 0;
            if (!empty($lastShip)) {
                $codeIndex = explode("-" . SHIPPING_PARTNER_TYPE_NJV . "-", $lastShip->ship_code);
                $codeIndex = (int)($codeIndex[1] ?? 0);
            }
            $shipCode = $order->code . "-" . SHIPPING_PARTNER_TYPE_NJV . "-" . (str_pad(++$codeIndex, 2, '0',
                    STR_PAD_LEFT));
            if($order->payment_method != 'CASH'){
                $order_payment = 4;
                if($order->is_freeship == 1){
                    $order_payment = 1;
                }
            }
            if($order->payment_method == 'CASH'){
                $order_payment = 2;
                if($order->is_freeship == 1){
                    $order_payment = 3;
                }
            }
            $param = [
                "service_type"              => "Parcel",
                "service_level"             => "Nextday",
                "requested_tracking_number" => $shipCode,
                "reference"                 => [
                    "merchant_order_number" => $shipCode,
                ],
                "from"  => [
                    "name"                  => !empty($order->distributor_code) ? $order->distributor_name : $order->store->name,
                    "phone_number"          => !empty($order->distributor_code) ? $order->distributor_phone : $order->store->contact_phone,
                    "email"                 => !empty($order->distributor_code) ? $order->distributor_email : $order->store->email,
                    "address"   => [
                        "address1"          => !empty($order->distributor_code) ? $order->addressDistributor->ward_full_name : $order->store->address,
                        "address2"          => "",
                        "city"              => !empty($order->distributor_code) ? $order->addressDistributor->city_full_name : $order->store->city_type." ".$order->store->city_name,
                        "district"          => !empty($order->distributor_code) ? $order->addressDistributor->district_full_name : $order->store->district_type." ".$order->store->district_name,
                        "ward"              => !empty($order->distributor_code) ? $order->addressDistributor->ward_full_name : $order->store->ward_type." ".$order->store->ward_name,
                        "address_type"      => $order->shipping_note == "Tất cả các ngày trong tuần" ? 'home' : 'office',
                        "country"           => "VN",
                        "postcode"          => !empty($order->distributor_postcode) ? $order->distributor_postcode : "700000"       
                    ]
                ],
                "to"    => [
                    "name"                  => $order->shipping_address_full_name,
                    "phone_number"          => $order->shipping_address_phone,
                    "email"                 => $order->customer->email,
                    "address" => [
                        "address1"          => !empty($order->street_address) ? $order->street_address : $order->shipping_address,
                        "address2"          => "",
                        "city"              => $order->shipping_address_city_name,
                        "district"          => $order->shipping_address_district_name,
                        "ward"              => $order->shipping_address_ward_name,
                        "address_type"      => $order->shipping_note == "Tất cả các ngày trong tuần" ? 'home' : 'office',
                        "country"           => "VN",
                        "postcode"          => !empty($order->customer_postcode) ? $order->customer_postcode : "700000"
                    ]
                ],
                "parcel_job"    => [
                    "is_pickup_required"    => true,
                    "pickup_service_type"   => "Scheduled",
                    "pickup_service_level"  => "Standard",
                    "pickup_date"           => $now->format('Y-m-d'),
                    "pickup_timeslot"   => [
                        "start_time"        => "09:00",
                        "end_time"          => "22:00",
                        "timezone"          => "Asia/Ho_Chi_Minh"
                    ],
                    "pickup_instructions"   => $order->shipping_note,
                    "delivery_instructions" => $order->shipping_note,
                    "delivery_start_date"   => $now->addDay()->format('Y-m-d'),
                    "delivery_timeslot" => [
                        "start_time"        => "09:00",
                        "end_time"          => "22:00",
                        "timezone"          => "Asia/Ho_Chi_Minh"
                    ],
                    "cash_on_delivery"      => $order_payment == 2 || $order_payment == 3 ? $cod_amount : 0,
                    "insured_value"         => !empty($cod_amount) ? $cod_amount : array_sum(array_column($products, 'price')),
                    "dimensions"    => [
                        "weight"            => array_sum(array_column($products, 'weight')),
                        // More info
                        // "length"            => 30,
                        // "width"             => 30,
                        // "height"            => 25
                    ],
                    "items"     => [
                        [
                          "item_description"    => "Đơn hàng ".$shipCode,
                          "quantity"            => 1,
                          "is_dangerous_good"   =>  false,
                        ]
                    ]
                ]
            ];
            $client = new Client();
            $response = $client->post(env("NJV_END_POINT") . "/VN/4.1/orders", [
                'headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $token],
                'body'    => json_encode($param),
            ]);
            $data = $response->getBody()->getContents() ?? null;
            $data = !empty($data) ? json_decode($data, true) : [];
            $check = $data['tracking_number'] ?? null;
            if(!empty($check)){
                // Update Order
                $order->shipping_info_code = $data['tracking_number'] ?? null;
                $order->shipping_info_json = !empty($data) ? json_encode($data) : null;
                $order->status = OrderStatus::SHIPPING;
                $order->save();
                // Create Shipping Order
                $shippingOrder = new ShippingOrder();
                $shippingOrder->type = "NJV";
                $shippingOrder->code = $data['tracking_number'];
                $shippingOrder->name = $data['tracking_number'];
                // $shippingOrder->partner_id = $response['partner_id'];
                $shippingOrder->status = 'SHIPPING';
                $shippingOrder->status_text = 'Sẵn sàng giao hàng';
                $shippingOrder->description = $order->shipping_note;
                // $shippingOrder->ship_fee = $response['fee'];
                $shippingOrder->estimated_pick_time = $data['parcel_job']['pickup_date'];
                $shippingOrder->estimated_deliver_time = $data['parcel_job']['delivery_start_date'];
                $shippingOrder->order_id = $order->id;
                $shippingOrder->ship_code = $shipCode;
                $shippingOrder->company_id = TM::getCurrentCompanyId();
                $shippingOrder->result_json = json_encode($data);
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
                return ['status' => 'success', 'success' => true, 'shipping_orders' => $shippingOrder, 'warehouse' =>$warehouse];
            }
        } catch (\Exception $exception) {
            if(strpos($exception->getMessage(),'401 Unauthorized') != false && strpos($exception->getMessage(),'ninjavan') != false && $exception->getCode() == 401){
                $setting = Setting::where('code','NINJA-VAN')->first();
                $setting->value = self::getApiToken();
                $setting->updated_at = $now->format('Y-m-d H:i:s');
                $setting->save();
                DB::commit();
                self::sendOrder($order,$setting->value);
            }else{
                return ['status' => 'error', 'success' => false, 'message' => "Không tạo được lệnh giao hàng đơn hàng #".$order->code."", 'ex' => $exception->getLine()];
            }
        }
    }

    public static function cancelOrder($shipping_code,$token)
    {
        try {
            $client = new Client();
            $response = $client->delete(env("NJV_END_POINT") . "/VN/2.2/orders/$shipping_code", [
                'headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $token]
            ]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];
            
        } catch (\Exception $exception) {
            return ['status' => 'error', 'success' => false, 'message' => $exception->getMessage()];
        }

        return ['status' => 'success', 'success' => true, "data" => $response];
    }
}