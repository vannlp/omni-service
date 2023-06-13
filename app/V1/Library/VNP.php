<?php

namespace App\V1\Library;


use App\Batch;
use App\Cart;
use App\City;
use App\Distributor;
use App\District;
use App\Order;
use App\OrderDetail;
use App\OrderHistory;
use App\OrderStatus;
use App\OrderStatusHistory;
use App\ShippingOrder;
use App\ShippingOrderDetail;
use App\Store;
use App\Supports\Message;
use App\TM;
use App\Unit;
use App\V1\Models\OrderModel;
use App\Ward;
use App\Warehouse;
use App\WarehouseDetail;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Product;
use App\ShipFeeVNP;

class VNP
{
    const STATUS
                = [
            10  => "Đã xóa",
            20  => "Gửi sang hệ thống MyVNPOST thành công",
            60  => "Hủy",
            61  => "Báo hủy",
            62  => "Đã nhận báo hủy",
            70  => "Bưu cục đã nhận Đơn hàng và nhập vào hệ thống chuyển phát của VNPost",
            91  => "Đã đi phát hàng cho người nhận nhưng không thành công",
            100 => "Hàng đã phát thành công cho người nhận",
            110 => "Bưu tá đã nhận tiền COD của người nhận và nhập vào hệ thống",
            120 => "Đã trả tiền cho người gửi",
            161 => "Phát lại cho người gửi thất bại",
            170 => "Phát lại cho người gửi thành công"

        ];
    const WARD
                = [
            "Số 1"  => "1",
            "Số 2"  => "2",
            "Số 3"  => "3",
            "Số 4"  => "4",
            "Số 5"  => "5",
            "Số 6"  => "6",
            "Số 7"  => "7",
            "Số 8"  => "8",
            "Số 9"  => "9",
            "Số 10" => "10",
            "Số 11" => "11",
            "Số 12" => "12",
            "Số 13" => "13",
            "Số 14" => "14",
            "Số 15" => "15",
            "Số 16" => "16",
            "Số 17" => "17",
            "Số 18" => "18",
            "Số 19" => "19",
            "Số 20" => "20",
            "Số 21" => "21",
            "Số 22" => "22",
            "Số 23" => "23",
            "Số 24" => "24",
            "Số 25" => "25",
            "Số 26" => "26",
            "Số 27" => "27",
            "Số 28" => "28",
            "Số 29" => "29",
        ];
    const DISTRICT
                = [
            "1"  => "Quận 1",
            "2"  => "Quận 2",
            "3"  => "Quận 3",
            "4"  => "Quận 4",
            "5"  => "Quận 5",
            "6"  => "Quận 6",
            "7"  => "Quận 7",
            "8"  => "Quận 8",
            "9"  => "Quận 9",
            "10" => "Quận 10",
            "11" => "Quận 11",
            "12" => "Quận 12",
            "13" => "Quận 13",
            "14" => "Quận 14",
            "15" => "Quận 15",
        ];
    const array = array(1872, 1873, 2053, 2055, 5244, 5573, 5714, 6548, 7943, 8044, 9222, 9241);
    public static $weight_converts = ['GRAM' => 1, 'KG' => 1000];
    protected $model;

    public function __construct()
    {
    }

