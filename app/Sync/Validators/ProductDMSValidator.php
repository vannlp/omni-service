<?php
/**
 * User: dai.ho
 * Date: 28/01/2021
 * Time: 3:12 PM
 */

namespace App\Sync\Validators;


use App\Supports\Message;
use App\Http\Validators\ValidatorBase;
class ProductDMSValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            
            "code"                           => 'required|string|max:50|unique:product_dms_imports',
            "parent_product_code"            => 'nullable',
            "status"                         => 'required|integer|max:5',
            "uom1"                           => 'nullable',
            "uom2"                           => 'nullable',
            "cat_id"                         => 'required',
            "sub_cat_id"                     => 'nullable',
            "brand_id"                       => 'nullable',
            "flavour_id"                     => 'nullable',
            "packing_id"                     => 'nullable',
            "product_type"                   => 'nullable',
            "expiry_type"                    => 'nullable',
            "expiry_date"                    => 'nullable',
            "barcode"                        => 'nullable',
            "product_level_id"               => 'nullable',
            "create_date"                    => 'nullable',
            "create_user"                    => 'nullable',
            "update_date"                    => 'nullable',
            "update_user"                    => 'nullable',
            "check_lot"                      => 'nullable',
            "safety_stock"                   => 'nullable',
            "commission"                     => 'nullable',
            "volumn"                         => 'nullable',
            "net_weight"                     => 'nullable',
            "gross_weight"                   => 'nullable',
            "product_name"                   => 'required',
            "name_text"                      => 'nullable',
            "sub_cat_t_id"                   => 'nullable',
            "is_not_trigger"                 => 'nullable',
            "is_not_migrate"                 => 'nullable',
            "syn_action"                     => 'nullable',
            "group_cat_id"                   => 'nullable',
            "group_vat"                      => 'nullable',
            "short_name"                     => 'nullable',
            "order_index"                    => 'nullable',
            "convfact"                       => 'nullable',
            "ref_product_id"                 => 'nullable',
            "ref_apply_date"                 => 'nullable',
            "order_index_po"                 => 'nullable',
            "pallet"                         => 'nullable',
        
        ];
    }

    protected function attributes()
    {
        return [
            "code"                           => Message::get("code"),
            "parent_product_code"            => Message::get("parent_product_code"),
            "status"                         => Message::get("status"),
            "uom1"                           => Message::get("uom1"),
            "uom2"                           => Message::get("uom2"),
            "cat_id"                         => Message::get("cat_id"),
            "sub_cat_id"                     => Message::get("sub_cat_id"),
            "brand_id"                       => Message::get("brand_id"),
            "flavour_id"                     => Message::get("flavour_id"),
            "packing_id"                     => Message::get("packing_id"),
            "product_type"                   => Message::get("product_type"),
            "expiry_type"                    => Message::get("expiry_type"),
            "expiry_date"                    => Message::get("expiry_date"),
            "barcode"                        => Message::get("barcode"),
            "product_level_id"               => Message::get("product_level_id"),
            "create_date"                    => Message::get("create_date"),
            "create_user"                    => Message::get("create_user"),
            "update_date"                    => Message::get("update_date"),
            "update_user"                    => Message::get("update_user"),
            "check_lot"                      => Message::get("check_lot"),
            "safety_stock"                   => Message::get("safety_stock"),
            "commission"                     => Message::get("commission"),
            "volumn"                         => Message::get("volumn"),
            "net_weight"                     => Message::get("net_weight"),
            "gross_weight"                   => Message::get("gross_weight"),
            "product_name"                   => Message::get("product_name"),
            "name_text"                      => Message::get("name_text"),
            "sub_cat_t_id"                   => Message::get("sub_cat_t_id"),
            "is_not_trigger"                 => Message::get("is_not_trigger"),
            "is_not_migrate"                 => Message::get("is_not_migrate"),
            "syn_action"                     => Message::get("syn_action"),
            "group_cat_id"                   => Message::get("group_cat_id"),
            "group_vat"                      => Message::get("group_vat"),
            "short_name"                     => Message::get("short_name"),
            "order_index"                    => Message::get("order_index"),
            "convfact"                       => Message::get("convfact"),
            "ref_product_id"                 => Message::get("ref_product_id"),
            "ref_apply_date"                 => Message::get("ref_apply_date"),
            "order_index_po"                 => Message::get("order_index_po"),
            "pallet"                         => Message::get("pallet"),
          
            
           
        ];
    }
}