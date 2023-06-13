<?php
/**
 * User: dai.ho
 * Date: 5/06/2020
 * Time: 10:47 AM
 */

namespace App\V1\Transformers\AccessTradeSetting;


use App\AccessTradeSetting;
use App\Age;
use App\Area;
use App\Supports\TM_Error;
use App\TM;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class AccessTradeSettingTransformer extends TransformerAbstract
{
    public function transform(AccessTradeSetting $item)
    {
        try {
            return [
                'id'         => $item->id,
                'cpde'       => $item->code,
                'api_key'    => $item->key,
                'value_script' => $item->value,
                'category_id' => $item->category_id,
                'campaign_id' => $item->campaign_id,
                'category_name' => array_get($item,"category.name"),
                'display_in_categories' => $item->display_in_categories,
                'company_id' => $item->company_id,
                'store_id'   => $item->store_id,
                'created_at' => date('d-m-Y', strtotime($item->created_at)),
                'updated_at' => date('d-m-Y', strtotime($item->updated_at)),
                'created_by' => object_get($item, 'createdBy.full_name', null),
                'updated_by' => object_get($item, 'updatedBy.full_name', null),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
