<?php
/**
 * User: kpistech2
 * Date: 2020-06-08
 * Time: 22:45
 */

namespace App\V1\Transformers\Feature;


use App\Feature;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class FeatureTransformer extends TransformerAbstract
{
    public function transform(Feature $item)
    {
        try {
            return [
                'id'          => $item->id,
                'code'        => $item->code,
                'name'        => $item->name,
                'description' => $item->description,
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
