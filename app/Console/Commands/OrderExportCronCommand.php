<?php

/**
 * Created by PhpStorm.
 * User: kpistech2
 * Date: 2019-10-16
 * Time: 22:19
 */

namespace App\Console\Commands;

use App\OrderExportCronLogs;
use App\OrderExportCronLogsRP;
use App\OrderExportExcel;
use App\OrderExportExcelRP;
use App\OrderRP;
use App\Product;
use App\ProductRP;
use App\PromotionProgramRP;
use App\PromotionTotalRP;
use App\Supports\TM_Error;
use App\TempOrderExportExcel;
use App\User;
use Illuminate\Console\Command;
use App\UserRP;
use Illuminate\Support\Facades\DB;

class OrderExportCronCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order_export:cron';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Order Export command';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        DB::table('check_process_cron_order')->update(['status' => 1, 'name' => 'Order Export Cron', 'date_run' => date('Y-m-d H:i:s')]);

        ini_set('max_execution_time', 50000);
        ini_set('memory_limit', '-1');
        if (ob_get_contents()) ob_end_clean();
        $time       = date('Y-m-d', time()); // quan.pham
        try {
            $orders     = OrderRP::with(['details', 'details.product.unit', 'details.product', 'vpvirtualaccount', 'store', 'customer', 'distributor', 'distributor.profile', 'distributor.profile.city', 'distributor.profile.district', 'distributor.profile.ward'])
                ->get();
            $i = 0;
            foreach ($orders as $order) {
                $pod_gift           = [];
                $pod_qty_gift       = [];
                $pod_name_gift      = [];
                $shipping_status    = [];
                $promocode          = [];
                $promoname          = [];
                $prodcode           = [];
                $prodc              = [];
                $created_order      = date('Y-m-d', strtotime($order->created_at));
                $status             = ORDER_STATUS_NEW_NAME[$order->status];
                foreach ($order->shippingStatusHistories as $ss) {
                    array_push($shipping_status, $ss->text_status_code);
                }
                foreach ($order->promotionTotals as $promo) {
                    array_push($promocode, $promo->promotion_code);
                    array_push($promoname, $promo->promotion_name);
                    $promotion = PromotionProgramRP::model()->where('code', $promo->promotion_code)->select('act_sale_type', 'act_products', 'act_categories', 'id')->first();
                    if (!empty($promotion)) {
                        if ($promotion->act_sale_type == "percentage") {
                            if (!empty($promotion->act_products)) {
                                foreach (json_decode($promotion->act_products) as $p) {
                                    array_push($prodcode, $p->product_code);
                                }
                            }
                            if (!empty($promotion->act_categories)) {
                                foreach (json_decode($promotion->act_categories) as $c) {
                                    $pro = ProductRP::model()->where(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$c->category_id,%")->select('id', 'code')->get();
                                    foreach ($pro as $prod) {
                                        array_push($prodcode, $prod->code);
                                    }
                                }
                            }
                        }
                        if ($promotion->act_sale_type == "fixed_price") {
                            if (!empty($promotion->act_products)) {
                                foreach (json_decode($promotion->act_products) as $p) {
                                    array_push($prodc, $p->product_code);
                                }
                            }
                            if (!empty($promotion->act_categories)) {
                                foreach (json_decode($promotion->act_categories) as $c) {
                                    $pro = ProductRP::model()->where(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$c->category_id,%")->select('id', 'code')->get();
                                    foreach ($pro as $prod) {
                                        array_push($prodc, $prod->code);
                                    }
                                }
                            }
                        }
                    }
                }
                $order_CK           = 0;
                $order_price_CK     = 0;
                $promotion_total    = PromotionTotalRP::model()->where('order_id', $order->id)->select('id', 'promotion_act_sale_type', 'promotion_act_type', 'promotion_act_price', 'value')->get();
                if (!empty($promotion_total)) {
                    foreach ($promotion_total as $pr_tt) {
                        if ($pr_tt->promotion_act_sale_type == "percentage" && $pr_tt->promotion_act_type == "order_sale_off" || $pr_tt->promotion_act_sale_type == "percentage" && $pr_tt->promotion_act_type == "combo") {
                            $order_CK += (int) $pr_tt->promotion_act_price;
                        }
                        if ($pr_tt->promotion_act_type == "order_sale_off" || $pr_tt->promotion_act_type == "combo") {
                            $order_price_CK += (int) $pr_tt->value;
                        }
                    }
                }
                $dis_coupon = 0;
                $dis_ship   = 0;
                $feeship    = 0;
                if (!empty($order->total_info)) {
                    foreach (json_decode($order->total_info) as $info) {
                        if ($info->code == 'coupon') {
                            $dis_coupon = $info->value;
                        }
                        if ($info->code == 'coupon_delivery') {
                            $dis_ship = $info->value;
                        }
                        if ($info->code == 'fee_ship') {
                            $feeship = $info->value;
                        }
                    }
                }

                if (!empty($dis_ship) && !empty($feeship)) {
                    $ship = ($feeship - $dis_ship <= 0 ? 0 : $feeship - $dis_ship);
                }

                if (empty($dis_ship) && !empty($feeship)) {
                    $ship = $feeship;
                }

                if (empty($feeship) || $order->shipping_method_code == "DEFAULT") {
                    $ship = 0;
                }

                if (!empty($order->free_item)) {
                    foreach (json_decode($order->free_item) as $free) {
                        foreach ($free->text as $item) {
                            array_push($pod_gift, $item->product_code);
                            array_push($pod_name_gift, $item->title_gift ?? $item->product_name);
                            array_push($pod_qty_gift, $item->qty_gift * $free->value);
                        }
                    }
                }
                
                $statusShipping = $order->shippingStatusHistories->first();
                $details        = $order->details;
                foreach ($details as $detail) {
                    $orderStatus     = $detail->orderStatus->pluck(null, 'order_status_code')->toArray();
                    $statusApproved  = $orderStatus[ORDER_STATUS_APPROVED]['created_at'] ?? null;
                    $statusShipping  = $orderStatus[ORDER_STATUS_SHIPPING]['created_at'] ?? null;
                    $statusShipped   = $orderStatus[ORDER_STATUS_SHIPPED]['created_at'] ?? null;
                    $statusCompleted = $orderStatus[ORDER_STATUS_COMPLETED]['created_at'] ?? null;
                    $statusCanceled  = $orderStatus[ORDER_STATUS_CANCELED]['created_at'] ?? null;
                    if ($detail->special_percentage != 0 && $detail->discount != 0) {
                        $special_percentage = $detail->special_percentage;
                    } else {
                        $special_percentage = 100 - round((($detail->total / $detail->qty) / $detail->price) * 100);
                    }
            
                    $dataOrder[]     = [
                        'distributor_id'                => $order->distributor_id,
                        'store_id'                      => $order->store_id,
                        'ship_fee_real'                      => $order->ship_fee_real,
                        'distributor_code'              => !empty($order->distributor_code) ? $order->distributor_code : array_get($order, "distributor.code"),
                        'distributor_name'              => !empty($order->distributor_name) ? $order->distributor_name : array_get($order, "distributor.name"),
                        'customer_code'                 => !empty($order->customer_code) ? $order->customer_code : $order->customer->code,
                        'customer_name'                 => !empty($order->customer_name) ? $order->customer_name : $order->customer->name,
                        'customer_phone'                => !empty($order->customer_phone) ? $order->customer_phone : $order->customer->phone,
                        'customer_email'                => !empty($order->customer_email) ? $order->customer_email : $order->customer->email,
                        'customer_gender'               => !empty($order->customer->profile->gender) ? GENDER_CUSTOMER[$order->customer->profile->gender] : null,
                        'customer_birthday'             => !empty($order->customer->profile->birthday) ? date('Y-m-d', strtotime($order->customer->profile->birthday)) : null,
                        'shipping_cus_name'             => !empty($order->shipping_address_full_name) ? $order->shipping_address_full_name : (!empty($order->customer_name) ? $order->customer_name : $order->customer->name),
                        'shipping_cus_phone'            => !empty($order->shipping_address_phone) ? $order->shipping_address_phone : (!empty($order->customer_phone) ? $order->customer_phone : $order->customer->phone),
                        'shipping_address'              => preg_replace('/[\x00-\x1F\x7F]/', '', $order->shipping_address),
                        'ward'                          => $order->getWard->full_name ?? null,
                        'district'                      => $order->getDistrict->full_name ?? null,
                        'city'                          => $order->getCity->full_name ?? null,
                        'order_code'                    => $order->code,
                        'order_type'                    => $order->customer->group->name ?? null,
                        'order_channel'                 => $order->order_channel ?? null,
                        'status_crm'                    => !empty($order->status_crm) ? ORDER_STATUS_CRM[$order->status_crm] : null,
                        'date_crm'                      => !empty($order->status_crm) &&  $order->status_crm != "PENDING" ? date('Y-m-d H:i:s', strtotime($order->updated_at)) : null,
                        'lading_method'                 => LADING_METHOD[$order->lading_method] ?? null,
                        'order_created_at'              => !empty($order->created_at) ? date('Y-m-d H:i:s', strtotime($order->created_at)) : date('Y-m-d H:i:s', strtotime($order->created_at)), //ngày nhận
                        'updated_date'                  => !empty($statusApproved) ? date('Y-m-d H:i:s', strtotime($statusApproved)) : null, //ngày duyệt
                        'order_created_date'            => !empty($statusShipping) ? date('Y-m-d H:i:s', strtotime($statusShipping)) : null, //ngày tạo lệnh gh
                        'order_shipped_date'            => !empty($statusShipped) ? date('Y-m-d H:i:s', strtotime($statusShipped)) : null, //ngày gh thành công
                        'order_updated_date'            => !empty($statusCompleted) ? date('Y-m-d H:i:s', strtotime($statusCompleted)) : null, //ngày hoàn thành đơn hàng
                        'order_canceled_date'           => !empty($statusCanceled) ? date('Y-m-d H:i:s', strtotime($statusCanceled)) : null, //ngày huỷ nhật đơn
                        'updated_at'                    => date('Y-m-d H:i:s', strtotime($order->updated_at)), //ngày cập nhật
                        'product_code'                  => $detail->product_code,
                        'product_name'                  => $detail->product_name,
                        'product_specification'         => $detail->product->specification->value ?? null,
                        'cad_code'                      => $detail->product->cadcode ?? null,
                        'capacity'                      => $detail->product->capacity ?? null,
                        'cat'                           => $detail->product->cat ?? null,
                        'sub_cat'                       => $detail->product->subcat ?? null,
                        'brand'                         => $detail->product->brand->name ?? null,
                        'qty'                           => array_get($detail, "qty", 0),
                        'product_price'                 => ($detail->price * $detail->qty) - ($detail->discount * $detail->qty) / $detail->qty ?? 0,
                        'product_real_price'            => $detail->price ?? 0,
                        'price_CK'                      => in_array($detail->product_code, $prodcode) ? ($special_percentage / 100) * $detail->price : (($detail->price - ($detail->total / $detail->qty)) > 0 ? $detail->price - ($detail->total / $detail->qty) : 0),
                        
                        'total_price_product'           => $detail->id == 27343 ? 216600 : ($detail->price * $detail->qty) - ($detail->discount * $detail->qty) ?? 0,
                        'discount'                      => $detail['discount'] ?? 0,
                        'payment'                       => $detail->total,
                        'payment_code'                  => $order->payment_code,
                        'payment_method'                => $order->payment_method,
                        'shipping_method_name'          => $order->shipping_method_name,
                        'is_freeship'                   => $order->is_freeship,
                        'payment_status'                => PAYMENT_METHOD_NAME[$order->payment_status] ?? null,
                        'status'                        => $status,
                        'shipping_order_status'         => end($shipping_status),
                        'seller_phone'                  => array_get($order, "customer.seller_phone"),
                        'reference_phone'               => array_get($order, "customer.reference_phone"),
                        'promotion_code'                => implode(", ", $promocode),
                        'promotion_name'                => implode(", ", $promoname),
                        'unit'                          => $detail->product->unit->name ?? null,
                        'age'                           => $detail->product->getAge->name ?? null,
                        'expiry'                        => $detail->product->expiry_date ?? null,
                        'manufacture'                   => $detail->product->getManufacture->name ?? null,
                        'brandy'                        => $detail->product->child_brand_name ?? null,
                        'intrustry'                     => $detail->product->area_name ?? null,
                        'ship_fee_customer'             => $ship,
                        'ship_fee_shop'                 => !empty($dis_ship) ? round($dis_ship) : ($order->is_freeship == 1 ? round($order->ship_fee) : 0),
                        'ship_fee_total'                => $ship,
                        'ward_code'                     => array_get($order, "distributor.profile.district.code") . "_" . array_get($order, "distributor.profile.ward.code"),
                        'ward_name'                     => array_get($order, "distributor.profile.ward.name"),
                        'district_code'                 => array_get($order, "distributor.profile.district.code"),
                        'district_name'                 => array_get($order, "distributor.profile.district.name"),
                        'city_code'                     => "0" . array_get($order, "distributor.profile.city.code"),
                        'city_name'                     => array_get($order, "distributor.profile.city.name"),
                        'ward_ship_code'                => $order->shipping_address_district_code . "_" . $order->shipping_address_ward_code,
                        'district_ship_code'            => $order->shipping_address_district_code,
                        'city_ship_code'                => "0" . $order->shipping_address_city_code,
                        'date_time'                     => (strtotime($time) - strtotime($created_order)) / (60 * 60 * 24),
                        'total_price'                   => $order->total_price,
                        'total_discount'                => $order->total_discount + $order->saving,
                        'seller'                        => $order->seller->name ?? null,
                        'leader'                        => $order->leader->name ?? null,
                        'coupon_code'                   => $order->coupon_code ?? null,
                        'coupon_delivery_code'          => $order->coupon_delivery_code ?? null,
                        'discount_coupon'               => !empty($dis_coupon) ? $dis_coupon : 0,
                        'discount_fee_ship'             => !empty($dis_ship) ? $dis_ship : null,
                        'item_product_code'             => implode(", ", $pod_gift),
                        'item_product_name'             => implode(", ", $pod_name_gift),
                        'item_product_qty'              => implode(", ", $pod_qty_gift),
                        'special_percentage'            =>  $detail->id == 27343 ? 10  : (in_array($detail->product_code, $prodcode) ? $special_percentage  : 0),
                        'product_type'                  => !empty($detail->product->type) && $detail->product->type == "PRODUCT" ? "Thành Phẩm" : (!empty($detail->product->type) && $detail->product->type == "GIFT" ? "Thành phẩm quà tặng" : "POSM"),
                        'customer_bank'                 => !empty($order->vpvirtualaccount2->collect_ammount) ? $order->vpvirtualaccount2->collect_ammount : null,
                        'customer_bank_code'            => !empty($order->vpvirtualaccount2->master_account_number) ? $order->vpvirtualaccount2->master_account_number : null,
                        'virtual_account_number'        => !empty($order->vpvirtualaccount2->virtual_account_number) ? $order->vpvirtualaccount2->virtual_account_number : null,
                        'virtual_account'               => !empty($order->payment_code) ? $order->payment_code : null,
                        'shipping_method_name'          => !empty($order->shipping_method_name) ? $order->shipping_method_name : null,
                        'shipping_method_code'          => $order->shipping_method_code == "DEFAULT" ? "NTF" : $order->shipping_method_code,
                        'cancel_reason'                 => !empty($order->canceled_reason_admin) ? $order->canceled_reason_admin : (!empty(json_decode($order->canceled_reason)) ? json_decode($order->canceled_reason)->value : $order->canceled_reason),
                        'tax_product'                   => !empty($detail->product->tax) ? $detail->product->tax : null,
                        'cad_code_sub_cat'              => !empty($detail->product->cad_code_subcat) ? $detail->product->cad_code_subcat : null,
                        'cad_code_brand'                => !empty($detail->product->cad_code_brand) ? $detail->product->cad_code_brand : null,
                        'cad_code_brandy'               => !empty($detail->product->cad_code_brandy) ? $detail->product->cad_code_brandy : null,
                        'invoice_company_address'       => !empty($order->invoice_company_address) ? $order->invoice_company_address : null,
                        'invoice_company_name'          => !empty($order->invoice_company_name) ? $order->invoice_company_name : null,
                        'invoice_tax'                   => !empty($order->invoice_tax) ? $order->invoice_tax : null,
                        'invoice_code'                  => !empty($order->invoice_company_name) ? $order->code : null,
                        'status_shipping'               => !empty($statusShipping) ? $statusShipping : null,
                        'payment_method'                => !empty($order->payment_method) ? PAYMENT_METHOD_NAME[$order->payment_method] : null,
                        'payment_status'                => $order->payment_status != 1 ? "Chưa thanh toán" : "Đã thanh toán",
                        'order_CK'                      => !empty($order_CK) ? $order_CK : 0,
                        'order_price_CK'                => $detail->id == 27343 ? 28200 : (!empty($order_price_CK) ? $order_price_CK : 0),
                        'division'                      => !empty($detail->product->division) ? $detail->product->division : null,
                        'source'                        => !empty($detail->product->source) ? $detail->product->source : null,
                        'packing'                       => !empty($detail->product->packing) ? $detail->product->packing : null,
                        'p_sku'                         => !empty($detail->product->p_sku) ? $detail->product->p_sku : null,
                        'p_sku_name'                    => !empty($detail->product->sku_name) ? $detail->product->sku_name : null,
                        'sku_standard'                  => !empty($detail->product->sku_standard) ? $detail->product->sku_standard : null,
                        'p_type'                        => !empty($detail->product->p_type) ? $detail->product->p_type : null,
                        'p_attribute'                   => !empty($detail->product->p_attribute) ? $detail->product->p_attribute : null,
                        'p_variant'                     => !empty($detail->product->p_variant) ? $detail->product->p_variant : null,
                        'p_variant'                     => !empty($detail->product->p_variant) ? $detail->product->p_variant : null,
                        'total_weight'                  => !empty($order->total_weight) ? $order->total_weight : null,
                        'total_km'                      => !empty($order->intersection_distance) ? $order->intersection_distance : null,
                    ];
                }
                if (!empty($order->free_item)) {

                    foreach (json_decode($order->free_item) as $free) {
                        foreach ($free->text as $item) {
                            $pr = ProductRP::find($item->product_id);
                            $dataOrder[]     = [
                                'stt'                       => ++$i,
                                'distributor_id'                => $order->distributor_id,
                                'store_id'                      => $order->store_id,
                                'distributor_code'          => !empty($order->distributor_code) ? $order->distributor_code : array_get($order, "distributor.code"),
                                'distributor_name'          => !empty($order->distributor_name) ? $order->distributor_name : array_get($order, "distributor.name"),
                                'customer_code'             => $order->customer->code,
                                'customer_name'             => !empty($order->customer_name) ? $order->customer_name : $order->customer->name,
                                'customer_phone'            => !empty($order->customer_phone) ? $order->customer_phone : $order->customer->phone,
                                'customer_email'            => !empty($order->customer_email) ? $order->customer_email : $order->customer->email,
                                'customer_gender'           => !empty($order->customer->profile->gender) ? GENDER_CUSTOMER[$order->customer->profile->gender] : null,
                                'customer_birthday'         => !empty($order->customer->profile->birthday) ? date('Y-m-d', strtotime($order->customer->profile->birthday)) : null,
                                'shipping_cus_name'         => !empty($order->shipping_address_full_name) ? $order->shipping_address_full_name : (!empty($order->customer_name) ? $order->customer_name : $order->customer->name),
                                'shipping_cus_phone'        => !empty($order->shipping_address_phone) ? $order->shipping_address_phone : (!empty($order->customer_phone) ? $order->customer_phone : $order->customer->phone),
                                'shipping_address'          => preg_replace('/[\x00-\x1F\x7F]/', '', $order->shipping_address),
                                'ward'                      => $order->getWard->full_name ?? null,
                                'district'                  => $order->getDistrict->full_name ?? null,
                                'city'                      => $order->getCity->full_name ?? null,
                                'order_code'                => $order->code,
                                'order_type'                => $order->customer->group->name ?? null,
                                'order_channel'             => $order->order_channel ?? null,
                                'status_crm'                => !empty($order->status_crm) ? ORDER_STATUS_CRM[$order->status_crm] : null,
                                'date_crm'                  => !empty($order->status_crm) &&  $order->status_crm != "PENDING" ? date('Y-m-d H:i:s', strtotime($order->updated_at)) : null,
                                'lading_method'             => LADING_METHOD[$order->lading_method] ?? null,
                                'order_created_at'          => !empty($order->created_at) ? date('Y-m-d H:i:s', strtotime($order->created_at)) : date('Y-m-d H:i:s', strtotime($order->created_at)), //ngày nhận
                                'updated_date'              => !empty($statusApproved) ? date('Y-m-d H:i:s', strtotime($statusApproved)) : null, //ngày duyệt
                                'order_created_date'        => !empty($statusShipping) ? date('Y-m-d H:i:s', strtotime($statusShipping)) : null, //ngày tạo lệnh gh
                                'order_shipped_date'        => !empty($statusShipped) ? date('Y-m-d H:i:s', strtotime($statusShipped)) : null, //ngày gh thành công
                                'order_updated_date'        => !empty($statusCompleted) ? date('Y-m-d H:i:s', strtotime($statusCompleted)) : null, //ngày hoàn thành đơn hàng
                                'order_canceled_date'       => !empty($statusCanceled) ? date('Y-m-d H:i:s', strtotime($statusCanceled)) : null, //ngày huỷ nhật đơn
                                'updated_at'                => date('Y-m-d H:i:s', strtotime($order->updated_at)), //ngày cập nhật
                                'product_code'              => $pr->code, //$detail->product_code,
                                'product_name'              => $pr->name, //$detail->product_name,
                                'product_specification'     => !empty($item->specification_value) ? round($item->specification_value) : ($pr->specification->value ?? null),
                                'cad_code'                  => $pr->cadcode ?? null,
                                'capacity'                  => $pr->capacity ?? null,
                                'cat'                       => $pr->cat ?? null,
                                'sub_cat'                   => $pr->subcat ?? null,
                                'brand'                     => $pr->brand->name ?? null,
                                'qty'                       => $item->qty_gift * $free->value, //array_get($detail, "qty", 0),
                                'product_price'             => 0,
                                'product_real_price'        => 0,
                                'price_CK'                  => 0,
                                'total_price_product'       => 0,
                                'discount'                  => 0,
                                'payment'                   => 0,
                                'payment_code'              => $order->payment_code,
                                'payment_method'            => $order->payment_method,
                                'shipping_method_name'      => $order->shipping_method_name,
                                'is_freeship'               => $order->is_freeship,
                                'payment_status'            => PAYMENT_METHOD_NAME[$order->payment_status] ?? null,
                                'status'                    => $status,
                                'shipping_order_status'     => end($shipping_status),
                                'seller_phone'              => array_get($order, "customer.seller_phone"),
                                'reference_phone'           => array_get($order, "customer.reference_phone"),
                                'promotion_code'            => $free->code,
                                'promotion_name'            => $free->title,
                                'unit'                      => !empty($item->unit_name) ? ($item->unit_name) : ($pr->unit->name ?? null),
                                'age'                       => $pr->getAge->name ?? null,
                                'expiry'                    => $pr->expiry_date ?? null,
                                'manufacture'               => $pr->getManufacture->name ?? null,
                                'brandy'                    => $pr->child_brand_name ?? null,
                                'intrustry'                 => $pr->area_name ?? null,
                                'ship_fee_customer'         => $ship,
                                'ship_fee_shop'             => !empty($dis_ship) ? round($dis_ship) : ($order->is_freeship == 1 ? round($order->ship_fee) : 0),
                                'ship_fee_total'            => $ship,
                                'ward_code'                 => array_get($order, "distributor.profile.district.code") . "_" . array_get($order, "distributor.profile.ward.code"),
                                'ward_name'                 => array_get($order, "distributor.profile.ward.name"),
                                'district_code'             => array_get($order, "distributor.profile.district.code"),
                                'district_name'             => array_get($order, "distributor.profile.district.name"),
                                'city_code'                 => "0" . array_get($order, "distributor.profile.city.code"),
                                'city_name'                 => array_get($order, "distributor.profile.city.name"),
                                'ward_ship_code'            => $order->shipping_address_district_code . "_" . $order->shipping_address_ward_code,
                                'district_ship_code'        => $order->shipping_address_district_code,
                                'city_ship_code'            => "0" . $order->shipping_address_city_code,
                                'date_time'                 => (strtotime($time) - strtotime($created_order)) / (60 * 60 * 24),
                                'total_price'               => $order->total_price,
                                'total_discount'            => $order->total_discount + $order->saving,
                                'seller'                    => $order->seller->name ?? null,
                                'leader'                    => $order->leader->name ?? null,
                                'coupon_code'               => $order->coupon_code ?? null,
                                'coupon_delivery_code'      => $order->coupon_delivery_code ?? null,
                                'discount_coupon'           => !empty($dis_coupon) ? $dis_coupon : 0,
                                'discount_fee_ship'         => !empty($dis_ship) ? $dis_ship : null,
                                'item_product_code'         => 0,
                                'item_product_name'         => 0,
                                'item_product_qty'          => 0,
                                'special_percentage'        => 0,
                                'product_type'              => $pr->type == "POSM" ? "POSM" : "Thành phẩm khuyến mãi",
                                'customer_bank'             => !empty($order->vpvirtualaccount2->collect_ammount) ? $order->vpvirtualaccount2->collect_ammount : null,
                                'customer_bank_code'        => !empty($order->vpvirtualaccount2->master_account_number) ? $order->vpvirtualaccount2->master_account_number : null,
                                'virtual_account_number'    => !empty($order->vpvirtualaccount2->virtual_account_number) ? $order->vpvirtualaccount2->virtual_account_number : null,
                                'virtual_account'           => !empty($order->payment_code) ? $order->payment_code : null,
                                'shipping_method_name'      => !empty($order->shipping_method_name) ? $order->shipping_method_name : null,
                                'shipping_method_code'      => $order->shipping_method_code == "DEFAULT" ? "NTF" : $order->shipping_method_code,
                                'cancel_reason'             => !empty($order->canceled_reason_admin) ? $order->canceled_reason_admin : (!empty(json_decode($order->canceled_reason)) ? json_decode($order->canceled_reason)->value : $order->canceled_reason),
                                'tax_product'               => 0,
                                'cad_code_sub_cat'          => !empty($pr->cad_code_subcat) ? $pr->cad_code_subcat : null,
                                'cad_code_brand'            => !empty($pr->cad_code_brand) ? $pr->cad_code_brand : null,
                                'cad_code_brandy'           => !empty($pr->cad_code_brandy) ? $pr->cad_code_brandy : null,
                                'invoice_company_address'   => !empty($order->invoice_company_address) ? $order->invoice_company_address : null,
                                'invoice_company_name'      => !empty($order->invoice_company_name) ? $order->invoice_company_name : null,
                                'invoice_tax'               => !empty($order->invoice_tax) ? $order->invoice_tax : null,
                                'invoice_code'              => !empty($order->invoice_company_name) ? $order->code : null,
                                'status_shipping'           => !empty($statusShipping) ? $statusShipping : null,
                                'payment_method'            => !empty($order->payment_method) ? PAYMENT_METHOD_NAME[$order->payment_method] : null,
                                'payment_status'            => $order->payment_status != 1 ? "Chưa thanh toán" : "Đã thanh toán",
                                'order_CK'                  => !empty($order_CK) ? $order_CK : 0,
                                'division'                  => !empty($pr->division) ? $pr->division : null,
                                'source'                    => !empty($pr->source) ? $pr->source : null,
                                'packing'                   => !empty($pr->packing) ? $pr->packing : null,
                                'p_sku'                     => !empty($pr->p_sku) ? $pr->p_sku : null,
                                'p_sku_name'                => !empty($pr->sku_name) ? $pr->sku_name : null,
                                'sku_standard'              => !empty($pr->sku_standard) ? $pr->sku_standard : null,
                                'p_type'                    => !empty($pr->p_type) ? $pr->p_type : null,
                                'p_attribute'               => !empty($pr->p_attribute) ? $pr->p_attribute : null,
                                'p_variant'                 => !empty($pr->p_variant) ? $pr->p_variant : null,
                                'total_weight'              => !empty($order->total_weight) ? $order->total_weight : null,
                                'total_km'                  => !empty($order->intersection_distance) ? $order->intersection_distance : null,
                            ];
                        }
                    }
                }
            }

            OrderExportExcel::truncate();
            TempOrderExportExcel::truncate();
            $orderCheck         = OrderExportExcel::model()->whereNull('deleted_at')->get();
            $tempOrderCheck     = TempOrderExportExcel::model()->whereNull('deleted_at')->get();
            /** @var \PDO $pdo */
            $pdo = DB::getPdo();
            $now = date('Y-m-d H:i:s', time());
            $queryContent = "";
            if (count($orderCheck) == 0  && count($tempOrderCheck) == 0) {

                DB::beginTransaction();
                $queryHeader = "INSERT INTO `order_export_excel` (" .
                    "`distributor_code`, " .
                    "`distributor_name`, " .
                    "`customer_code`, " .
                    "`customer_name`, " .
                    "`customer_phone`, " .
                    "`customer_email`, " .
                    "`customer_gender`, " .
                    "`customer_birthday`, " .
                    "`shipping_cus_name`, " .
                    "`shipping_cus_phone`, " .
                    "`shipping_address`, " .
                    "`ward`, " .
                    "`district`, " .
                    "`city`, " .
                    "`order_code`, " .
                    "`order_type`, " .
                    "`order_channel`, " .
                    "`status_crm`, " .
                    "`date_crm`, " .
                    "`lading_method`, " .
                    "`created_at`, " .
                    "`updated_date`, " .
                    "`order_created_date`, " .
                    "`order_shipped_date`, " .
                    "`order_updated_date`, " .
                    "`order_canceled_date`, " .
                    "`updated_at`, " .
                    "`product_code`, " .
                    "`product_name`, " .
                    "`product_specification`, " .
                    "`cad_code`, " .
                    "`capacity`, " .
                    "`cat`, " .
                    "`sub_cat`, " .
                    "`brand`, " .
                    "`qty`, " .
                    "`product_price`, " .
                    "`product_real_price`, " .
                    "`price_CK`, " .
                    "`total_price_product`, " .
                    "`discount`, " .
                    "`payment`, " .
                    "`payment_code`, " .
                    "`payment_method`, " .
                    "`shipping_method_name`, " .
                    "`is_freeship`, " .
                    "`payment_status`, " .
                    "`status`, " .
                    "`shipping_order_status`, " .
                    "`seller_phone`, " .
                    "`reference_phone`, " .
                    "`promotion_code`, " .
                    "`promotion_name`, " .
                    "`unit`, " .
                    "`age`, " .
                    "`expiry`, " .
                    "`manufacture`, " .
                    "`brandy`, " .
                    "`intrustry`, " .
                    "`ship_fee_customer`, " .
                    "`ship_fee_shop`, " .
                    "`ship_fee_total`, " .
                    "`ward_code`, " .
                    "`ward_name`, " .
                    "`district_code`, " .
                    "`district_name`, " .
                    "`city_code`, " .
                    "`city_name`, " .
                    "`city_ship_code`, " .
                    "`ward_ship_code`, " .
                    "`district_ship_code`, " .
                    "`date_time`, " .
                    "`total_price`, " .
                    "`total_discount`, " .
                    "`seller`, " .
                    "`leader`, " .
                    "`coupon_code`, " .
                    "`coupon_delivery_code`, " .
                    "`discount_coupon`, " .
                    "`discount_fee_ship`, " .
                    "`item_product_code`, " .
                    "`item_product_name`, " .
                    "`item_product_qty`, " .
                    "`special_percentage`, " .
                    "`product_type`, " .
                    "`customer_bank`, " .
                    "`customer_bank_code`, " .
                    "`virtual_account_number`, " .
                    "`virtual_account`, " .
                    "`shipping_method_code`, " .
                    "`cancel_reason`, " .
                    "`tax_product`, " .
                    "`cad_code_sub_cat`, " .
                    "`cad_code_brand`, " .
                    "`cad_code_brandy`, " .
                    "`invoice_company_address`, " .
                    "`invoice_company_name`, " .
                    "`invoice_tax`, " .
                    "`invoice_code`, " .
                    "`order_CK`, " .
                    "`order_price_CK`, " .
                    "`division`, " .
                    "`source`, " .
                    "`packing`, " .
                    "`p_sku`, " .
                    "`p_sku_name`, " .
                    "`sku_standard`, " .
                    "`p_type`, " .
                    "`p_attribute`, " .
                    "`p_variant`, " .
                    "`total_weight`, " .
                    "`total_km`, " .
                    "`order_created_at`, " .
                    "`store_id`, " .
                    "`ship_fee_real`, " .
                    "`distributor_id`,
                ) VALUES ";
                foreach ($dataOrder as $value) {
                    $birthday           = !empty($value['customer_birthday'])   ? $pdo->quote($value['customer_birthday']) : "null";
                    $order_created_date = !empty($value['order_created_date'])  ? $pdo->quote($value['order_created_date']) :  "null";
                    $updated_date       = !empty($value['updated_date'])        ? $pdo->quote($value['updated_date']) :  "null";
                    $order_shipped_date = !empty($value['order_shipped_date'])  ? $pdo->quote($value['order_shipped_date']) :  "null";
                    $order_updated_date = !empty($value['order_updated_date'])  ? $pdo->quote($value['order_updated_date']) :  "null";
                    $order_canceled_date = !empty($value['order_canceled_date'])  ? $pdo->quote($value['order_canceled_date']) :  "null";
                    $order_created_at   = !empty($value['order_created_at'])    ? $pdo->quote($value['order_created_at']) :  "null";
                    $updated_at         = !empty($value['updated_at'])          ? $pdo->quote($value['updated_at']) :  "null";
                    $discount           = !empty($value['discount'])            ? $pdo->quote($value['discount']) :  "null";
                    $payment            = !empty($value['payment'])             ? $pdo->quote($value['payment']) :  "null";
                    $is_freeship        = !empty($value['is_freeship'])         ? $pdo->quote($value['is_freeship']) :  "null";
                    $ship_fee_customer  = !empty($value['ship_fee_customer']) ? $pdo->quote($value['ship_fee_customer']) : 0;
                    $ship_fee_shop      = !empty($value['ship_fee_shop'])       ? $pdo->quote($value['ship_fee_shop']) :  0;
                    $ship_fee_total     = !empty($value['ship_fee_total']) ? $pdo->quote($value['ship_fee_total']) : 0;
                    $total_price        = !empty($value['total_price'])         ? $pdo->quote($value['total_price']) :  "null";
                    $total_discount     = !empty($value['total_discount'])      ? $pdo->quote($value['total_discount']) :  "null";
                    $discount_coupon    = !empty($value['discount_coupon'])     ? $pdo->quote($value['discount_coupon']) :  "null";
                    $discount_fee_ship  = !empty($value['discount_fee_ship'])   ? $pdo->quote($value['discount_fee_ship']) :  "null";
                    $special_percentage = !empty($value['special_percentage'])  ? $pdo->quote($value['special_percentage']) :  "null";
                    $order_CK           = !empty($value['order_CK'])            ? $pdo->quote($value['order_CK']) :  "null";
                    $order_price_CK     = !empty($value['order_price_CK'])      ? $pdo->quote($value['order_price_CK']) :  "null";
                    $total_weight       = !empty($value['total_weight'])        ? $pdo->quote($value['total_weight']) :  "null";
                    $total_km           = !empty($value['total_km'])            ? $pdo->quote($value['total_km']) :  "null";
                    $date_crm           = !empty($value['date_crm'])            ? $pdo->quote($value['date_crm']) :  "null";
                    $customer_phone     = !empty($value['customer_phone'])      ? $pdo->quote($value['customer_phone']) :  "null";
                    $customer_email     = !empty($value['customer_email'])      ? $pdo->quote($value['customer_email']) :  "null";
                    $payment_code       = !empty($value['payment_code'])        ? $pdo->quote($value['payment_code']) :  "null";
                    $reference_phone    = !empty($value['reference_phone'])     ? $pdo->quote($value['reference_phone']) :  "null";
                    $seller_phone       = !empty($value['seller_phone'])        ? $pdo->quote($value['seller_phone']) :  "null";
                    $promotion_code     = !empty($value['promotion_code'])      ? $pdo->quote($value['promotion_code']) :  "null";
                    $promotion_name     = !empty($value['promotion_name'])      ? $pdo->quote($value['promotion_name']) :  "null";
                    $age                = !empty($value['age'])                 ? $pdo->quote($value['age']) :  "null";
                    $expiry             = !empty($value['expiry'])              ? $pdo->quote($value['expiry']) :  "null";
                    $brandy             = !empty($value['brandy'])              ? $pdo->quote($value['brandy']) :  "null";
                    $seller             = !empty($value['seller'])              ? $pdo->quote($value['seller']) :  "null";
                    $leader             = !empty($value['leader'])              ? $pdo->quote($value['leader']) :  "null";
                    $coupon_code        = !empty($value['coupon_code'])         ? $pdo->quote($value['coupon_code']) :  "null";
                    $coupon_delivery_code       = !empty($value['coupon_delivery_code'])        ? $pdo->quote($value['coupon_delivery_code']) :  "null";
                    $customer_bank              = !empty($value['customer_bank'])               ? $pdo->quote($value['customer_bank']) :  "null";
                    $customer_bank_code         = !empty($value['customer_bank_code'])          ? $pdo->quote($value['customer_bank_code']) :  "null";
                    $virtual_account_number     = !empty($value['virtual_account_number'])      ? $pdo->quote($value['virtual_account_number']) :  "null";
                    $virtual_account            = !empty($value['virtual_account'])             ? $pdo->quote($value['virtual_account']) :  "null";
                    $invoice_company_address    = !empty($value['invoice_company_address'])     ? $pdo->quote($value['invoice_company_address']) :  "null";
                    $invoice_company_name       = !empty($value['invoice_company_name'])        ? $pdo->quote($value['invoice_company_name']) :  "null";
                    $invoice_tax                = !empty($value['invoice_tax'])                 ? $pdo->quote($value['invoice_tax']) :  "null";
                    $invoice_code               = !empty($value['invoice_code'])                ? $pdo->quote($value['invoice_code']) :  "null";
                    $p_type                     = !empty($value['p_type'])                      ? $pdo->quote($value['p_type']) :  "null";
                    $p_attribute                = !empty($value['p_attribute'])                 ? $pdo->quote($value['p_attribute']) :  "null";
                    $p_variant                  = !empty($value['p_variant'])                   ? $pdo->quote($value['p_variant']) :  "null";
                    $cancel_reason              = !empty($value['cancel_reason'])               ? $pdo->quote($value['cancel_reason']) :  "null";
                    $tax_product                = !empty($value['tax_product'])                 ? $pdo->quote($value['tax_product']) :  "null";
                    $shipping_order_status      = !empty($value['shipping_order_status'])       ? $pdo->quote($value['shipping_order_status']) :  "null";
                    // $customer_code              = !empty($value['customer_code'])               ? $pdo->quote($value['customer_code']) :  "null";
                    $source                     = !empty($value['source'])                      ? $pdo->quote($value['source']) :  "null";
                    $packing                    = !empty($value['packing'])                     ? $pdo->quote($value['packing']) :  "null";
                    $order_channel              = !empty($value['order_channel'])               ? $pdo->quote($value['order_channel']) :  "null";
                    $status_crm                 = !empty($value['status_crm'])                  ? $pdo->quote($value['status_crm']) :  "null";
                    $sub_cat                    = !empty($value['sub_cat'])                     ? $pdo->quote($value['sub_cat']) :  "null";
                    $lading_method              = !empty($value['lading_method'])               ? $pdo->quote($value['lading_method']) :  "null";
                    $product_specification      = !empty($value['product_specification'])       ? $pdo->quote($value['product_specification']) :  "null";
                    $cad_code                   = !empty($value['cad_code'])                    ? $pdo->quote($value['cad_code']) :  "null";
                    $capacity                   = !empty($value['capacity'])                    ? $pdo->quote($value['capacity']) :  "null";
                    $cat                        = !empty($value['cat'])                         ? $pdo->quote($value['cat']) :  "null";
                    $unit                       = !empty($value['unit'])                        ? $pdo->quote($value['unit']) :  "null";
                    $manufacture                = !empty($value['manufacture'])                 ? $pdo->quote($value['manufacture']) :  "null";
                    $intrustry                  = !empty($value['intrustry'])                   ? $pdo->quote($value['intrustry']) :  "null";
                    $cad_code_sub_cat           = !empty($value['cad_code_sub_cat'])            ? $pdo->quote($value['cad_code_sub_cat']) :  "null";
                    $cad_code_brand             = !empty($value['cad_code_brand'])              ? $pdo->quote($value['cad_code_brand']) :  "null";
                    $cad_code_brandy            = !empty($value['cad_code_brandy'])              ? $pdo->quote($value['cad_code_brandy']) :  "null";
                    $division                   = !empty($value['division'])                    ? $pdo->quote($value['division']) :  "null";
                    $p_sku                      = !empty($value['p_sku'])                       ? $pdo->quote($value['p_sku']) :  "null";
                    $p_sku_name                 = !empty($value['p_sku_name'])                  ? $pdo->quote($value['p_sku_name']) :  "null";
                    $sku_standard               = !empty($value['sku_standard'])                ? $pdo->quote($value['sku_standard']) :  "null";
                    $shipping_method_code       = !empty($value['shipping_method_code'])        ? $pdo->quote($value['shipping_method_code']) :  "null";
                    $shipping_method_name       = !empty($value['shipping_method_name'])        ? $pdo->quote($value['shipping_method_name']) :  "null";
                    $shipping_cus_name          = !empty($value['shipping_cus_name'])           ? $pdo->quote($value['shipping_cus_name']) :  "null";
                    $shipping_cus_phone         = !empty($value['shipping_cus_phone'])          ? $pdo->quote($value['shipping_cus_phone']) :  "null";
                    $shipping_address           = !empty($value['shipping_address'])            ? $pdo->quote($value['shipping_address']) :  "null";
                    $brand                      = !empty($value['brand'])                       ? $pdo->quote($value['brand']) :  "null";
                    $payment_status             = !empty($value['payment_status'])              ? $pdo->quote($value['payment_status']) :  "null";
                    $product_type               = !empty($value['product_type'])                ? $pdo->quote($value['product_type']) :  "null";
                    $ship_fee_real               = !empty($value['ship_fee_real'])                ? $pdo->quote($value['ship_fee_real']) :  "null";
                    $queryContent .=
                        "(" .
                        $pdo->quote($value['distributor_code']) . "," .
                        $pdo->quote($value['distributor_name']) . "," .
                        $pdo->quote($value['customer_code']) . "," .
                        $pdo->quote($value['customer_name']) . "," .
                        $customer_phone . "," .
                        $customer_email . "," .
                        $pdo->quote($value['customer_gender']) . "," .
                        $birthday . "," .
                        $shipping_cus_name . "," .
                        $shipping_cus_phone . "," .
                        $shipping_address . "," .
                        $pdo->quote($value['ward']) . "," .
                        $pdo->quote($value['district']) . "," .
                        $pdo->quote($value['city']) . "," .
                        $pdo->quote($value['order_code'])  . "," .
                        $pdo->quote($value['order_type'])  . "," .
                        $order_channel . "," .
                        $status_crm . "," .
                        $date_crm . "," .
                        $lading_method . "," .
                        $pdo->quote($now) . "," .
                        $updated_date . "," .
                        $order_created_date . "," .
                        $order_shipped_date . "," .
                        $order_updated_date . "," .
                        $order_canceled_date . "," .
                        $updated_at . "," .
                        $pdo->quote($value['product_code']) . "," .
                        $pdo->quote($value['product_name']) . "," .
                        $product_specification . "," .
                        $cad_code . "," .
                        $capacity . "," .
                        $cat . "," .
                        $sub_cat . "," .
                        $brand . "," .
                        $pdo->quote($value['qty']) . "," .
                        $pdo->quote($value['product_price']) . "," .
                        $pdo->quote($value['product_real_price']) . "," .
                        $pdo->quote($value['price_CK']) . "," .
                        $pdo->quote($value['total_price_product']) . "," .
                        $discount . "," .
                        $payment . "," .
                        $payment_code . "," .
                        $pdo->quote($value['payment_method']) . "," .
                        $shipping_method_name . "," .
                        $is_freeship . "," .
                        $payment_status . "," .
                        $pdo->quote($value['status']) . "," .
                        $shipping_order_status . "," .
                        $seller_phone . "," .
                        $reference_phone . "," .
                        $promotion_code . "," .
                        $promotion_name . "," .
                        $unit . "," .
                        $age . "," .
                        $expiry . "," .
                        $manufacture . "," .
                        $brandy . "," .
                        $intrustry . "," .
                        $ship_fee_customer . "," .
                        $ship_fee_shop . "," .
                        $ship_fee_total . "," .
                        $pdo->quote($value['ward_code']) . "," .
                        $pdo->quote($value['ward_name']) . "," .
                        $pdo->quote($value['district_code']) . "," .
                        $pdo->quote($value['district_name']) . "," .
                        $pdo->quote($value['city_code']) . "," .
                        $pdo->quote($value['city_name']) . "," .
                        $pdo->quote($value['city_ship_code']) . "," .
                        $pdo->quote($value['ward_ship_code']) . "," .
                        $pdo->quote($value['district_ship_code']) . "," .
                        $pdo->quote($value['date_time']) . "," .
                        $total_price . "," .
                        $total_discount . "," .
                        $seller . "," .
                        $leader . "," .
                        $coupon_code . "," .
                        $coupon_delivery_code . "," .
                        $discount_coupon . "," .
                        $discount_fee_ship . "," .
                        $pdo->quote($value['item_product_code']) . "," .
                        $pdo->quote($value['item_product_name']) . "," .
                        $pdo->quote($value['item_product_qty']) . "," .
                        $special_percentage . "," .
                        $product_type . "," .
                        $customer_bank . "," .
                        $customer_bank_code . "," .
                        $virtual_account_number . "," .
                        $virtual_account . "," .
                        $shipping_method_code . "," .
                        $cancel_reason . "," .
                        $tax_product . "," .
                        $cad_code_sub_cat . "," .
                        $cad_code_brand . "," .
                        $cad_code_brandy . "," .
                        $invoice_company_address . "," .
                        $invoice_company_name . "," .
                        $invoice_tax . "," .
                        $invoice_code . "," .
                        $order_CK . "," .
                        $order_price_CK . "," .
                        $division . "," .
                        $source . "," .
                        $packing . "," .
                        $p_sku . "," .
                        $p_sku_name . "," .
                        $sku_standard . "," .
                        $p_type . "," .
                        $p_attribute . "," .
                        $p_variant . "," .
                        $total_weight . "," .
                        $total_km . "," .
                        $order_created_at . "," .
                        $pdo->quote($value['store_id']) . "," .
                        $pdo->quote($value['ship_fee_real']) . "," .
                        $pdo->quote($value['distributor_id']) .
                        "),";
                }
                if (!empty($queryContent)) {
                    $queryUpdate = $queryHeader . (trim($queryContent, ", "));
                    DB::statement($queryUpdate);
                }
                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            TM_Error::handle($e);
            DB::table('check_process_cron_order')
            ->where('name','Order Export Cron')
            ->update(['status' => 0]);
            return $this->writeLog('INSERT EXPORT ORDERS', $e, 'insert', "");
        }
        DB::table('check_process_cron_order')
        ->where('name','Order Export Cron')
        ->update(['status' => 0]);
    }

    private function writeLog($code, $message = null, $function, $params = null)
    {
        OrderExportCronLogs::create([
            'code'       => $code,
            'params'     => json_encode($params),
            'message'    => json_encode($message),
            'function'   => $function,
        ]);
        return true;
    }

    public function parsePriceByProducts($product_code, $price, $promotion = null)
    {
        if (empty($promotion)) {
            $promotion = $this->getPromotion();
        }

        $product = ProductRP::model()->where('code', $product_code)->first();

        if ($promotion->act_type == 'sale_off_on_products') {
            if (!empty($promotion->act_products) && $promotion->act_products != "[]") {
                $act_products = json_decode($promotion->act_products);

                $promo_prod = array_pluck($act_products, 'product_code');
                $check_prod = array_search($product->code, $promo_prod);
                if (is_numeric($check_prod)) {
                    if ($promotion->act_sale_type == 'percentage') {
                        if (!empty($act_products[$check_prod]->discount)) {
                            return $price * ($act_products[$check_prod]->discount / 100);
                        }
                        if (empty($act_products[$check_prod]->discount)) {
                            return 0;
                        }
                    }
                    return $act_products[$check_prod]->discount ?? 0;
                }
            }
        }

        if ($promotion->act_type == 'sale_off_on_categories') {
            if (!empty($promotion->act_categories) && $promotion->act_categories != "[]") {
                foreach (json_decode($promotion->act_categories) as $act_category) {
                    $check = array_intersect(explode(',', $act_category->category_id), explode(',', $product->category_ids));
                    if (!empty($check)) {
                        if ($promotion->act_sale_type == 'percentage') {
                            if (!empty($act_category->discount)) {
                                return $price * ($act_category->discount / 100);
                            } else {
                                return 0;
                            }
                        }
                        return $act_category->discount ?? 0;
                    }
                }
            }
        }

        if (empty($promotion)) {
            return $price;
        }
    }
}
