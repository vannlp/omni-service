<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:55 PM
 */

namespace App\Sync\Transformers;

use App\ProductDMS;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class ProductDMSSyncTransformer extends TransformerAbstract
{
    public function transform(ProductDMS $ProductDMS)
    {
        try {
            return [
                'id'                    =>$ProductDMS->id,
                'code'                  =>$ProductDMS->code,
                'parent_product_code'   =>$ProductDMS->parent_product_code,
                'status'                =>$ProductDMS->status,
                'uom1'                  =>$ProductDMS->uom1,
                'uom2'                  =>$ProductDMS->uom2,
                'cat_id'                =>$ProductDMS->cat_id,
                'sub_cat_id'            =>$ProductDMS->sub_cat_id,
                'brand_id'              =>$ProductDMS->brand_id,
                'flavour_id'            =>$ProductDMS->flavour_id,
                'packing_id'            =>$ProductDMS->packing_id,
                'product_type'          =>$ProductDMS->product_type,
                'expiry_type'           =>$ProductDMS->expiry_type,
                'expiry_date'           =>$ProductDMS->expiry_date,
                'barcode'               =>$ProductDMS->barcode,
                'product_level_id'      =>$ProductDMS->product_level_id,
                'create_date'           =>$ProductDMS->create_date,
                'create_user'           =>$ProductDMS->create_user,
                'update_date'           =>$ProductDMS->update_date,
                'update_user'           =>$ProductDMS->update_user,
                'check_lot'             =>$ProductDMS->check_lot,
                'safety_stock'          =>$ProductDMS->safety_stock,
                'commission'            =>$ProductDMS->commission,
                'volumn'                =>$ProductDMS->volumn,
                'net_weight'            =>$ProductDMS->net_weight,
                'gross_weight'          =>$ProductDMS->gross_weight,
                'product_name'          =>$ProductDMS->product_name,
                'name_text'             =>$ProductDMS->name_text,
                'sub_cat_t_id'          =>$ProductDMS->sub_cat_t_id,
                'is_not_trigger'        =>$ProductDMS->is_not_trigger,
                'is_not_migrate'        =>$ProductDMS->is_not_migrate,
                'syn_action'            =>$ProductDMS->syn_action,
                'group_cat_id'          =>$ProductDMS->group_cat_id,
                'group_vat'             =>$ProductDMS->group_vat,
                'short_name'            =>$ProductDMS->short_name,
                'order_index'           =>$ProductDMS->order_index,
                'convfact'              =>$ProductDMS->convfact,
                'ref_product_id'        =>$ProductDMS->ref_product_id,
                'ref_apply_date'        =>$ProductDMS->ref_apply_date,
                'order_index_po'        =>$ProductDMS->order_index_po,
                'pallet'                =>$ProductDMS->pallet,
                'product_id'            =>$ProductDMS->product_id,
              
         ];
        }
        catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
