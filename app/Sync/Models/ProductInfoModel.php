<?php
/**
 * User: dai.ho
 * Date: 28/01/2021
 * Time: 3:21 PM
 */

namespace App\Sync\Models;



use App\ProductInfo;
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
class ProductInfoModel  extends AbstractModel
{
    /**
     * ProductModel constructor.
     * @param ProductInfo|null $model
     */
    public function __construct(ProductInfo $model = null)
    {
        parent::__construct($model);
    }
    

    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        

        if ($id) {
            $item = ProductInfo::find($id);
            if (empty($item)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }      
            $item->code                    = $input['code'] ?? $item->code;
            $item->product_info_name       = $input['product_info_name'] ?? $item->product_info_name;
            $item->description             = $input['description'] ?? $item->description;
            $item->status                  = $input['status'] ?? $item->status;
            $item->type                    = $input['type'] ?? $item->type;

            $item->updated_at = date("Y-m-d H:i:s", time());
            $item->updated_by = TM::getCurrentUserId();
            $item->save();
        } else {
            $param = [
                'code'                   => $input['code'],
                'product_info_name'      => $input['product_info_name'],
                'description'            => $input['description'],
                'status'                 => $input['status'],
                'type'                   => $input['type'],
              
            ];

            /** @var Area $item */
            $item = $this->create($param);
        }
        return $item;
    }

}