<?php

/**
 * User: Administrator
 * Date: 21/12/2018
 * Time: 09:25 PM
 */

namespace App;

use Illuminate\Support\Facades\DB;

class Order extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders';



    protected $fillable
        = [
            'id',
            'code',
            'order_type',
            'status',
            'status_text',
            'sync_status',
            'search_sync_status',
            'customer_id',
            'customer_name',
            'customer_email',
            'customer_code',
            'customer_phone',
            'customer_lat',
            'customer_long',
            'customer_postcode',
            'session_id',
            'hub_id',
            'customer_point',
            'customer_star',
            'cancel_reason',
            'comment_for_customer',
            'partner_id',
            'partner_point',
            'partner_start',
            'comment_for_partner',
            'note',
            'phone',
            'shipping_address_id',
            'shipping_address',
            'street_id',
            'coupon_code',
            'coupon_discount_code',
            'coupon_delivery_code',
            'delivery_discount_code',
            'voucher_code',
            'voucher_discount_code',
            'paid',
            'partner_ship_fee',
            'partner_revenue_total',
            'shine_revenue_total',
            'original_price',
            'point',
            'ex_change_point',
            'sub_total_price',
            'total_price',
            'total_discount',
            'payment_method',
            'transfer_confirmation',
            'payment_status',
            'shipping_method',
            "shipping_method_code",
            "shipping_method_name",
            "shipping_service",
            "shipping_service_name",
            "extra_service",
            "ship_fee_start",
            'estimated_deliver_time',
            'shipping_note',
            'outvat',
            'invoice_city_code',
            'invoice_city_name',
            'invoice_district_code',
            'invoice_district_name',
            'invoice_ward_code',
            'invoice_ward_name',
            'invoice_street_address',
            'invoice_company_name',
            'invoice_company_email',
            'invoice_tax',
            'invoice_company_address',
            'updated_date',
            'created_date',
            'completed_date',
            'canceled_date',
            'canceled_by',
            'canceled_reason',
            'canceled_reason_admin',
            'delivery_time',
            'latlong',
            'lat',
            'long',
            'district_code',
            'district_fee',
            'approver',
            'denied_ids',
            'enterprise_denied_ids',
            'store_id',
            'company_id',
            'seller_id',
            'omni_chanel_code',
            'request_assign_to',
            'chanel_data_json',
            'order_channel',
            'street_address',
            'shipping_address_ward_code',
            'shipping_address_ward_type',
            'shipping_address_ward_name',
            'shipping_address_district_code',
            'shipping_address_district_type',
            'shipping_address_district_name',
            'shipping_address_city_code',
            'shipping_address_city_type',
            'shipping_address_city_name',
            'shipping_address_phone',
            'shipping_address_full_name',
            'shipping_info_code',
            'shipping_info_json',
            'discount',
            'distributor_id',
            'distributor_code',
            'distributor_name',
            'distributor_email',
            'distributor_phone',
            'distributor_lat',
            'distributor_long',
            'distributor_postcode',
            'distributor_status',
            'distributor_deny_ids',
            'data_sync',
            'order_service',
            'order_service_add',
            'saving',
            'ship_fee',
            'is_active',
            'is_freeship',
            'seller_id',
            'seller_code',
            'seller_name',
            'leader_id',
            'total_info',
            'qr_scan',
            'deleted',
            'created_at',
            'created_by',
            'updated_by',
            'updated_at',
            'deleted',
            'time_qr_momo',
            'log_qr_payment',
            'id_momo_payment',
            'shopee_reference_id',
            'free_item',
            'lading_method',
            'payment_code',
            'time_qr_momo',
            'time_qr_zalo',
            'time_qr_spp',
            'uid_payment',
            'virtual_account_code',
            'status_crm',
            'crm_check',
            'crm_description',
            'description',
            'total_weight',
            'receive_date',
            'intersection_distance',
            'access_trade_id',
            'access_trade_click_id',
            'order_source',
            'status_shipping',
            'failed_reason',
            'push_cancel_to_dms',
            'cart_id',
            'ship_fee_real',
            'discount_admin_input_type',
            'discount_admin_input',
            'free_item_admin',
            'coupon_admin'
        ];

    public function getStatus()
    {
        return $this->belongsTo(OrderStatus::class, 'status', 'code')
            ->where('company_id', TM::getCurrentCompanyId());
    }
    public function sub_distributor()
    {
        return $this->hasOne(User::class, 'code', 'sub_distributor_code');
    }
    public function details()
    {
        return $this->hasMany(__NAMESPACE__ . '\OrderDetail', 'order_id', 'id');
    }

    public function user()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'approver');
    }

    public function addressDistributor()
    {
        return $this->hasOne(__NAMESPACE__ . '\Distributor', 'code', 'distributor_code');
    }

    public function getShippingMethod()
    {
        return $this->hasOne(__NAMESPACE__ . '\ShippingMethod', 'id', 'shipping_method');
    }

    public function shippingOrder()
    {
        return $this->hasOne(ShippingOrder::class, 'order_id', 'id');
    }

    public function customer()
    {
        return $this->hasOne(User::class, 'id', 'customer_id');
    }

    public function partner()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'partner_id');
    }

    public function created_by()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function updated_by()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
    }


    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
    }

    public function getShippingAddress()
    {
        return $this->hasOne(__NAMESPACE__ . '\ShippingAddress', 'id', 'shipping_address_id');
    }

    public function approverUser()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'approver');
    }

    public function promotionOrder()
    {
        return $this->hasOne(__NAMESPACE__ . '\Promotion', 'code', 'coupon_code');
    }

    public function orderHistory()
    {
        return $this->hasMany(__NAMESPACE__ . '\OrderHistory', 'order_id', 'id');
    }

    public function seller()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'seller_id');
    }
    public function leader()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'leader_id');
    }

    public function getCity()
    {
        return $this->hasOne(__NAMESPACE__ . '\City', 'code', 'shipping_address_city_code');
    }

    public function getDistrict()
    {
        return $this->hasOne(__NAMESPACE__ . '\District', 'code', 'shipping_address_district_code');
    }

    public function getWard()
    {
        return $this->hasOne(__NAMESPACE__ . '\Ward', 'code', 'shipping_address_ward_code');
    }

    public function getOrderStatus($statusCode)
    {
        $orderStatus = OrderStatus::model()->where('code', $statusCode)->where(
            'company_id',
            TM::getCurrentCompanyId()
        )->first();
        return $orderStatus;
    }

    public function store()
    {
        return $this->hasOne(Store::class, 'id', 'store_id');
    }

    public function hub()
    {
        return $this->belongsTo(User::class, 'hub_id', 'id')
            ->addSelect(['id', 'name', 'email', 'phone']);
    }

    public function promotionTotals()
    {
        return $this->hasMany(PromotionTotal::class, 'order_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(OrderStatus::class, 'status', 'code');
    }

    public function getShippingInfoByPhone()
    {
        return $this->hasOne(CustomerInformation::class, 'phone', 'customer_phone');
    }

    public function distributor()
    {
        return $this->hasOne(User::class, 'id', 'distributor_id');
    }

    public function vpvirtualaccount()
    {
        return $this->hasOne(PaymentVirtualAccount::class, 'order_id', 'id')
            ->addSelect([
                'type',
                'type_cart',
                'payer_name',
                'collect_ammount',
                'transaction_id',
                'transaction_description',
                'virtual_account_number',
                'master_account_number',
                DB::raw('date_format(transaction_date, "%d/%m/%Y %H:%i:%s") as transaction_date'),
                DB::raw('date_format(value_date, "%d/%m/%Y") as value_date'),
            ]);
    }
    public function vpvirtualaccount2()
    {
        return $this->hasOne(PaymentVirtualAccount::class, 'order_id', 'id');
    }
    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id', 'id')
            ->addSelect([
                'order_id',
                'order_status_id',
                'order_status_code',
                'order_status_name',
                DB::raw('date_format(created_at, "%d/%m/%Y") as status_at'),
                'created_by',
            ]);
    }
    public function shippingStatusHistories()
    {
        return $this->hasMany(ShippingHistoryStatus::class, 'shipping_id', 'code')
            ->addSelect([
                'status_code',
                'text_status_code',
                'phone_driver',
                'name_driver',
                'license_plate',
                DB::raw('date_format(created_at, "%d/%m/%Y %H:%i") as status_at'),
                'created_by',
                'created_at'
            ])
            ->orderBy("created_at",'desc');
    }
    public function promotionTotalsNotFlashSale()
    {
        return $this->hasMany(PromotionTotal::class, 'order_id', 'id')->where('promotion_type',"!=","FLASH_SALE");
    }

}