    public static function getToken()
    {
        try {
            $param = [
                "TenDangNhap" => env("VNP_USERNAME"),
                "MatKhau"     => env("VNP_PASSWORD")
            ];

            $client   = new Client();
            $response = $client->post(env("VNP_END_POINT") . "/MobileAuthentication/GetAccessToken", [
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => json_encode($param),
                'timeout' => 10
            ]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];
        } catch (\Exception $exception) {
            return ['status' => 'error', 'success' => false, 'message' => $exception->getMessage()];
        }
        return $response['Token'];
    }

//    public static function getAddress()
//    {
//        $client = new Client();
//        $response = $client->get("https://donhang.vnpost.vn/api/api/PhuongXa/GetAll", [
//            'headers' => ['Content-Type' => 'application/json'],
//        ]);
//        $response = $response->getBody()->getContents() ?? null;
//        $response = !empty($response) ? json_decode($response, true) : [];
//        dd(count($response));
//            foreach ($response as $t => $value) {
//                $district = Ward::where('name',static::WARD[$value['TenPhuongXa']] ?? $value['TenPhuongXa'])->whereHas('district', function ($q) use ($value) {
//                    $q->where('id_district_vnp', $value['MaQuanHuyen']);
//                    $q->where('name_district_vnp', $value['TenQuanHuyen']);
//                })->first();
//                if (!empty($district) && empty($district->id_ward_vnp)) {
//                    $district->id_ward_vnp = $value['MaPhuongXa'];
//                    $district->name_ward_vnp = $value['TenPhuongXa'];
//                    $district->ma_buu_chinh = $value['MaBuuChinh'];
//                    $district->save();
//                }
//                else {
//                    $district = Ward::model()->where('full_name', $value['TenPhuongXa'])->whereHas('district', function ($q) use ($value) {
//                        $q->where('id_district_vnp', $value['MaQuanHuyen']);
//                        $q->where('name_district_vnp', $value['TenQuanHuyen']);
//                    })->first();
//                    if (!empty($district) && empty($district->id_ward_vnp)) {
//                        $district->id_ward_vnp = $value['MaPhuongXa'];
//                        $district->name_ward_vnp = $value['TenPhuongXa'];
//                        $district->ma_buu_chinh = $value['MaBuuChinh'];
//                        $district->save();
//                    }
//                }
//            }
//    }
    public static function getShipFeeAllService(Request $request, $store)
    {
        $input            = $request->all();
        $weight           = 0;
//        $weight_free_item = 0;
//        $weight_gift_item = 0;
        $height_item_product = 0;
        $width_item_product  = 0;
        $length_item_product = 0;
        try {
            if (!empty($input['cart_id'])) {
                $cart = Cart::with('details')->where('id', $input['cart_id'])->first();
                if (empty($cart)) {
                    throw new \Exception(Message::get("V003", $input['cart_id']));
                }
                if (empty($cart->getWard->id_ward_vnp)) {
                    return [];
                }
                $total_price = $cart->total_info[array_search('total', array_column($cart->total_info, 'code'))]['value'];
                $weight_free_item = (float)$cart->total_weight*1000;
//                if ($cart->free_item && $cart->free_item != "[]") {
//                    foreach ($cart->free_item as $item) {
//                        foreach ($item['text'] as $value) {
////                            $width_item_product += ($value['width'] * ($value['qty_gift'] ?? 1)) ?? 0;
////                            $length_item_product += ($value['length'] * ($value['qty_gift'] ?? 1)) ?? 0;
////                            $height_item_product += ($value['height'] * ($value['qty_gift'] ?? 1)) ?? 0;
//                            $weight_free_item += $value['weight'] * ($value['qty_gift'] ?? 1) * self::$weight_converts[($value['weight_class'])];
//                        }
//                    }
//                }
//                $weight1 = $cart->details->map(function ($detail) {
//                    return [
//                        'id'     => $detail->product_id,
//                        'qty'    => $detail['quantity'],
//                        'weigth' => $detail['quantity'] * $detail->product->weight * self::$weight_converts[($detail->product->weight_class)],
//                    ];
//                });
//
//                foreach ($weight1 as $key) {
//                    $product             = Product::model()->where('id', $key['id'])->first();
//                    if($product->is_cool==1){
//                        return [];
//                    }
//                    $width_item_product  += $product['width'] ?? 0;
//                    $length_item_product += $product['length'] ?? 0;
//                    $height_item_product += $product['height'] ?? 0;
//                    $weight_free_item    += $product['weight'] * $key['qty'] * self::$weight_converts[($product['weight_class'])];
//                    if ($product['gift_item'] && $product['gift_item'] != "[]") {
//                        foreach (json_decode($product['gift_item']) as $value) {
//                            if (!empty($value->weight)) {
////                                $width_item_product  += !empty($value->width) ? $value->width : 0;
////                                $length_item_product +=!empty($value->length) ?$value->length : 0;
////                                $height_item_product += !empty($value->height)?$value->height : 0;
//                                $weight_free_item += $value->weight * self::$weight_converts[$value->weight_class];
//                            }
//                        }
//                    }
//                }
                $receiverProvince = $cart->getCity->id_city_vnp;
                $receiverDistrict = $cart->getDistrict->id_district_vnp;
                $receiverArea     = $cart->getCity->description;
                $receiverCityArea = $cart->customer_city_code;
                if (!empty($cart->distributor_code)) {
                    if (empty($cart->getWardDistributor->id_ward_vnp) || $cart->getUserDistributor->is_transport != 1) {
                        return [];
                    }
                    if(!empty($cart->getUserDistributor->type_delivery_hub) && $cart->getUserDistributor->type_delivery_hub != SHIPPING_PARTNER_TYPE_VNP){
                        return [];
                    }
//                    $senderProvice  = $distributor->getCity->id_city_vnp;
//                    $senderDistrict = $distributor->getDistrict->id_district_vnp;
                    $senderArea     = $cart->getCityDistributor->description;
                    $senderCity     = $cart->getCityDistributor->code;
                }
                if (empty($cart->distributor_code)) {
                    $store = Store::model()->where('id', $store)->first();
                    if (empty($store->ward->id_ward_vnp)) {
                        return [];
                    }
                    $senderProvice  = $store->city->id_city_vnp;
                    $senderArea     = $store->city->description;
                    $senderDistrict = $store->district->id_district_vnp;
                    $senderCity     = $store->city_code;
                }
            }

//            if (!empty($input['order_id'])) {
//                $order = Order::with("details.product")->where('id', $input['order_id'])->first();
//                if (empty($order)) {
//                    throw new \Exception(Message::get("V003", $input['order_id']));
//                }
//                $total_price = json_decode($order->total_info)[array_search('total', array_column(json_decode($order->total_info), 'code'))]->value;
//                if ($order->free_item && $order->free_item != "[]") {
//                    foreach ($order->free_item as $item) {
//                        foreach ($item['text'] as $value) {
////                            $width_item_product += ($value['width'] * ($value['qty_gift'] ?? 1)) ?? 0;
////                            $length_item_product += ($value['length'] * ($value['qty_gift'] ?? 1)) ?? 0;
////                            $height_item_product += ($value['height'] * ($value['qty_gift'] ?? 1)) ?? 0;
//                            $weight_free_item += $value['weight'] * ($value['qty_gift'] ?? 1) * self::$weight_converts[($value['weight_class'])];
//                        }
//                    }
//                }
//                foreach ($order->details as $input_detail) {
//                    $product             = Product::model()->where('id', $input_detail['product_id'])->first();
//                    $width_item_product  += $product['width'] ?? 0;
//                    $length_item_product += $product['length'] ?? 0;
//                    $height_item_product += $product['height'] ?? 0;
//                    if ($product['gift_item'] && $product['gift_item'] != "[]") {
//                        foreach (json_decode($product['gift_item']) as $value) {
//                            if (!empty($value->weight)) {
////                                $width_item_product += !empty($value->width) ? $value->width : 0;
////                                $length_item_product += !empty($value->length) ?$value->length : 0;
////                                $height_item_product += !empty($value->height) ?$value->height : 0;
//                                $weight_free_item += $value->weight * self::$weight_converts[$value->weight_class];
//                            }
//                        }
//                    }
//                }
//                $receiverProvince = $order->getCity->id_city_vnp;
//                $receiverDistrict = $order->getDistrict->id_district_vnp;
//                $receiverArea     = $order->getCity->description;
//                $receiverCityArea = $order->shipping_address_city_code;
//                if (!empty($order->distributor_code)) {
//                    $distributor    = Distributor::model()->where('code', $order->distributor_code)->first();
//                    $senderProvice  = $distributor->getCity->id_city_vnp;
//                    $senderDistrict = $distributor->getDistrict->id_district_vnp;
//                    $senderArea     = $distributor->getCity->description;
//                    $senderCity     = $distributor->code;
//                }
//                if (empty($order->distributor_code)) {
//                    $store          = Store::model()->where('id', $store)->first();
//                    $senderProvice  = $store->city->id_city_vnp;
//                    $senderArea     = $store->city->description;
//                    $senderDistrict = $store->district->id_district_vnp;
//                    $senderCity     = $store->city_code;
//                }
//                if (!empty($input['distributor_code'])) {
//                    $distributor    = Distributor::model()->where('code', $input['distributor_code'])->first();
//                    $senderProvice  = $distributor->getCity->id_city_vnp;
//                    $senderDistrict = $distributor->getDistrict->id_district_vnp;
//                    $senderArea     = $distributor->getCity->description;
//                    $senderCity     = $distributor->code;
//                }
//            }
            if ($weight_free_item >= 30000) {
                return [];
            }
            $time_ems  = "1.5 - 3 ngày";
            $time_ecod = "3 - 5 ngày";
            $region    = "Cận Vùng";

            if (!empty($senderArea)) {
                if (($senderArea == "MN" && $receiverArea == "MB") || ($senderArea == "MB" && $receiverArea == "MN")) {
                    $region    = "Liên vùng";
                    $time_ems  = "2 - 4 ngày";
                    $time_ecod = "4 - 6 ngày";
                }
                if ($senderArea == $receiverArea) {
                    $region    = "Nội vùng";
                    $time_ems  = "1 - 2 ngày";
                    $time_ecod = "2 - 3 ngày";
                }
            }
            if ($receiverCityArea == $senderCity) {
                $region    = "Nội Tỉnh";
                $time_ems  = "12h - 24h";
                $time_ecod = "23h - 36h";
            }
            $result_EMS = ShipFeeVNP::where('type_address', SHIPPING_PARTNER_TYPE_VNP)->where('type', "EMS")->where('type_ship', $region)->first();
            $result_BK  = ShipFeeVNP::where('type_address', SHIPPING_PARTNER_TYPE_VNP)->where('type', "BK")->where('type_ship', $region)->first();
            if ($weight_free_item <= 2000) {
                $price_EMS = $result_EMS->price_ship_zero_two;
                $price_BK  = $result_BK->price_ship_zero_two;
            }

            if ($weight_free_item >= 2000 && $weight_free_item <= 5000) {
                $price_EMS = $result_EMS->price_ship_two_five;
                $price_BK  = $result_BK->price_ship_two_five;
            }
            if ($weight_free_item >= 5000 && $weight_free_item <= 10000) {

                $price_EMS = $result_EMS->price_ship_five_ten;
                $price_BK  = $result_BK->price_ship_five_ten;
            }
            if ($weight_free_item > 10000) {
                $priceTotal_EMS = ($weight_free_item - 10000) / 1000 * $result_EMS->price_ship_over_ten;
                $priceTotal_BK  = ($weight_free_item - 10000) / 1000 * $result_BK->price_ship_over_ten;
                $price_EMS      = $result_EMS->price_ship_five_ten + $priceTotal_EMS;
                $price_BK       = $result_BK->price_ship_five_ten + $priceTotal_BK;
            }
//            if (!empty(env("VNP_SUPPORT_COVID"))) {
//                if (in_array($receiverDistrict, self::array)) {
//                    $price_EMS = ($price_EMS * 0.1) + $price_EMS;
//                    $price_BK  = ($price_BK * 0.1) + $price_BK;
//                }
//            }
//            $param    = [
//                "SenderDistrictId"                => $senderDistrict,
//                "SenderProvinceId"                => $senderProvice,
//                "ReceiverDistrictId"              => $receiverDistrict,
//                "ReceiverProvinceId"              => $receiverProvince,
//                "Weight"                          => $weight_free_item,
////                "Width"                           => 1,
////                "Length"                          => 1,
////                "Height"                          => 1,
////                "CodAmount"                       => $total_price,
////                "OrderAmount"                     => $total_price,
////                "IsReceiverPayFreight"            => false,
////                "UseBaoPhat"                      => false,
////                "UseHoaDon"                       => false,
////                "UseNhanTinSmsNguoiNhanTruocPhat" => false,
////                "UseNhanTinSmsNguoiNhanSauPhat"   => false,
//            ];
//            $client   = new Client();
//            $response = $client->post(env("VNP_END_POINT") . "/CustomerConnect/TinhCuocTatCaDichVu", [
//                'headers' => ['Content-Type' => 'application/json', 'h-token' => $token],
//                'body'    => json_encode($param),
//            ]);
//            $response = $response->getBody()->getContents() ?? null;
//            $response = !empty($response) ? json_decode($response, true) : [];
//            $ems      = array_search('EMS', array_column($response, 'MaDichVu'));
//            $ecod     = array_search('BK', array_column($response, 'MaDichVu'));
//
//            $data     = [];
//            if (isset($ems) && is_integer($ems) == true) {
//                $data[] = [
//                    'MaDichVu' => "EMS",
//                    'GiaCuoc'  => $response[$ems]['TongCuocSauVAT'],
//                    // 'CuocCOD'  => $response[$ems]['CuocCOD'],
//                    'time'     => $time_ems
//                ];
//            }
//            if (isset($ecod) && is_integer($ecod) == true) {
//                $data[] = [
//                    'MaDichVu' => "BK",
//                    'GiaCuoc'  => $response[$ecod]['TongCuocSauVAT'],
//                    'CuocCOD'  => $response[$ecod]['CuocCOD'],
//                    'time'     => $time_ecod
//                ];
//            }
//            return $data;
            $data[] = [
                'MaDichVu' => "TMDT_BK",
                'GiaCuoc'  => round($price_BK),
                'time'     => $time_ecod
            ];
            $data[] = [
                'MaDichVu' => "TMDT_EMS",
                'GiaCuoc'  => round($price_EMS),
                'time'     => $time_ems
            ];
            return $data;

        } catch (\RequestException  $exception) {
            return $exception->getRequest() . "\n";
        }
    }

