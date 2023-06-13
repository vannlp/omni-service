<?php
/**
 * User: dai.ho
 * Date: 15/05/2020
 * Time: 1:29 PM
 */

namespace App;


class ProductExcel extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_dms_imports';

    protected $fillable = [
        "id",
        "code",
        "parent_product_code",
        "status",
        "uom1",
        "uom2",
        "cat_id",
        "sub_cat_id",
        "brand_id",
        "flavour_id",
        "packing_id",
        "product_type",
        "expiry_type",
        "expiry_date",
        "barcode",
        "product_level_id",
        "create_date",
        "create_user",
        "update_date",
        "update_user",
        "check_lot",
        "safety_stock",
        "commission",
        "volumn",
        "net_weight",
        "gross_weight",
        "product_name",
        "name_text",
        "sub_cat_t_id",
        "is_not_trigger",
        "is_not_migrate",
        "syn_action",
        "group_cat_id",
        "group_vat",
        "short_name",
        "order_index",
        "convfact",
        "ref_product_id",
        "ref_apply_date",
        "order_index_po",
        "pallet",
        "product_id",

        
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at",
        "deleted_by",
    ];
 

    // public function product()
    // {
    //     return $this->hasOne(Product::class, 'id', 'product_id');
    // }
    
}
