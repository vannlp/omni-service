<?php

namespace App\V1\Library;

use App\Category;
use App\CdpLogs;
use App\Order;
use App\Product;
use App\PromotionProgram;
use App\Supports\TM_Error;
use App\TM;
use App\User;
use App\V1\Models\OrderModel;
use DateTime;
use DateTimeZone;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 *  #Class CDP
 * Customer Data Platform
 * Nền tảng dữ liệu khách hàng là một tập hợp phần mềm tạo ra một cơ sở dữ liệu khách hàng thống nhất, bền vững mà các hệ thống khác có thể truy cập được. 
 * Dữ liệu được lấy từ nhiều nguồn, được làm sạch và kết hợp để tạo ra một hồ sơ khách hàng duy nhất.
 */
class CDP
{

    public static $config = null;


    public static $__MOM_CODE = [
        "CUSTOMER_DEV"              => "M-RATK1-O-YIWSL-M",
        "CUSTOMER_LIVE"             => "M-RWJJJ-O-EGIZ0-M",

    ];

    /**
     * @param null
     * Hàm tạo
     */
    public function __construct()
    {
    }

    /**
     * Dữ liệu master
     * @return object
     */
    public static function handle()
    {
        date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Ho_Chi_Minh'));
        return DB::table('active_status')->where(['name' => 'CDP', 'is_active' => 1])->first();
    }

    /**
     * Xác thực và trả về token
     * @return string
     *
     */
    public static function getToken()
    {
        $client = new Client([
            'defaults'                          => [
                RequestOptions::CONNECT_TIMEOUT => 5,
                RequestOptions::ALLOW_REDIRECTS => true,
            ],
            RequestOptions::VERIFY  => false,
        ]);

        $params = [
            "username" => self::handle()->value,
            "apiKey"   => self::handle()->value_2,
            "cdpGKey"   => self::handle()->value_3,
        ];

        $reponse = $client->request('POST', self::handle()->value_4, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($params),
        ]);

