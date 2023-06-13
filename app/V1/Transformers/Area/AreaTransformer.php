<?php
/**
 * User: dai.ho
 * Date: 5/06/2020
 * Time: 10:47 AM
 */

namespace App\V1\Transformers\Area;


use App\Area;
use App\Supports\TM_Error;
use App\TM;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class AreaTransformer extends TransformerAbstract
{
    public function transform(Area $item)
    {
        try {
            return [
                'id'          => $item->id,
                'code'        => $item->code,
                'name'        => $item->name,
                'description' => $item->description,
                'company_id' =>  $item->company_id,
                'store_id'    => $item->store_id,
                'image_id'    => $item->image_id,
                'image_url'   => Arr::get($item, 'file.url', null),
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
