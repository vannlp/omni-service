<?php
/**
 * User: dai.ho
 * Date: 9/06/2020
 * Time: 3:55 PM
 */

namespace App\V1\Transformers\Area;


use App\Area;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class AreaClientTransformer extends TransformerAbstract
{
    public function transform(Area $item)
    {
        try {
            return [
                'id'          => $item->id,
                'code'        => $item->code,
                'name'        => $item->name,
                'description' => $item->description,
                'store_id'    => $item->store_id,
                'is_active'   => $item->is_active,
                'created_at'  => date('d-m-Y', strtotime($item->created_at)),
                'updated_at'  => date('d-m-Y', strtotime($item->updated_at)),
                'created_by'  => object_get($item, 'createdBy.full_name', null),
                'updated_by'  => object_get($item, 'updatedBy.full_name', null),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