        if (!empty($reponse)) {
            $data = json_decode($reponse->getBody()->getContents(), true);
            if (!empty($data['status']) && ($data['status'] == 200 || $data['status'] == true)) {
                return $data['token'];
            } else {
                self::writeLogCDP("Xác thực đăng nhập", null, $params, $data['message'], null, null, "SUCCESS", 'getToken', null);
                return null;
            }
        } else {
            self::writeLogCDP("Xác thực đăng nhập", null, $params, null, null, null, "FAILED", 'getToken', null);
            return null;
        }
    }

    /**
     * Ghi nhận lịch sử đồng bộ
     * @param $type
     * @param $params
     * @param $res
     * @param $content
     * @param $code
     * @param $status
     * @param $function_request
     * @return bool
     */
    public static function writeLogCDP($type, $params, $param_request, $res, $content, $code, $status, $function_request, $log_find)
    {
        try {
            if (!empty($log_find)) {
                $log_find->param            = json_encode($params);
                $log_find->param_request    = json_encode($param_request);
                $log_find->response         = $res;
                $log_find->content          = $content;
                $log_find->code             = $code;
                $log_find->status           = $status;
                $log_find->function_request = $function_request;
                $log_find->count_repost    = $log_find->count_repost + 1;
                $log_find->save();
            } else {
                CdpLogs::create([
                    'param'             => !empty($params) ? json_encode($params) : null,
                    'param_request'     => json_encode($param_request),
                    'response'          => $res,
                    'content'           => $content,
                    'sync_type'         => $type,
                    'code'              => $code,
                    'status'            => $status,
                    'function_request'  => $function_request,
                ]);
            }
        } catch (Exception $e) {
            TM::sendMessage('CDP Exception: ', $e);
        }
        return true;
    }
    /**
     * Đồng bộ đơn hàng sang CDP
     * @param $order
     * @param $function_request
     * @return int
     * @throws Exception
     */
    public static function pushOrderCdp($order, $function_request, $log_find = null)
    {
        try {
            if (self::handle()) {
                $token = self::getToken();
                $created_at = DateTime::createFromFormat(
                    'd-m-Y H:i:s',
                    date('d-m-Y H:i:s', strtotime($order->created_at)),
                    new DateTimeZone('Asia/Ho_Chi_Minh')
                );

                $updated_at = DateTime::createFromFormat(
                    'd-m-Y H:i:s',
                    date('d-m-Y H:i:s', strtotime($order->updated_at)),
                    new DateTimeZone('Asia/Ho_Chi_Minh')
                );
                $items = [];
                foreach ($order->details as $detail) {
                    // $sub_cates = explode(",", $detail->product->category_ids);
                    $sub_cates = explode(",", $detail->product_category);
                    $sub_cate_string = [];
                    $main_cate = null;
                    foreach ($sub_cates as $key => $value) {
                        $sub_cate = Category::model()->where(['id' => $value])->first();
                        if (empty($sub_cate)) {
                            continue;
                        }
                        if ($sub_cate && !$main_cate) {
                            $main_cate = $sub_cate->name ?? null;
                            continue;
                        }

                        if (!empty($sub_cate)) {
                            $sub_cate_string[] = $sub_cate->name;
                        }
                    }

                    $discount = !empty($detail->discount) ? $detail->discount : 0;
                    $items[] = [
                        "orderId"           => $order->id,
                        "productSku"        => $detail->product_code, //Mã SKU
                        "productName"       => $detail->product_name, //Tên sản phẩm
                        "orderDate"         => DateTime::createFromFormat(
                            'd-m-Y H:i:s',
                            date('d-m-Y H:i:s', strtotime($order->created_at)),
                            new DateTimeZone('Asia/Ho_Chi_Minh')
                        )->getTimestamp() * 1000, //Ngày đặt hàng
                        "price"             => $detail->real_price, //Giá gốc sản phẩm
                        "unit"              => "VND",
                        "quantity"          => $detail->qty, //Số lượng
                        "price4Sale"        => $detail->price, //Giá bán
                        "discount"          => $discount, //Giảm giá
                        "totalProductCost"  => $detail->price - $discount, //Tổng tiền trước discount
                        "totalCost"         => $detail->price, //Tổng tiền sau discount
                        "addedAt"           => DateTime::createFromFormat(
                            'd-m-Y H:i:s',
                            date('d-m-Y H:i:s', strtotime($detail->created_at)),
                            new DateTimeZone('Asia/Ho_Chi_Minh')
                        )->getTimestamp() * 1000, //Thời gian tạo
                        "updatedAt"         => DateTime::createFromFormat(
                            'd-m-Y H:i:s',
                            date('d-m-Y H:i:s', strtotime($detail->updated_at)),
                            new DateTimeZone('Asia/Ho_Chi_Minh')
                        )->getTimestamp() * 1000, //Thời gian cập nhật
                        "customFieldTexts"          => [
                            "customField14" => $detail->product_code . "-" . $detail->id, // ID Purchase detail
                            "customField20" => !empty($detail->product->brand) ? $detail->product->brand->name : null, // Brand Thuộc thương hiệu nào
                            "customField15" => !empty($main_cate) ? $main_cate : null, // Category Thuộc nhóm sản phẩm nào
                            "customField16" => implode(",", $sub_cate_string), // Sub Category Thuộc nhóm sản phẩm con nào
                            "customField17" => !empty($discount) ? "Value" : null, // Loại giảm giá (% | Value)
                            "customField18" => !empty($detail->createdBy) ? $detail->createdBy->code : $order->customer_code, // Đối tượng tạo
                            "customField19" => !empty($detail->updatedBy) ? $detail->updatedBy->code : null, // Đối tượng chỉnh sửa gần nhất
                        ]
                    ];
                }

                $list_promotion = [];
                $discountOrderKM = 0;
                $coupon         = [];

                if (!empty($order->total_info)) {
                    $arrayTotal = json_decode($order->total_info, true);

                    if (count($arrayTotal) > 2) {
                        foreach ($arrayTotal as $key => $value) {
                            if ($value['code'] == 'sub_total' || $value['code'] == 'total') {
                                continue;
                            }
                            $promotion = PromotionProgram::model()->where('code', $value['code'])->first();

                            if (!empty($promotion)) {
                                if ($promotion->type == "CART" && $promotion->promotion_type == "DISCOUNT") {
                                    $discountOrderKM += $value['value'];
                                }
                                if ($promotion->type == "CART" && $promotion->promotion_type == "GIFT" && $promotion->act_type == "combo") {
                                    $discountOrderKM += $value['value'];
                                }
                                if ($promotion->type == "CART" && $promotion->promotion_type == "GIFT" && $promotion->act_type == "promotion_list" && !empty($promotion->act_price)) {
                                    $discountOrderKM += $value['value'];
                                }
                                array_push($list_promotion, $promotion->code);
                            }

                            if ($value['code'] == 'coupon' || $value['code'] == 'coupon_delivery') {
                                array_push($coupon, $value['title']);
                                $discountOrderKM += $value['value'];
                            }
                        }
                    }
                }
                $cancel_reason = null;
                if (!empty($order->canceled_reason)) {
                    $cancel_reason = json_decode($order->canceled_reason)->value;
                }

                if (!empty($order->cancel_reason)) {
                    $cancel_reason = $order->cancel_reason;
                }

                if (!empty($order->canceled_reason_admin)) {
                    $cancel_reason = $order->canceled_reason_admin;
                }


                $user_approver = null;
                if (!empty($order->seller)) {
                    $user_approver = $order->seller;
                } else {
                    $user_approver = $order->user;
                }

                $data = [
                    "objectType"                => "order",
                    "source"                    => "Ecommerce",
                    "entries"                   => [
                        [
                            "systemName"            => "NutifoodShop",
                            "momCode"               => "order", // MOM Code
                            "orderId"               => $order->id,
                            "orderDate"             => $created_at->getTimestamp() * 1000,
                            "customerId"            => $order->customer_id,
                            "customerName"          => $order->customer_name,
                            "customerPhone"         => $order->customer_phone,
                            "customerEmail"         => $order->customer_email,
                            "orderStatus"           => $order->status_text,
                            "totalProductCost"      => $order->sub_total_price, // Tổng tiền Hàng
                            "discount"              => !empty($order->saving) ? $order->saving : 0, //Tổng gía trị giảm giá
                            "shippingCost"          => $order->ship_fee, // Phí vận chuyển
                            "totalCost"             => (float) $order->total_price, // Tổng giá trị đơn hàng
                            "createdAt"             => $created_at->getTimestamp() * 1000,
                            "createdBy"             => Arr::get($order, 'createdBy.code', $order->customer_code),
                            "updatedBy"             => !empty($order->updatedBy) ? $order->updatedBy->code : null,
                            "updatedAt"             => $updated_at->getTimestamp() * 1000,
                            "items"                 => $items, // Details
                            "customFieldTexts"          => [
                                "customField06"         => !empty($order->description) ? $order->description : (!empty($order->crm_description) ? $order->crm_description : null), // Description | Required
                                "customField08"         => $order->street_address, // Street | Required
                                "customField09"         => $order->shipping_address_city_type . " " . $order->shipping_address_city_name, // City | Required
                                "customField11"         => $order->shipping_address, // Address | Required
                                "customField61"         => $order->shipping_address_ward_type . " " .  $order->shipping_address_ward_name, // Ward
                                "customField60"         => $order->shipping_address_district_type . " " . $order->shipping_address_district_name, // District
                                "customField28"         => $cancel_reason, // canceled_reason | cancel_reason | canceled_reason_admin sài trường nào ??
                                "customField53"         => $order->code, // Mã đơn hàng
                                "customField54"         => null, // Loại Account: Bussiness & Person
                                "customField55"         => !empty($order->distributor) ? $order->distributor->code : null, // Mã số Cửa hàng
                                "customField56"         => !empty($order->distributor) ? $order->distributor->name : null, // Tên Cửa hàng
                                "customField57"         => !empty($order->distributor) ? ($order->distributor->profile->address . ", " . object_get($order->distributor, "profile.ward.full_name", null) . ", " . object_get($order->distributor, "profile.district.full_name", null) . ", " . object_get($order->distributor, "profile.city.full_name", null)) : null, // Địa chỉ cửa hàng
                                "customField58"         => "VND", // Currency
                                "customField59"         => implode(",", $coupon), // Coupon Code
                                "customField63"         => !empty($order->shippingOrder) ? (!empty($order->shippingOrder->code_type_ghn) ? $order->shippingOrder->code_type_ghn : $order->shippingOrder->ship_code) : null, // Mã giao hàng
                                "customField64"         => !empty($user_approver) ? $user_approver->code : null, // Mã nhân viên xử lý đơn
                                "customField65"         => !empty($user_approver->profile) ? $user_approver->profile->full_name : null, // Họ và tên nhân viên tạo đơn
                                "customField66"         => !empty($user_approver->profile->city) ? $user_approver->profile->city->full_name : null, // Tỉnh thành nhân viên tạo đơn
                                "customField67"         => !empty($user_approver) ? $user_approver->type : null, // Loại nhân viên tạo đơn
                            ],
                            "customFieldListTexts"      => [
                                "customFieldList01"     => !empty($list_promotion) ? $list_promotion : null, // Mã Promo áp dụng cho toàn đơn hàng | VD: Promo1,Promo2
                            ],
                            "customFieldTimestamps"         => [
                                "customFieldTimestamp02"    => !empty($order->canceled_date) ?  DateTime::createFromFormat(
                                    'd-m-Y H:i:s',
                                    date('d-m-Y H:i:s', strtotime($order->canceled_date)),
                                    new DateTimeZone('Asia/Ho_Chi_Minh')
                                )->getTimestamp() * 1000 : null, // Ngày đóng đơn hàng
                                "customFieldTimestamp07"         => !empty($order->delivery_time) ?  DateTime::createFromFormat(
                                    'd-m-Y H:i:s',
                                    date('d-m-Y H:i:s', strtotime($order->delivery_time)),
                                    new DateTimeZone('Asia/Ho_Chi_Minh')
                                )->getTimestamp() * 1000 : null, // Thời gian giao hàng thành công
                            ]
                        ]
                    ],
                ];

                $client = new Client([
                    'defaults'                          => [
                        RequestOptions::CONNECT_TIMEOUT => 5,
                        RequestOptions::ALLOW_REDIRECTS => true,
                    ],
                    RequestOptions::VERIFY  => false,
                ]);
                // TM::sendMessage('CDP Order Data REQ: ', $data);
                try {
                    $response = $client->request("POST", self::handle()->value_5 . "?initData=true", [
                        'headers' => [
                            'Content-Type'      => 'application/json',
                            "TOKEN"             => $token,
                            "API_KEY"           => self::handle()->value_2,
                        ],
                        'body' => json_encode($data)
                    ]);

                    $status     = $response->getStatusCode();
                    $response   = $response->getBody()->getContents();

                    self::writeLogCDP("Đồng bộ đơn hàng", null, $data, $response, NULL, $order->code ?? null, "SUCCESS", $function_request, $log_find);
                } catch (\GuzzleHttp\Exception\RequestException $ex) {
                    $response = !empty($ex->getResponse()) ? $ex->getResponse()->getBody() : $ex;
                    self::writeLogCDP("Đồng bộ đơn hàng", $param ?? $order, $data, $response, null, $order->code ?? null, "FAILED", $function_request, $log_find);
                }
                return $status  ?? "FAIL";
            }
        } catch (Exception $e) {
            // TM::sendMessage('CDP Order Exception: ', $e);
            self::writeLogCDP("Đồng bộ đơn hàng", $param ?? $order, $data ?? $order, $e, null, $order->code ?? null, "FAILED", $function_request, $log_find);
        }
    }

    /**
     * Đồng bộ khách hàng sang CDP
     * @param $customer
     * @param $function_request  
     * @return int
     * @throws Exception
     */
    public static function pushCustomerCdp($customer, $function_request, $log_find = null)
    {
        try {
            if (self::handle()) {
                $token = self::getToken();
                $client = new Client([
                    'defaults'                          => [
                        RequestOptions::CONNECT_TIMEOUT => 5,
                        RequestOptions::ALLOW_REDIRECTS => true,
                    ],
                    RequestOptions::VERIFY  => false,
                ]);

                if (env('APP_ENV') == 'production') {
                    $mom_code = self::$__MOM_CODE["CUSTOMER_LIVE"];
                } else {
                    $mom_code = self::$__MOM_CODE["CUSTOMER_DEV"];
                }

                $data = [
                    "objectType"               => "custom-model",
                    "source"                   => "Ecommerce",
                    "entries"                  => [
                        [
                            "systemName"       => "NutifoodShop",
                            "momCode"          => $mom_code,
                            "refId"            => $customer->id,
                            "customerId"       => $customer->id,
                            "email"            => $customer->email, // Email
                            "phone"            => $customer->phone, // Số điện thoại
                            "createdAt"        => DateTime::createFromFormat(
                                'd-m-Y H:i:s',
                                date('d-m-Y H:i:s', strtotime($customer->created_at)),
                                new DateTimeZone('Asia/Ho_Chi_Minh')
                            )->getTimestamp() * 1000, // Ngày tạo
                            "updatedAt"        => DateTime::createFromFormat(
                                'd-m-Y H:i:s',
                                date('d-m-Y H:i:s', strtotime($customer->updated_at)),
                                new DateTimeZone('Asia/Ho_Chi_Minh')
                            )->getTimestamp() * 1000, // Ngày cập nhật
                            "customFieldTexts"          => [
                                "customField01" => !empty($customer->profile) ? $customer->profile->full_name : $customer->name, //name
                                "customField02" => !empty($customer->group_code) ? $customer->group_code : "GUEST", // Loại khách hàng
                                "customField03" => null, // Đường
                                "customField05" => !empty($customer->profile->district) ? $customer->profile->district->full_name : (!empty($customer->shipping_address) ? $customer->shipping_address->getDistrict->full_name : null), // District
                                "customField06" => !empty($customer->profile->ward) ? $customer->profile->ward->full_name : (!empty($customer->shipping_address) ? $customer->shipping_address->getWard->full_name : null), // Ward
                                "customField07" => !empty($customer->profile->city) ? $customer->profile->city->full_name : (!empty($customer->shipping_address) ? $customer->shipping_address->getCity->full_name : null), // City
                                "customField09" => !empty($customer->profile) ? $customer->profile->address : (!empty($customer->shipping_address) ? $customer->shipping_address->street_address . ", " . $customer->shipping_address->getWard->full_name . ", " .  $customer->shipping_address->getDistrict->full_name . ", " . $customer->shipping_address->getCity->full_name : null), // Địa chỉ chung
                            ],
                            "customFieldListTexts"      => [
                                "customFieldList01"         => !empty($customer->profile) ? (array)$customer->profile->full_name : $customer->name, // Nick name
                            ],
                            "customFieldTimestamps"         => [
                                "customFieldTimestamp01"    => !empty($customer->profile->birthday) ? DateTime::createFromFormat(
                                    'd-m-Y H:i:s',
                                    date('d-m-Y H:i:s', strtotime($customer->profile->birthday)),
                                    new DateTimeZone('Asia/Ho_Chi_Minh')
                                )->getTimestamp() * 1000 : null, // Ngay sinh
                            ]
                        ]
                    ],
                ];
                try {
                    $response = $client->request("POST", self::handle()->value_5 . "?initData=true", [
                        'headers' => [
                            'Content-Type'      => 'application/json',
                            "TOKEN"             => $token,
                            "API_KEY"           => self::handle()->value_2,
                        ],
                        'body' => json_encode($data)
                    ]);

                    $status     = $response->getStatusCode();
                    $response   = $response->getBody()->getContents();
                    if (!empty(json_decode($response)->success) && json_decode($response)->success == true) {
                        self::writeLogCDP("Đồng bộ khách hàng", null, $data, $response, NULL, $customer->code ?? null, "SUCCESS", $function_request, $log_find);
                    } else {
                        self::writeLogCDP("Đồng bộ khách hàng", $customer, $data, $response, null, $customer->code ?? null, "FAILED", $function_request, $log_find);
                    }
                } catch (\GuzzleHttp\Exception\RequestException $ex) {

                    $response = !empty($ex->getResponse()) ? $ex->getResponse()->getBody() : $ex;
                    self::writeLogCDP("Đồng bộ khách hàng", $customer, $data, $response, null, $customer->code ?? null, "FAILED", $function_request, $log_find);
                }
                return $status  ?? "FAIL";
            }
        } catch (Exception $e) {
            self::writeLogCDP("Đồng bộ khách hàng", $customer, $data ?? $customer, $e, null, $customer->code ?? null, "FAILED", $function_request, $log_find);
        }
    }

    /**
     * Đồng bộ sản phẩm sang CDP
     * @param $product
     * @param $function_request  
     * @return int
     * @throws Exception
     */
    public static function pushProductCdp($product, $function_request, $log_find = null)
    {
        try {
            if (self::handle()) {
                $token = self::getToken();
                $client = new Client([
                    'defaults'                          => [
                        RequestOptions::CONNECT_TIMEOUT => 5,
                        RequestOptions::ALLOW_REDIRECTS => true,
                    ],
                    RequestOptions::VERIFY  => false,
                ]);
                $fileCode           = object_get($product, 'file.code', null);

                $data = [
                    "objectType"               => "product",
                    "source"                   => "Ecommerce",
                    "entries"                  => [
                        [
                            "systemName"                => "NutifoodShop",
                            "momCode"                   => "product", // Mã sản phẩm
                            "productId"                 => $product->id,
                            "sku"                       => $product->code, // Mã SP
                            "name"                      => $product->name, // Name
                            "price"                     => $product->price, // Price
                            "productLink"               => env('APP_URL', "https://nutifoodshop.com") . "/san-pham/" . $product->slug, // Link sản phẩm
                            "reviewLink"                => env('APP_URL', "https://nutifoodshop.com") . "/san-pham/" . $product->slug, // Link đánh giá
                            "imageLink"                 => !empty($fileCode) ? env('GET_FILE_URL') . $fileCode : null, // Image
                            "recordStatus"              => $product->status, // Status
                            "brand"                     => Arr::get($product, 'brand.name', null), // Nhãn hàng
                            "categoryName"              => self::getNameCategory($product->category_ids), // Tên loại sp
                            "customFieldTexts"          => [
                                "customField01"         => !empty($product->createdBy) ?  $product->createdBy->code : null, // Người tạo,
                                "customField02"         => !empty($product->updatedBy) ?  $product->updatedBy->code : null, // Người cập nhật,
                            ],
                            "customFieldTimestamps"         => [
                                "customFieldTimestamp01"    => !empty($product->created_at) ?  DateTime::createFromFormat(
                                    'd-m-Y H:i:s',
                                    date('d-m-Y H:i:s', strtotime($product->created_at)),
                                    new DateTimeZone('Asia/Ho_Chi_Minh')
                                )->getTimestamp() * 1000 : null, // Ngày tạo
                                "customFieldTimestamp02"    => !empty($product->updated_at) ?  DateTime::createFromFormat(
                                    'd-m-Y H:i:s',
                                    date('d-m-Y H:i:s', strtotime($product->updated_at)),
                                    new DateTimeZone('Asia/Ho_Chi_Minh')
                                )->getTimestamp() * 1000 : null, // Ngày cập nhật
                            ]
                        ]
                    ],
                ];
                try {
                    $response = $client->request("POST", self::handle()->value_5 . "?initData=true", [
                        'headers' => [
                            'Content-Type'      => 'application/json',
                            "TOKEN"             => $token,
                            "API_KEY"           => self::handle()->value_2,
                        ],
                        'body' => json_encode($data)
                    ]);

                    $status     = $response->getStatusCode();
                    $response   = $response->getBody()->getContents();

                    if (!empty(json_decode($response)->success) && json_decode($response)->success == true) {
                        self::writeLogCDP("Đồng bộ sản phẩm", null, $data, $response, NULL, $product->code ?? null, "SUCCESS", $function_request, $log_find);
                    } else {
                        self::writeLogCDP("Đồng bộ sản phẩm", $product, $data, $response, null, $product->code ?? null, "FAILED", $function_request, $log_find);
                    }
                } catch (\GuzzleHttp\Exception\RequestException $ex) {

                    $response = !empty($ex->getResponse()) ? $ex->getResponse()->getBody() : $ex;
                    self::writeLogCDP("Đồng bộ sản phẩm", $product, $data, $response, null, $product->code ?? null, "FAILED", $function_request, $log_find);
                }
                return $status  ?? "FAIL";
            }
        } catch (Exception $e) {
            self::writeLogCDP("Đồng bộ sản phẩm", $product, $data ?? $product, $e, null, $product->code ?? null, "FAILED", $function_request, $log_find);
        }
    }


    /**
     * @param $ids
     * @return array|string
     */
    private static function getNameCategory($ids)
    {
        if (empty($ids)) {
            return [];
        }
        $category = Category::model()->select(['name'])->whereIn('id', explode(",", $ids))->get()->toArray();
        $category = array_pluck($category, 'name');
        $category = implode(', ', $category);
        return $category;
    }


    /**
     * Đồng bộ đơn hàng sang CDP
     * @param $order
     * @param $function_request
     * @return int
     * @throws Exception
     */
    public static function pushOldDataOrderCdp(Request $request)
    {
        $input = $request->all();
        $input['status'] = $input['status'] ?? 'COMPLETED';
        $offset = $request->get('offset', 0);
        $limit  = $request->get('limit');
        try {
            $order = Order::model()->where('status', $input['status'])
                ->select(
                    'created_at',
                    'updated_at',
                    'code',
                    'id',
                    'total_info',
                    'canceled_reason',
                    'canceled_reason_admin',
                    'customer_id',
                    'customer_name',
                    'customer_phone',
                    'customer_email',
                    'status_text',
                    'sub_total_price',
                    'total_discount',
                    'ship_fee',
                    'total_price',
                    'updated_by',
                    'created_by',
                    'description',
                    'street_address',
                    'shipping_address_city_name',
                    'shipping_address',
                    'shipping_address_ward_name',
                    'shipping_address_district_name',
                    'delivery_time',
                    'canceled_date'
                );
            if (isset($offset) && is_numeric($offset) && $limit) {
                $order->offset($offset * $limit)->take($limit);
            }

            $order = $order->get();

            foreach ($order as $item) {
                self::pushOrderCdp($item, "CDP@pushOldDataOrderCdp");
            }
        } catch (Exception $e) {
            TM::sendMessage('CDP Push Old Order Exception: ', $e);
        }
        return true;
    }

    public static function pushOldDataCustomerCdp(Request $request)
    {
        try {
            $offset = $request->get('offset', 0);
            $limit = $request->get('limit');
            $user = User::model()->whereIn('group_code', ['HUB', 'TTPP', 'DISTRIBUTOR', 'LEAD', 'GUEST'])
                ->where('is_active', 1);
            if (isset($offset) && is_numeric($offset) && $limit) {
                $user->offset($offset * $limit)->take($limit);
            }
            $user = $user->get();
            foreach ($user as $item) {
                self::pushCustomerCdp($item, "CDP@pushOldDataCustomerCdp");
            }
        } catch (Exception $e) {
            TM::sendMessage('CDP Push Old Customer Exception: ', $e);
        }
        return true;
    }

    public static function pushOldDataProductCdp(Request $request)
    {
        try {
            $product = Product::model()->where('is_active', 1)
                ->where('status', 1)
                ->get();
            foreach ($product as $item) {
                self::pushProductCdp($item, "CDP@pushOldDataProductCdp");
            }
        } catch (Exception $e) {
            TM::sendMessage('CDP Push Old Product Exception: ', $e);
        }
        return true;
    }
}
