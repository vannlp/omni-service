<?php

/**
 * User: kpistech2
 * Date: 2020-05-09
 * Time: 22:53
 */

namespace App;


class OrderExportExcelRP extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_export_excel';

    protected $connection = 'mysql2';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'distributor_code',
        'distributor_name',
        'customer_code',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_gender',
        'customer_birthday',
        'shipping_cus_name',
        'shipping_cus_phone',
        'shipping_address',
        'ward',
        'district',
        'city',
        'order_code',
        'order_type',
        'order_channel',
        'status_crm',
        'date_crm',
        'lading_method',
        'created_at',
        'updated_date',
        'order_created_date',
        'order_shipped_date',
        'order_updated_date',
        'updated_at',
        'product_code',
        'product_name',
        'product_specification',
        'cad_code',
        'capacity',
        'cat',
        'sub_cat',
        'brand',
        'qty',
        'product_price',
        'product_real_price',
        'price_CK',
        'total_price_product',
        'discount',
        'payment',
        'payment_code',
        'payment_method',
        'shipping_method_name',
        'is_freeship',
        'payment_status',
        'status',
        'shipping_order_status',
        'seller_phone',
        'reference_phone',
        'promotion_code',
        'promotion_name',
        'unit',
        'age',
        'expiry',
        'manufacture',
        'brandy',
        'intrustry',
        'ship_fee_customer',
        'ship_fee_shop',
        'ship_fee_total',
        'ward_code',
        'ward_name',
        'district_code',
        'district_name',
        'city_code',
        'city_name',
        'ward_ship_code',
        'district_ship_code',
        'date_time',
        'total_price',
        'total_discount',
        'seller',
        'leader',
        'coupon_code',
        'coupon_delivery_code',
        'discount_coupon',
        'discount_fee_ship',
        'item_product_code',
        'item_product_name',
        'item_product_qty',
        'special_percentage',
        'product_type',
        'customer_bank',
        'customer_bank_code',
        'virtual_account_number',
        'virtual_account',
        'shipping_method_code',
        'cancel_reason',
        'tax_product',
        'cad_code_sub_cat',
        'cad_code_brand',
        'cad_code_brandy',
        'invoice_company_address',
        'invoice_company_name',
        'invoice_company_email',
        'invoice_tax',
        'invoice_code',
        'order_CK',
        'order_price_CK',
        'division',
        'source',
        'packing',
        'p_sku',
        'p_sku_name',
        'sku_standard',
        'p_type',
        'p_attribute',
        'p_variant',
        'total_weight',
        'total_km',
        'order_created_at',
        'updated_at',
        'updated_by',
        'deleted_at',
        'deleted'
    ];
}