     static function sendOrder(Order $order, $token,$check=null)
    {
        $price            = 0;
        $weight           = 0;
        $weight_free_item = 0;
//        $weight_gift_item = 0;
        $height_item_product = 0;
        $width_item_product  = 0;
        $length_item_product = 0;
        try {
            if (empty($order) || empty($order->id)) {
                throw new \Exception(Message::get("V001", Message::get("orders")));
            }
            $order = Order::model()->with(['details.product.warehouse', 'store', 'customer.profile'])->where('id',
                $order->id)->first();
//            if ($order->free_item && $order->free_item != "[]") {
//                foreach (json_decode($order->free_item) as $item) {
//                    foreach ($item->text as $value) {
////                        $width_item_product  += ($value['width'] * ($value['qty_gift'] ?? 1)) ?? 0;
////                        $length_item_product += ($value['length'] * ($value['qty_gift'] ?? 1)) ?? 0;
////                        $height_item_product += ($value['height'] * ($value['qty_gift'] ?? 1)) ?? 0;
//                        $weight_free_item += $value->weight * ($value->qty_gift ?? 1) * self::$weight_converts[($value->weight_class)];
//                    }
//                }
//            }
            if (!empty($order->distributor_code)) {
//                $distributor         = Distributor::model()->where('code', $order->distributor_code)->first();
                $senderProvice       =$order->distributor->profile->city->id_city_vnp;
                $senderDistrict      = $order->distributor->profile->district->id_district_vnp;
                $senderWard          =  $order->distributor->profile->ward->id_ward_vnp;
                $distributor_address = $order->distributor->profile->address;
            }
            if (empty($order->distributor_code)) {
                $store               = Store::find(TM::getCurrentStoreId());
                $senderProvice       = $store->city->id_city_vnp;
                $senderDistrict      = $store->district->id_district_vnp;
                $senderWard          = $store->ward->id_ward_vnp;
                $distributor_address = $store->address;
            }

            $cod_amount = $order->total_price;
//            if (!empty($order->ship_fee) && !empty($order->total_price) && $order->ship_fee != 0) {
//                $cod_amount = $order->total_price - (int)$order->ship_fee;
//            }
            // Get Ward
            $order_details = [];
            $warehouse     = [];
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
//                $width_item_product  += ($item['width']   *  $order_detail['qty']) ?? 0;
//                $length_item_product += ($item['length'] * ( $order_detail['qty'] ?? 1)) ?? 0;
//                $height_item_product += ($item['height'] * ( $order_detail['qty'] ?? 1)) ?? 0;
//                $weight_free_item += $item['weight'] * ($order_detail['qty'] ?? 1) * self::$weight_converts[($item['weight_class'])];
//                if ($item['gift_item'] && $item['gift_item'] != "[]") {
//                    foreach (json_decode($item['gift_item']) as $value) {
//                        if (!empty($value->weight)) {
////                            $width_item_product  += $value['width']  ?? 0;
////                            $length_item_product += $value['length'] ?? 0;
////                            $height_item_product += $value['height'] ?? 0;
//                            $weight_free_item += $value['weight'] * self::$weight_converts[($value['weight_class'])];
//                        }
//                    }
//                }
                $price += $item['price'] ?? $order_detail['price'];
//                $weight += $order_detail['qty'] * $item['weight'] * $weight_converts[$item['weight_class']];
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
            $lastShip  = ShippingOrder::model()->where('ship_code', 'like',
                "{$order->code}-" . SHIPPING_PARTNER_TYPE_VNP . "-%")->orderBy('id', 'desc')->first();
            $codeIndex = 0;
            if (!empty($lastShip)) {
                $codeIndex = explode("-" . SHIPPING_PARTNER_TYPE_VNP . "-", $lastShip->ship_code);
                $codeIndex = (int)($codeIndex[1] ?? 0);
            }
            $shipCode = $order->code . "-" . SHIPPING_PARTNER_TYPE_VNP . "-" . (str_pad(++$codeIndex, 2, '0',
                    STR_PAD_LEFT));
            $shipCode = implode('', explode('-', $shipCode));
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
            $params   = [
                "SenderTel"                       => !empty($order->distributor_code) ? $order->distributor_phone : $store->contact_phone,
                "SenderFullname"                  => !empty($order->distributor_code) ? $order->addressDistributor->name : $store->name,
                "SenderAddress"                   => !empty($order->distributor_code) && !empty($distributor_address) ? $distributor_address : $store->address,
                "SenderWardId"                    => $senderWard,
                "SenderDistrictId"                => $senderDistrict,
                "SenderProvinceId"                => $senderProvice,
                "ReceiverTel"                     => $order->customer_phone,
                "ReceiverFullname"                => $order->customer_name,
                "ReceiverAddress"                 => $order->shipping_address,
                "ReceiverWardId"                  => $order->getWard->id_ward_vnp,
                "ReceiverDistrictId"              => $order->getDistrict->id_district_vnp,
                "ReceiverProvinceId"              => $order->getCity->id_city_vnp,
                "ReceiverAddressType"             => null,
                "ServiceName"                     => $order->shipping_service,
                "OrderCode"                       => $shipCode,
                ////                "PackageContent" => false,
                "WeightEvaluation"                => ($order->total_weight)*1000,
//                "WidthEvaluation"                 => 1,
//                "LengthEvaluation"                => 1,
//                "HeightEvaluation"                => 1,
                "IsPackageViewable"               => false,
                "CustomerNote"                    => $order->shipping_note,
                "PickupType"                      => 1,
                "CodAmountEvaluation"             => $order_payment == 2 || $order_payment == 3 ? $cod_amount : 0,
                "IsReceiverPayFreight"            => false,
//                "OrderAmountEvaluation"           => $order_payment == 2 || $order_payment == 3 ? $cod_amount : 0,
//                "UseBaoPhat"                      => false,
//                "UseHoaDon"                       => false,
//                "UseNhanTinSmsNguoiNhanTruocPhat" => false,
//                "UseNhanTinSmsNguoiNhanSauPhat"   => false,
            ];
            $client   = new Client();
            $response = $client->post(env("VNP_END_POINT") . "/CustomerConnect/CreateOrder", [
                'headers' => ['Content-Type' => 'application/json', 'h-token' => $token],
                'body'    => json_encode($params),
            ]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];
            if (!empty($response) && $response['OrderCode'] == $shipCode) {
                // Update Order
                $order->shipping_info_code = $response['Id'] ?? null;
                $order->shipping_info_json = !empty($response) ? json_encode($response) : null;
                $order->status             = OrderStatus::SHIPPING;
                $order->save();
                if($check != 1){
                    OrderHistory::insert([
                        'order_id'   => $order->id,
                        'status'     => OrderStatus::SHIPPING,
                        'created_at' => date("Y-m-d H:i:s", time()),
                        'created_by' => TM::getCurrentUserId(),
                    ]);
                    self::updateOrderStatusHistory($order);
                }
                // Create Shipping Order
                $shippingOrder                = new ShippingOrder();
                $shippingOrder->type          = SHIPPING_PARTNER_TYPE_VNP;
                $shippingOrder->code          = $order->code;
                $shippingOrder->name          = $order->code;
                $shippingOrder->code_type_ghn = $response['ItemCode'] ?? null;
                // $shippingOrder->partner_id = $response['partner_id'];
                $shippingOrder->status      = 'SHIPPING';
                $shippingOrder->status_text = 'Sẵn sàng giao hàng';
                $shippingOrder->description = $order->shipping_note;
                // $shippingOrder->ship_fee = $response['fee'];
                $shippingOrder->order_id    = $order->id;
                $shippingOrder->ship_code   = $shipCode;
                $shippingOrder->count_print = 0;
                $shippingOrder->company_id  = TM::getCurrentCompanyId();
                $shippingOrder->result_json = json_encode($response) ?? null;
                $shippingOrder->created_at  = date("Y-m-d H:i:s");
                $shippingOrder->created_by  = TM::getCurrentUserId();
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
            return $exception->getMessage();
        }
        return ['status' => 'success', 'success' => true, 'shipping_orders' => $shippingOrder, 'warehouse' => $warehouse];
    }

    public static function cancelOrder($shipping_code, $token)
    {
        try {
            $client   = new Client();
            $params   = [
                "OrderId" => $shipping_code
            ];
            $response = $client->post(env("VNP_END_POINT") . "/CustomerConnect/CancelOrder", [
                'headers' => ['Content-Type' => 'application/json', 'h-token' => $token],
                'body'    => json_encode($params)
            ]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];
        } catch (\RequestException  $exception) {
            return $exception->getRequest() . "\n";
        }
        return ['status' => 'success', 'success' => true];
    }
    public static function updateOrderStatusHistory(Order $order)
    {
        $orderStatus = OrderStatus::model()->where([
            'code'       => $order->status,
            'company_id' => TM::getCurrentCompanyId() ?? $order->company_id
        ])->first();
        $param = [
            'order_id'          => $order->id,
            'order_status_id'   => $orderStatus->id ?? null,
            'order_status_code' => $orderStatus->code ?? null,
            'order_status_name' => $orderStatus->name ?? null,
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
            'created_by'        => TM::getCurrentUserId() ?? $order->customer_id
        ];
        OrderStatusHistory::insert($param);
//        $orderStatusHistory                    = new OrderStatusHistory();
//        $orderStatusHistory->order_id          = $order->id;
//        $orderStatusHistory->order_status_id   = $orderStatus->id ?? null;
//        $orderStatusHistory->order_status_code = $orderStatus->code ?? null;
//        $orderStatusHistory->order_status_name = $orderStatus->name ?? null;
//        $orderStatusHistory->created_by        = 1;
//        $orderStatusHistory->save();
    }
}