<?php
/**
 * User: TrungThanh
 * Date: 23/06/2021
 * Time: 10:24 AM
 */

namespace App\V1\Library;


use App\Batch;
use App\Cart;
use App\CartDetail;
use App\Distributor;
use App\Order;
use App\OrderDetail;
use App\OrderHistory;
use App\OrderStatus;
use App\Product;
use App\ShipFeeVNP;
use App\ShippingOrder;
use App\ShippingOrderDetail;
use App\Store;
use App\Supports\Message;
use App\TM;
use App\Unit;
use App\Warehouse;
use App\WarehouseDetail;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class VTP
{
    const STATUS
        = [
            -100 => "Đơn hàng mới tạo, chưa duyệt",
            -108 => "Đơn hàng gửi tại bưu cục",
            -109 => "Đơn hàng đã gửi tại điểm thu gom",
            -110 => "Đơn hàng đang bàn giao qua bưu cục",
            100  => "Tiếp nhận đơn hàng từ đối tác 'Viettelpost xử lý đơn hàng'",
            101  => "ViettelPost yêu cầu hủy đơn hàng",
            102  => "Đơn hàng chờ xử lý",
            103  => "Giao cho bưu cục 'Viettelpost xử lý đơn hàng'",
            104  => "Giao cho Bưu tá đi nhận",
            105  => "Bưu Tá đã nhận hàng",
            106  => "Đối tác yêu cầu lấy lại hàng",
            107  => "Đối tác yêu cầu hủy qua API",
            200  => "Nhận từ bưu tá - Bưu cục gốc",
            201  => "Hủy nhập phiếu gửi",
            202  => "Sửa phiếu gửi",
            300  => "Close delivery file",
            301  => "Ðóng túi gói 'Vận chuyển đi từ'",
            302  => "Đóng chuyến thư 'Vận chuyển đi từ'",
            303  => "Đóng tuyến xe 'Vận chuyển đi từ'",
            400  => "Nhận bảng kê đến 'Nhận tại'",
            401  => "Nhận Túi gói 'Nhận tại'",
            402  => "Nhận chuyến thư 'Nhận tại'",
            403  => "Nhận chuyến xe 'Nhận tại'",
            500  => "Giao bưu tá đi phát",
            501  => "Thành công - Phát thành công",
            502  => "Chuyển hoàn bưu cục gốc",
            503  => "Hủy - Theo yêu cầu khách hàng",
            504  => "Thành công - Chuyển trả người gửi",
            505  => "Tồn - Thông báo chuyển hoàn bưu cục gốc",
            506  => "Tồn - Khách hàng nghỉ, không có nhà",
            507  => "Tồn - Khách hàng đến bưu cục nhận",
            508  => "Phát tiếp",
            509  => "Chuyển tiếp bưu cục khác",
            510  => "Hủy phân công phát",
            515  => "Bưu cục phát duyệt hoàn",
            550  => "Đơn Vị Yêu Cầu Phát Tiếp"
        ];
    public static $weight_converts = ['GRAM' => 1, 'KG' => 1000];

    /**
     * Viettel Post constructor.
     */
    public function __construct()
    {

    }

    public static function getApiToken()
    {
        try {
            $param    = [
                'USERNAME' => env("VTP_USERNAME"),
                'PASSWORD' => env("VTP_PASSWORD"),
            ];
            $response = Http::timeout(3)->withHeaders(['Content-Type' => 'application/json'])->post(env("VTP_END_POINT") . "/user/Login", $param);
            $response = $response->body() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];
        } catch (ConnectionException $exception) {
            return ['status' => 'error', 'success' => false, 'message' => $exception->getMessage()];
        }
        return $response['data']['token'] ?? [];
    }

    public static function getProvince()
    {
        $client   = new Client();
        $response = $client->get(env("VTP_END_POINT") . "/categories/listProvinceById?provinceId=", [
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $response = $response->getBody()->getContents() ?? null;
        return $response;
    }

    public static function getDistrict($provinceID)
    {
        $client   = new Client();
        $response = $client->get(env("VTP_END_POINT") . "/categories/listDistrict?provinceId=" . $provinceID, [
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $response = $response->getBody()->getContents() ?? null;
        return $response;
    }

    public static function getWard($districtID)
    {
        $client   = new Client();
        $response = $client->get(env("VTP_END_POINT") . "/categories/listWards?districtId=" . $districtID, [
            'headers' => ['Content-Type' => 'application/json'],
        ]);
        $response = $response->getBody()->getContents() ?? null;
        return $response;
    }

    public static function getShipFee(Request $request, $token, $store)
    {
        $input = $request->all();
//        $weight = 0;
        $price = 0;
        try {
            if (!empty($input['cart_id'])) {
                $cart = Cart::with('details')->where('id', $input['cart_id'])->first();
                if (empty($cart)) {
                    throw new \Exception(Message::get("V003", $input['cart_id']));
                }
                $weight = (float)$cart->total_weight * 1000;
               if(!empty($input['type'])){
                   if((int)$price > 3000000 && $cart->payment_method == PAYMENT_METHOD_CASH){
                       $total = [
                           'service_type_id' => $cart->shipping_service,
                           'MONEY_TOTAL'     => round((int)$cart->ship_fee+((0.5/100)*((int)$price - 3000000)))
                       ];
                   }
                   else{
                       $total = [
                           'service_type_id' => $cart->shipping_service,
                           'MONEY_TOTAL'     => (int)$cart->ship_fee
                       ];
                   }
                   return $total;
               }
//               if ($cart->free_item && $cart->free_item != "[]") {
//                   foreach ($cart->free_item as $item) {
//                       foreach ($item['text'] as $value) {
//                           $weight += $value['weight'] * ($value['qty_gift'] ?? 1) * self::$weight_converts[($value['weight_class'])];
//                       }
//                   }
//               }
               $weight1 = $cart->details->map(function ($detail) {
                   $weight_converts = ['GRAM' => 1, 'KG' => 1000];
                   return [
                       'id'     => $detail->product_id,
                       'weigth' => $detail['quantity'] * $detail->product->weight * $weight_converts[($detail->product->weight_class)],
                   ];
               });
               foreach ($weight1 as $key) {
                   $product             = Product::model()->where('id', $key['id'])->first();
                   if($product->is_cool == 1){
                       return [];
                   }
                //    if ($product['gift_item'] && $product['gift_item'] != "[]") {
                //        foreach (json_decode($product['gift_item']) as $value) {
                //            if (!empty($value->weight)) {
                //                $weight += $value->weight * self::$weight_converts[$value->weight_class];
                //            }
                //        }
                //    }
//                   $weight += $key['weigth'];
               }

                $price = $cart->total_info[array_search('total', array_column($cart->total_info, 'code'))]['value'];

                if (!empty($cart->distributor_code)) {
                    if ($cart->getUserDistributor->is_transport != 1) {
                        return [];
                    }
                    // dd($cart->id)
                    if(!empty($cart->getUserDistributor->type_delivery_hub) && $cart->getUserDistributor->type_delivery_hub != SHIPPING_PARTNER_TYPE_VTP){
                        return [];
                    }
                    $senderProvice     = $cart->getCityDistributor->name;
                    $senderCity        = $cart->getCityDistributor->code;
                    $senderDistrict    = $cart->distributor_district_name;
                    $senderWard        = $cart->getWardDistributor->type . " " . $cart->getWardDistributor->name;
                    $fromWard          = $cart->getWardDistributor->id_ward_vtp;
                    $checkRegionSender = $cart->getCityDistributor->description;
                }
                // if(empty($cart->customer_city_code)){
                //     throw new \Exception(Message::get("V003", 'customer'));
                // }
                $receiverCityArea    = $cart->getCity->code;
                $receiverProvince    = $cart->getCity->name;
                $receiverDistrict    = $cart->getDistrict->full_name;
                $receiverWard        = $cart->getWard->full_name;
                $toWard              = $cart->getWard->id_ward_vtp;
                $isInterDistrict     = $cart->getWard->is_inter_district;
                $checkRegionReceiver = $cart->getCity->description;

                if (empty($cart->distributor_code)) {
                    $store         = Store::find($store);
                    $senderCity    = $store->city_code;
                    $senderProvice = $store->city_name;
//                    $senderDistrict = $store->district_type." ".$store->district_name;
                    $fromWard            = $store->ward->id_ward_vtp;
                    $checkRegionReceiver = $store->city->description;
                }
            }

//            if(!empty($input['order_id'])){
//                $order = Order::with("details.product")->where('id',$input['order_id'])->first();
//                if (empty($order)) {
//                    throw new \Exception(Message::get("V003", $input['order_id']));
//                }
//                $weight_converts = ['GRAM' => 0.001, 'KG' => 1];
//                foreach ($order->details as $input_detail) {
//                    $weight  += $input_detail['qty'] * $input_detail->product['weight'] * $weight_converts[$input_detail->product['weight_class']];
//                }
//                $price = $order->total_price;
//                if (!empty($order->ship_fee) && !empty($order->total_price) && $order->ship_fee != 0) {
//                    $price = $order->total_price - (int)$order->ship_fee;
//                }
//                if(!empty($order->distributor_code)) {
//                    $order = Order::with("details.product","addressDistributor.getCity")->where('id',$input['order_id'])->first();
//                    $senderProvice  = $order->addressDistributor->getCity->name;
//                    $senderDistrict = $order->addressDistributor->district_full_name;
//                }
//
//                if(empty($order->distributor_code)){
//                    $store = Store::find(TM::getCurrentStoreId());
//                    $senderProvice = $store->city_name;
//                    $senderDistrict = $store->district_type." ".$store->district_name;
//                }
//                if(!empty($input['distributor_code'])){
//                    $distributor = Distributor::model()->where('code',$input['distributor_code'])->first();
//                    $senderProvice = $distributor->getCity->name;
//                    $senderDistrict = $distributor->district_full_name;
//                    $services = self::getSerVice($request,$token,$store,$input['order_id']);
//                    $order->update(['shipping_service' => $services[0]['service_type_id']]);
//                }
//                $receiverProvince = $order->shipping_address_city_name;
//                $receiverDistrict = $order->shipping_address_district_name;
//            }

            // Get province
//            $getProvinces = self::getProvince();
//            $provinces = json_decode($getProvinces)->data;
//            $fromProvinceID = $provinces[array_search($senderProvice,array_column($provinces, 'PROVINCE_NAME'))]->PROVINCE_ID;
//            $toProvinceID = $provinces[array_search($receiverProvince,array_column($provinces, 'PROVINCE_NAME'))]->PROVINCE_ID;
//            // Get district
//            $getDistricts = self::getDistrict($fromProvinceID);
//            $getFromDistricts = json_decode($getDistricts)->data;
//            $fromDistrict = $getFromDistricts[array_search(mb_strtoupper($senderDistrict,"UTF-8"),array_column($getFromDistricts, 'DISTRICT_NAME'))]->DISTRICT_ID;
//            $getDistrictsTo = self::getDistrict($toProvinceID);
//            $getToDistricts = json_decode($getDistrictsTo)->data;
//            $toDistrict = $getToDistricts[array_search(mb_strtoupper($receiverDistrict,"UTF-8"),array_column($getToDistricts, 'DISTRICT_NAME'))]->DISTRICT_ID;
//            $getWards = self::getWard($fromDistrict);
//            $getFromWards = json_decode($getWards)->data;
//            $fromWard = $getFromWards[array_search(mb_strtoupper($senderWard,"UTF-8"),array_column($getFromWards, 'WARDS_NAME'))]->WARDS_ID;
//            $getWardsTo = self::getWard($toDistrict);
//            $getToWards = json_decode($getWardsTo)->data;
//            $toWard = $getToWards[array_search(mb_strtoupper($receiverWard,"UTF-8"),array_column($getToWards, 'WARDS_NAME'))]->WARDS_ID;
            if (empty($toWard) || empty($fromWard) || $weight > 30000) {
                return [];
            }
            $region = "Cận vùng";
            $time1  = "1 - 2 ngày";
            $time2  = "2 - 4 ngày";
            if (($checkRegionSender == "MN" && $checkRegionReceiver == "MB") || ($checkRegionSender == "MB" && $checkRegionReceiver == "MN")) {
                $region = "Liên vùng";
                $time1  = "1 - 2 ngày";
                $time2  = "2 - 5 ngày";
            }
            if ($checkRegionSender == $checkRegionReceiver) {
                $region = "Nội vùng";
                $time1  = $time2 = "1 - 2 ngày";
            }
            if ($receiverCityArea == $senderCity) {
                $region    = "Nội Tỉnh";
                $time_ems  = "24h";
                $time_ecod = "24h";
            }
            $result_NCOD = ShipFeeVNP::where([
                'type_address' => SHIPPING_PARTNER_TYPE_VTP,
                'type_ship'    => $region,
                'type'         => "NCOD"
            ])->first();
            $result_LCOD = ShipFeeVNP::where([
                'type_address' => SHIPPING_PARTNER_TYPE_VTP,
                'type_ship'    => $region,
                'type'         => "LCOD"
            ])->first();
            if ($weight <= 2000) {
                $price_NCOD = $result_NCOD->price_ship_zero_two;
                $price_LCOD = $result_LCOD->price_ship_zero_two;
            }

            if ($weight >= 2000 && $weight <= 5000) {
                $price_NCOD = $result_NCOD->price_ship_two_five;
                $price_LCOD = $result_LCOD->price_ship_two_five;
            }
            if ($weight >= 5000 && $weight <= 10000) {

                $price_NCOD = $result_NCOD->price_ship_five_ten;
                $price_LCOD = $result_LCOD->price_ship_five_ten;
            }
            if ($weight > 10000) {
                $priceTotal_NCOD = ($weight - 10000) / 1000 * $result_NCOD->price_ship_over_ten;
                $priceTotal_LCOD = ($weight - 10000) / 1000 * $result_LCOD->price_ship_over_ten;
                $price_NCOD      = $result_NCOD->price_ship_five_ten + $priceTotal_NCOD;
                $price_LCOD      = $result_LCOD->price_ship_five_ten + $priceTotal_LCOD;
            }
//            if ($senderProvice == 'Hồ Chí Minh' && $receiverProvince == 'Hồ Chí Minh') {
            if ($receiverCityArea == $senderCity) {
                $data[] = [
                    'service_type_id' => 'PHS',
                    'short_name'      => 'TMĐT Phát hôm sau',
                    'MONEY_TOTAL'     => $isInterDistrict == 1 ? (float)$price_LCOD + 7000 : $price_LCOD,
                    'time'            => '24 giờ'
                ];
                return $data;
            }
            $data[] = [
                'service_type_id' => 'NCOD',
                'short_name'      => '',
                'MONEY_TOTAL'     => $isInterDistrict == 1 ? (float)$price_NCOD + 7000 : $price_NCOD,
                'time'            => $time1
            ];
            $data[] = [
                'service_type_id' => 'LCOD',
                'short_name'      => '',
                'MONEY_TOTAL'     => $isInterDistrict == 1 ? (float)$price_LCOD + 7000 : $price_LCOD,
                'time'            => $time2
            ];


//            $param = [
//                    "PRODUCT_WEIGHT"        => $weight,
//                    "PRODUCT_PRICE"         => $price,
//                    "MONEY_COLLECTION"      => $price,
//                    "ORDER_SERVICE_ADD"     => "",
////                    "ORDER_SERVICE"         => !empty($service) ? $service : $order->shipping_service,
//                    "ORDER_SERVICE"         => !empty($service) ? $service : $order->shipping_service,
//                    "SENDER_PROVINCE"       => $fromProvinceID,
//                    "SENDER_DISTRICT"       => $fromDistrict,
//                    "RECEIVER_PROVINCE"     => $toProvinceID,
//                    "RECEIVER_DISTRICT"     => $toDistrict,
//                    "PRODUCT_TYPE"          => "HH",
//                    "NATIONAL_TYPE"         => 1
//            ];
//            $client = new Client();
//            $response = $client->post(env("VTP_END_POINT") . "/order/getPrice", [
//                'headers' => ['Content-Type' => 'application/json', 'Token' => $token],
//                'body'    => json_encode($param),
//            ]);
//            $response = $response->getBody()->getContents() ?? null;
//            $response = !empty($response) ? json_decode($response, true) : [];
        } catch (\RequestException  $exception) {
            return $exception->getRequest() . "\n";
        }
        return $data;
    }

    public static final function sendOrder(Order $order, $token,$check = null)
    {
        $price    = 0;
        $weight   = 0;
        $group_id = 0;
        try {
            if (empty($order) || empty($order->id)) {
                throw new \Exception(Message::get("V001", Message::get("orders")));
            }
            $order = Order::model()->with(['details.product.warehouse', 'store', 'customer.profile'])->where('id',
                $order->id)->first();
//            if ($order->free_item && $order->free_item != "[]") {
//                foreach (json_decode($order->free_item) as $item) {
//                    foreach ($item->text as $value) {
//                        $weight += $value->weight * ($value->qty_gift ?? 1) * self::$weight_converts[($value->weight_class)];
//                    }
//                }
//            }

            if (!empty($order->distributor_code) && !empty($order->distributor_phone)) {
                $distributor         = Distributor::model()->where('code', $order->distributor_code)->first();
                $distributor_address = $distributor->users->profile->address;
                $group_id            = self::CheckPhoneDistributor($distributor->users->phone, $token);
            }
            if (!empty($order->distributor_code)) {
                $order          = Order::model()->with(['details.product.warehouse', 'addressDistributor.getCity', 'store', 'customer.profile'])->where('id', $order->id)->first();
                $fromProvinceID = $order->distributor->profile->city->id_city_vtp;
                $fromDistrict   = $order->distributor->profile->district->id_district_vtp;
                $fromWard       = $order->distributor->profile->ward->id_ward_vtp;
//                $from1 = $order->distributor->profile->city->name;
//                $from2 = $order->distributor->profile->district->full_name;
//                $from3 = $order->distributor->profile->ward->full_name;
            }
            if ($group_id == 0) {
                $store          = Store::find(TM::getCurrentStoreId());
                $fromProvinceID = $store->city->id_city_vtp;
                $fromDistrict   = $store->district->id_district_vtp;
                $fromWard       = $store->ward->id_ward_vtp;
//                $from1 = $store->city_name;
//                $from2 = $store->district_type." ".$store->district_name;
//                $from3 = $store->ward_type." ".$store->ward_name;
//                $dv = self::getSerVice(null,$token,$store->id,$order->id);
//                $key = array_search(min(array_column($dv, 'fee_service')),array_column($dv, 'fee_service'));
//                $order->update(['shipping_service' => $dv[$key]['service_type_id']]);
            }
            $cod_amount = $order->total_price;
//            if (!empty($order->ship_fee) && !empty($order->total_price) && $order->ship_fee != 0) {
//                $cod_amount = $order->total_price - (int)$order->ship_fee;
                $cod_amount = $order->total_price;
//            }
            // Get province
            $toProvinceID = $order->getCity->id_city_vtp;
            $toDistrict   = $order->getDistrict->id_district_vtp;
            $toWard       = $order->getWard->id_ward_vtp;

            if (empty($order)) {
                throw new \Exception(Message::get("V003", Message::get("orders")));
            }
            $weight_converts = ['KG' => 1000, 'GRAM' => 1];
            $order_details   = [];
            $warehouse       = [];
            foreach ($order->details as $key => $detail) {
                $order_details[$detail->id] = $detail->toArray();
                array_push($warehouse, $detail->product);
                $warehouse[$key]['order_detail_id'] = $detail->id;
                $warehouse[$key]['warehouse_id']    = $detail->product->warehouse->warehouse_id;
                $warehouse[$key]['batch_id']        = $detail->product->warehouse->batch_id;
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
//                if ($item['gift_item'] && $item['gift_item'] != "[]") {
//                    foreach (json_decode($item['gift_item']) as $value) {
//                        if (!empty($value->weight)) {
//                            $weight += $value['weight'] * self::$weight_converts[($value['weight_class'])];
//                        }
//                    }
//                }
                $products[]            = [
                    "PRODUCT_NAME"     => $item['name'] ?? $order_detail['product_name'],
                    "PRODUCT_PRICE"    => $item['price'] ?? $order_detail['price'],
                    "PRODUCT_WEIGHT"   => $order_detail['qty'] * $item['weight'] * $weight_converts[$item['weight_class']],
                    "PRODUCT_QUANTITY" => $order_detail['qty'],

                ];
                $price                 += $item['price'] ?? $order_detail['price'];
//                $weight                += $order_detail['qty'] * $item['weight'] * $weight_converts[$item['weight_class']];
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
                "{$order->code}-" . SHIPPING_PARTNER_TYPE_VTP . "-%")->orderBy('id', 'desc')->first();

            $codeIndex = 0;
            if (!empty($lastShip)) {
                $codeIndex = explode("-" . SHIPPING_PARTNER_TYPE_VTP . "-", $lastShip->ship_code);
                $codeIndex = (int)($codeIndex[1] ?? 0);
            }
            $shipCode = $order->code . "-" . SHIPPING_PARTNER_TYPE_VTP . "-" . (str_pad(++$codeIndex, 2, '0',
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
                "ORDER_NUMBER"        => $shipCode,
                "DELIVERY_DATE"       => $date,
                "GROUPADDRESS_ID"     => $group_id != 0 ? $group_id : "9227056",
                "CUS_ID"              => !empty($order->distributor_id) ? $order->distributor_id : $store->id,
                "SENDER_FULLNAME"     => !empty($order->distributor_code) ? $order->addressDistributor->name : $store->name,
                "SENDER_ADDRESS"      => !empty($order->distributor_code) && !empty($distributor_address) ? $distributor_address : $store->address,
                "SENDER_PHONE"        => !empty($order->distributor_code) ? $order->distributor_phone : $store->contact_phone,
                "SENDER_EMAIL"        => !empty($order->distributor_code) ? $order->distributor_email : $store->email,
                "SENDER_WARD"         => $fromWard,
                "SENDER_DISTRICT"     => $fromDistrict,
                "SENDER_PROVINCE"     => $fromProvinceID,
                "RECEIVER_FULLNAME"   => $order->shipping_address_full_name ?? $order->customer_name,
                "RECEIVER_ADDRESS"    => $order->shipping_address,
                "RECEIVER_PHONE"      => $order->shipping_address_phone,
                "RECEIVER_EMAIL"      => $order->customer_email,
                "RECEIVER_WARD"       => $toWard,
                "RECEIVER_DISTRICT"   => $toDistrict,
                "RECEIVER_PROVINCE"   => $toProvinceID,
                "PRODUCT_NAME"        => "ĐƠN HÀNG CỦA " . $order->customer_name,
                "PRODUCT_DESCRIPTION" => $order->note ?? "",
                "PRODUCT_QUANTITY"    => count($products),
                "PRODUCT_PRICE"       => $cod_amount,
                "PRODUCT_WEIGHT"      => ($order->total_weight)*1000,
                "PRODUCT_TYPE"        => "HH",
                "ORDER_PAYMENT"       => 3,
                "ORDER_SERVICE"       => $order->shipping_service, // NCOD LCOD
                "ORDER_SERVICE_ADD"   => $order->order_service_add,
                "ORDER_NOTE"          => $order->shipping_note,
                "MONEY_COLLECTION"    => $order_payment == 2 || $order_payment == 3 ? $cod_amount : 0,
                "MONEY_TOTALFEE"      => 0,
                //                "MONEY_TOTALFEE"                => $order_payment == 2 || $order_payment == 4 && $order->ship_fee > 0 ? $order->ship_fee : 0,
                "MONEY_FEECOD"        => $input['money_feecod'] ?? 0,
                "MONEY_FEEVAS"        => $input['money_feevas'] ?? 0,
                "MONEY_FEEINSURRANCE" => $input['money_feeinsurrance'] ?? 0,
                "MONEY_FEE"           => $order->ship_fee ?? 0,
                "MONEY_FEEOTHER"      => $input['money_feeother'] ?? 0,
                "MONEY_TOTALVAT"      => $input['money_totalvat'] ?? 0,
                "MONEY_TOTAL"         => $order->total_price ?? 0,
                "LIST_ITEM"           => $products,
            ];  
            // dd($param, $token)
            $client   = new Client();
            $response = $client->post(env("VTP_END_POINT") . "/order/createOrder", [
                'headers' => ['Content-Type' => 'application/json', 'Token' => $token],
                'body'    => json_encode($param),
            ]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];
            if ($response['status'] == 200) {
                // Update Order
                $order->shipping_info_code = $response['data']['ORDER_NUMBER'] ?? null;
                $order->shipping_info_json = !empty($response['data']) ? json_encode($response['data']) : null;
                $order->status             = OrderStatus::SHIPPING;
                $order->status_text        = "Sẵn sàng giao hàng";
                $order->save();
                if($check != 1){
                    OrderHistory::insert([
                        'order_id'   => $order->id,
                        'status'     => OrderStatus::SHIPPING,
                        'created_at' => date("Y-m-d H:i:s", time()),
                        'created_by' => TM::getCurrentUserId(),
                    ]);
                    VNP::updateOrderStatusHistory($order);
                }
                // Create Shipping Order
                $shippingOrder                = new ShippingOrder();
                $shippingOrder->type          = "VTP";
                $shippingOrder->code          = $order->code;
                $shippingOrder->name          = $order->code;
                $shippingOrder->code_type_ghn = $response['data']['ORDER_NUMBER'] ?? null;
                // $shippingOrder->partner_id = $response['partner_id'];
                $shippingOrder->status      = 'SHIPPING';
                $shippingOrder->status_text = 'Đang giao hàng';
                $shippingOrder->description = $order->shipping_note;
                // $shippingOrder->ship_fee = $response['fee'];
                $shippingOrder->estimated_pick_time    = date('Y-m-d', strtotime($now . ' + 3 days'));
                $shippingOrder->estimated_deliver_time = date('Y-m-d', strtotime($now . ' + 3 days'));
                $shippingOrder->order_id               = $order->id;
                $shippingOrder->ship_code              = $shipCode;
                $shippingOrder->count_print            = 0;
                $shippingOrder->company_id             = TM::getCurrentCompanyId();
                $shippingOrder->result_json            = json_encode($response['data']) ?? null;
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
            if( $response['status'] == 204){
               
                $param    = [
                    "ORDER_NUMBER"        => $shipCode,
                    "DELIVERY_DATE"       => $date,
                    "GROUPADDRESS_ID"     => $group_id != 0 ? $group_id : "9227056",
                    "CUS_ID"              => !empty($order->distributor_id) ? $order->distributor_id : $store->id,
                    "SENDER_FULLNAME"     => !empty($order->distributor_code) ? $order->addressDistributor->name : $store->name,
                    "SENDER_ADDRESS"      => !empty($order->distributor_code) && !empty($distributor_address) ? $distributor_address : $store->address,
                    "SENDER_PHONE"        => !empty($order->distributor_code) ? $order->distributor_phone : $store->contact_phone,
                    "SENDER_EMAIL"        => !empty($order->distributor_code) ? $order->distributor_email : $store->email,
                    "SENDER_WARD"         => $fromWard,
                    "SENDER_DISTRICT"     => $fromDistrict,
                    "SENDER_PROVINCE"     => $fromProvinceID,
                    "RECEIVER_FULLNAME"   => $order->shipping_address_full_name ?? $order->customer_name,
                    "RECEIVER_ADDRESS"    => $order->shipping_address,
                    "RECEIVER_PHONE"      => $order->shipping_address_phone,
                    "RECEIVER_EMAIL"      => $order->customer_email,
                    "RECEIVER_WARD"       => $toWard,
                    "RECEIVER_DISTRICT"   => $toDistrict,
                    "RECEIVER_PROVINCE"   => $toProvinceID,
                    "PRODUCT_NAME"        => "ĐƠN HÀNG CỦA " . $order->customer_name,
                    "PRODUCT_DESCRIPTION" => $order->note ?? "",
                    "PRODUCT_QUANTITY"    => count($products),
                    "PRODUCT_PRICE"       => $cod_amount,
                    "PRODUCT_WEIGHT"      => ($order->total_weight)*1000,
                    "PRODUCT_TYPE"        => "HH",
                    "ORDER_PAYMENT"       => 3,
                    "ORDER_SERVICE"       => 'LCOD', // NCOD LCOD
                    "ORDER_SERVICE_ADD"   => $order->order_service_add,
                    "ORDER_NOTE"          => $order->shipping_note,
                    "MONEY_COLLECTION"    => $order_payment == 2 || $order_payment == 3 ? $cod_amount : 0,
                    "MONEY_TOTALFEE"      => 0,
                    //                "MONEY_TOTALFEE"                => $order_payment == 2 || $order_payment == 4 && $order->ship_fee > 0 ? $order->ship_fee : 0,
                    "MONEY_FEECOD"        => $input['money_feecod'] ?? 0,
                    "MONEY_FEEVAS"        => $input['money_feevas'] ?? 0,
                    "MONEY_FEEINSURRANCE" => $input['money_feeinsurrance'] ?? 0,
                    "MONEY_FEE"           => $order->ship_fee ?? 0,
                    "MONEY_FEEOTHER"      => $input['money_feeother'] ?? 0,
                    "MONEY_TOTALVAT"      => $input['money_totalvat'] ?? 0,
                    "MONEY_TOTAL"         => $order->total_price ?? 0,
                    "LIST_ITEM"           => $products,
                ];  
                // dd($param, $token);
                $client   = new Client();
                $response = $client->post(env("VTP_END_POINT") . "/order/createOrder", [
                    'headers' => ['Content-Type' => 'application/json', 'Token' => $token],
                    'body'    => json_encode($param),
                ]);
                $response = $response->getBody()->getContents() ?? null;
                $response = !empty($response) ? json_decode($response, true) : [];
                if ($response['status'] == 200) {
                    // Update Order
                    $order->shipping_info_code = $response['data']['ORDER_NUMBER'] ?? null;
                    $order->shipping_info_json = !empty($response['data']) ? json_encode($response['data']) : null;
                    $order->status             = OrderStatus::SHIPPING;
                    $order->status_text        = "Sẵn sàng giao hàng";
                    $order->save();
                    if($check != 1){
                        OrderHistory::insert([
                            'order_id'   => $order->id,
                            'status'     => OrderStatus::SHIPPING,
                            'created_at' => date("Y-m-d H:i:s", time()),
                            'created_by' => TM::getCurrentUserId(),
                        ]);
                        VNP::updateOrderStatusHistory($order);
                    }
                    // Create Shipping Order
                    $shippingOrder                = new ShippingOrder();
                    $shippingOrder->type          = "VTP";
                    $shippingOrder->code          = $order->code;
                    $shippingOrder->name          = $order->code;
                    $shippingOrder->code_type_ghn = $response['data']['ORDER_NUMBER'] ?? null;
                    // $shippingOrder->partner_id = $response['partner_id'];
                    $shippingOrder->status      = 'SHIPPING';
                    $shippingOrder->status_text = 'Đang giao hàng';
                    $shippingOrder->description = $order->shipping_note;
                    // $shippingOrder->ship_fee = $response['fee'];
                    $shippingOrder->estimated_pick_time    = date('Y-m-d', strtotime($now . ' + 3 days'));
                    $shippingOrder->estimated_deliver_time = date('Y-m-d', strtotime($now . ' + 3 days'));
                    $shippingOrder->order_id               = $order->id;
                    $shippingOrder->ship_code              = $shipCode;
                    $shippingOrder->count_print            = 0;
                    $shippingOrder->company_id             = TM::getCurrentCompanyId();
                    $shippingOrder->result_json            = json_encode($response['data']) ?? null;
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
            }
            return ['status' => 'success', 'success' => true, 'shipping_orders' => $shippingOrder, 'warehouse' => $warehouse];

        } catch (\Exception $exception) {
            throw new \Exception("Không tạo được lệnh giao hàng đơn hàng #" . $order->code);
        }
     
    }

    public static function updateOrder($shipping_code, $request, $token)
    {
        $input = $request->all();
        try {
            $param = [
                "TYPE"         => $input['status_type'],
                "ORDER_NUMBER" => $shipping_code,
                "NOTE"         => $input['note']
            ];

            $client   = new Client();
            $response = $client->post(env("VTP_END_POINT") . "/order/UpdateOrder", [
                'headers' => ['Content-Type' => 'application/json', 'Token' => $token],
                'body'    => json_encode($param),
            ]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];

        } catch (\RequestException  $exception) {
            return $exception->getRequest() . "\n";
        }

        return $response;
    }

//    public static function getSerVice($request,$token,$store,$orderId)
//    {
//        $input = !empty($request) ? $request->all() : null;
//        $weight = 0;
//        $price = 0;
//        try {
//
//            if(!empty($input['cart_id'])){
//                if (empty($input['cart_id'])) {
//                    throw new \Exception(Message::get("V001", 'cart_id'));
//                }
//
//                $cart  = Cart::with('details')->where('id',$input['cart_id'])->first();
//                if (empty($cart)) {
//                    throw new \Exception(Message::get("V003", $input['cart_id']));
//                }
//                $weight1 = $cart->details->map(function ($detail) {
//                    $weight_converts = ['GRAM' => 1, 'KG' => 1000];
//                    return [
//                        'weigth' => $detail['quantity'] * $detail->product->weight * $weight_converts[($detail->product->weight_class)],
//                    ];
//                });
//                foreach ($weight1 as $key) {
//                    $weight += $key['weigth'];
//                }
//                $price = $cart->total_info[array_search('total',array_column($cart->total_info, 'code'))]['value'];
//                $senderProvice = $cart->getCityDistributor->name;
//                $senderDistrict = $cart->getDistrictDistributor->full_name;
//                $senderArea = $cart->getCityDistributor->description;
//                // if(empty($cart->customer_city_code)){
//                //     throw new \Exception(Message::get("V003", 'customer'));
//                // }
//                $receiverProvince = $cart->getCity->name;
//                $receiverDistrict = $cart->getDistrict->full_name;
//                $receiverArea = $cart->getCity->description;
//
//                if(empty($cart->distributor_code)){
//                    $store = Store::find($store);
//                    $senderProvice = $store->city_name;
//                    $senderDistrict = $store->district_type." ".$store->district_name;
//                    $senderArea = $store->city->description;
//                }
//            }
//
//            if(!empty($orderId)){
//                $order = Order::with("details.product")->where('id',$orderId)->first();
//                if (empty($order)) {
//                    throw new \Exception(Message::get("V003", $orderId));
//                }
//                $weight_converts = ['GRAM' => 0.001, 'KG' => 1];
//                foreach ($order->details as $input_detail) {
//                    $weight  += $input_detail['qty'] * $input_detail->product['weight'] * $weight_converts[$input_detail->product['weight_class']];
//                }
//                $price = $order->total_price;
//                if (!empty($order->ship_fee) && !empty($order->total_price) && $order->ship_fee != 0) {
//                    $price = $order->total_price - (int)$order->ship_fee;
//                }
//                if(!empty($order->distributor_code)) {
//                    $order = Order::with("details.product","addressDistributor.getCity")->where('id',$orderId)->first();
//                    $senderProvice  = $order->addressDistributor->getCity->name;
//                    $senderDistrict = $order->addressDistributor->district_full_name;
//
//                }
//                if(empty($order->distributor_code)){
//                    $store = Store::find(TM::getCurrentStoreId());
//                    $senderProvice = $store->city_name;
//                    $senderDistrict = $store->district_type." ".$store->district_name;
//                }
//                $receiverProvince = $order->shipping_address_city_name;
//                $receiverDistrict = $order->shipping_address_district_name;
//            }
//
//            // Get province
//            $getProvinces = self::getProvince();
//            $provinces = json_decode($getProvinces)->data;
//            $fromProvinceID = $provinces[array_search($senderProvice,array_column($provinces, 'PROVINCE_NAME'))]->PROVINCE_ID;
//            $toProvinceID = $provinces[array_search($receiverProvince,array_column($provinces, 'PROVINCE_NAME'))]->PROVINCE_ID;
//            // Get district
//            $getDistricts = self::getDistrict($fromProvinceID);
//            $getFromDistricts = json_decode($getDistricts)->data;
//            $fromDistrict = $getFromDistricts[array_search(mb_strtoupper($senderDistrict,"UTF-8"),array_column($getFromDistricts, 'DISTRICT_NAME'))]->DISTRICT_ID;
//            $getDistrictsTo = self::getDistrict($toProvinceID);
//            $getToDistricts = json_decode($getDistrictsTo)->data;
//            $toDistrict = $getToDistricts[array_search(mb_strtoupper($receiverDistrict,"UTF-8"),array_column($getToDistricts, 'DISTRICT_NAME'))]->DISTRICT_ID;
//            $region = "Cận vùng";
//            $time1 = "1 - 2 ngày";
//            $time2 = "2 - 4 ngày";
//            if(!empty($senderArea)){
//            if(($senderArea == "MN" && $receiverArea == "MB") || ($senderArea == "MB" && $receiverArea == "MN")){
//                $region = "Liên vùng";
//                $time1 = "1 - 2 ngày";
//                $time2 = "2 - 5 ngày";
//            }
//            if($senderArea == $receiverArea){
//                $region = "Nội vùng";
//                $time1 = $time2 = "1 - 2 ngày";
//            }
//            }
////            $param = [
////                    "PRODUCT_WEIGHT"        => $weight,
////                    "PRODUCT_PRICE"         => $price,
////                    "MONEY_COLLECTION"      => $price,
////                    "SENDER_PROVINCE"       => $fromProvinceID,
////                    "SENDER_DISTRICT"       => $fromDistrict,
////                    "RECEIVER_PROVINCE"     => $toProvinceID,
////                    "RECEIVER_DISTRICT"     => $toDistrict,
////                    "PRODUCT_TYPE"          => "HH",
////                    "TYPE"                  => 1
////            ];
////            dd($senderProvice,$receiverProvince);
//            if($senderProvice == 'Hồ Chí Minh' && $receiverProvince == 'Hồ Chí Minh'){
//                $data[] = [
//                    'service_type_id' => 'PHS',
//                    'short_name' => 'TMĐT Phát hôm sau',
//                    'fee_service'=> 26500,
//                    'time'=> '24 giờ'
//                ];
//            }else{
//                $client = new Client();
//                $response = $client->post(env("VTP_END_POINT") . "/order/getPriceAll", [
//                    'headers' => ['Content-Type' => 'application/json', 'Token' => $token],
//                    'body'    => json_encode($param),
//                ]);
//                $response = $response->getBody()->getContents() ?? null;
//                $response = !empty($response) ? json_decode($response, true) : [];
//                if(!empty($response['status'])){
//                    if($response['status'] != 200){
//                        return [];
//                    }
//                }
//                $data = [];
//
//                foreach ($response as $value) {
//                    $data[] = [
//                        'service_type_id' => $value['MA_DV_CHINH'],
//                        'short_name' => $value['TEN_DICHVU'],
//                        'fee_service'=> $value['GIA_CUOC'],
//                        'time'=> $value['MA_DV_CHINH'] == 'NCOD' ? $time1 : $time2,
//                    ];
//                }
//            }
//
//        } catch (\RequestException  $exception) {
//            return $exception->getRequest() . "\n";
//        }
//
//        return $data;
//
//    }

    static function CheckPhoneDistributor($phone, $token)
    {
        $client   = new Client();
        $response = $client->get(env("VTP_END_POINT") . "/user/listInventory", [
            'headers' => ['Content-Type' => 'application/json', 'Token' => $token],
        ]);
        $response = $response->getBody()->getContents() ?? null;
        $response = !empty($response) ? json_decode($response, true) : [];
        $num      = 0;
        $arr      = (array)$response['data'];
        foreach ($arr as $key) {
            if ($key['phone'] == $phone) {
                return $num = $key['groupaddressId'];
            }
        }
        return $num;
    }
}
