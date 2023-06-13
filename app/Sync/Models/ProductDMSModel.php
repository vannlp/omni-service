<?php
/**
 * User: dai.ho
 * Date: 28/01/2021
 * Time: 3:21 PM
 */

namespace App\Sync\Models;



use App\ProductDMS;
use App\Supports\Message;
use App\TM;
use App\Unit;
use App\V1\Models\AbstractModel;
use App\WarehouseDetail;
use App\Website;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class ProductModel
 * @package App\Sync\Models
 */
class ProductDMSModel  extends AbstractModel
{
    /**
     * ProductModel constructor.
     * @param ProductDMS|null $model
     */
    public function __construct(ProductDMS $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        
        if ($id) {
            $item = ProductDMS::find($id);
            if (empty($item)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }      
            
            $item->code                    = $input['code'] ?? $item->code;
            $item->parent_product_code     = $input['parent_product_code'] ?? $item->parent_product_code;
            $item->status                  = $input['status'] ?? $item->status;
            $item->uom1                    = $input['uom1'] ?? $item->uom1;
            $item->uom2                    = $input['uom2'] ?? $item->uom2;
            $item->cat_id                  = $input['cat_id'] ?? $item->cat_id;
            $item->sub_cat_id              = $input['sub_cat_id'] ?? $item->sub_cat_id;
            $item->brand_id                = $input['brand_id'] ?? $item->brand_id;
            $item->flavour_id              = $input['flavour_id'] ?? $item->flavour_id;
            $item->packing_id              = $input['packing_id'] ?? $item->packing_id;
            $item->product_type            = $input['product_type'] ?? $item->product_type;
            $item->expiry_type             = $input['expiry_type'] ?? $item->expiry_type;
            $item->expiry_date             = $input['expiry_date'] ?? $item->expiry_date;
            $item->barcode                 = $input['barcode'] ?? $item->barcode;
            $item->product_level_id        = $input['product_level_id'] ?? $item->product_level_id;
            $item->create_date             = $input['create_date'] ?? $item->create_date;
            $item->create_user             = $input['create_user'] ?? $item->create_user;
            $item->update_date             = $input['update_date'] ?? $item->update_date;
            $item->update_user             = $input['update_user'] ?? $item->update_user;
            $item->check_lot               = $input['check_lot'] ?? $item->check_lot;
            $item->safety_stock            = $input['safety_stock'] ?? $item->safety_stock;
            $item->commission              = $input['commission'] ?? $item->commission;
            $item->volumn                  = $input['volumn'] ?? $item->volumn;
            $item->net_weight              = $input['net_weight'] ?? $item->net_weight;
            $item->gross_weight            = $input['gross_weight'] ?? $item->gross_weight;
            $item->product_name            = $input['product_name'] ?? $item->product_name;
            $item->name_text               = $input['name_text'] ?? $item->name_text;
            $item->sub_cat_t_id            = $input['sub_cat_t_id'] ?? $item->sub_cat_t_id;
            $item->is_not_trigger          = $input['is_not_trigger'] ?? $item->is_not_trigger;
            $item->is_not_migrate          = $input['is_not_migrate'] ?? $item->is_not_migrate;
            $item->syn_action              = $input['syn_action'] ?? $item->syn_action;
            $item->group_cat_id            = $input['group_cat_id'] ?? $item->group_cat_id;
            $item->group_vat               = $input['group_vat'] ?? $item->group_vat;
            $item->short_name              = $input['short_name'] ?? $item->short_name;
            $item->order_index             = $input['order_index'] ?? $item->order_index;
            $item->convfact                = $input['convfact'] ?? $item->convfact;
            $item->ref_product_id          = $input['ref_product_id'] ?? $item->ref_product_id;
            $item->ref_apply_date          = $input['ref_apply_date'] ?? $item->ref_apply_date;
            $item->order_index_po          = $input['order_index_po'] ?? $item->order_index_po;
            $item->pallet                  = $input['pallet'] ?? $item->pallet;
         
          


            $item->updated_at = date("Y-m-d H:i:s", time());
            $item->updated_by = TM::getCurrentUserId();
            $item->save();
        } else {
            $param = [
                'code'                             =>$input['code'],
                "parent_product_code"              =>$input['parent_product_code'],
                "status"                           =>array_get($input, 'status',null),
                "uom1"                             =>array_get($input, 'uom1',null),
                "uom2"                             =>array_get($input, 'uom2',null),
                "cat_id"                           =>array_get($input, 'cat_id',null),
                "sub_cat_id"                       =>array_get($input, 'sub_cat_id',null),
                "brand_id"                         =>array_get($input, 'brand_id',null),
                "flavour_id"                       =>array_get($input, 'flavour_id',null),
                "packing_id"                       =>array_get($input, 'packing_id',null),
                "product_type"                     =>array_get($input, 'product_type',null),
                "expiry_type"                      =>array_get($input, 'expiry_type',null),
                "expiry_date"                      =>array_get($input, 'expiry_date',null),
                "barcode"                          =>array_get($input, 'barcode',null),
                "product_level_id"                 =>array_get($input, 'product_level_id',null),
                "create_date"                      =>array_get($input, 'create_date',null),
                "create_user"                      =>array_get($input, 'create_user',null),
                "update_date"                      =>array_get($input, 'update_date',null),
                "update_user"                      =>array_get($input, 'update_user',null),
                "check_lot"                        =>array_get($input, 'check_lot',null),
                "safety_stock"                     =>array_get($input, 'safety_stock',null),
                "commission"                       =>array_get($input, 'commission',null),
                "volumn"                           =>array_get($input, 'volumn',null),
                "net_weight"                       =>array_get($input, 'net_weight',null),
                "gross_weight"                     =>array_get($input, 'gross_weight',null),
                "product_name"                     =>array_get($input, 'product_name',null),
                "name_text"                        =>array_get($input, 'name_text',null),
                "sub_cat_t_id"                     =>array_get($input, 'sub_cat_t_id',null),
                "is_not_trigger"                   =>array_get($input, 'is_not_trigger',null),
                "is_not_migrate"                   =>array_get($input, 'is_not_migrate',null),
                "syn_action"                       =>array_get($input, 'syn_action',null),
                "group_cat_id"                     =>array_get($input, 'group_cat_id',null),
                "group_vat"                        =>array_get($input, 'group_vat',null),
                "short_name"                       =>array_get($input, 'short_name',null),
                "order_index"                      =>array_get($input, 'order_index',null),
                "convfact"                         =>array_get($input, 'convfact',null),
                "ref_product_id"                   =>array_get($input, 'ref_product_id',null),
                "ref_apply_date"                   =>array_get($input, 'ref_apply_date',null),
                "order_index_po"                   =>array_get($input, 'order_index_po',null),
                "pallet"                           =>array_get($input, 'pallet',null),
              
              
            ];

            /** @var Area $item */
            $item = $this->create($param);
        }
        return $item;
    }

}