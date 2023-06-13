<?php
/**
 * User: kpistech2
 * Date: 2020-06-01
 * Time: 22:21
 */

namespace App\V1\Transformers\Unit;


use App\Supports\TM_Error;
use App\Unit;
use League\Fractal\TransformerAbstract;

class UnitTransformer extends TransformerAbstract
{
    /**
     * @param Unit $item
     * @return array
     * @throws \Exception
     */
    public function transform(Unit $item)
    {
        try {
            return [
                'id'          => $item->id,
                'code'        => $item->code,
                'name'        => $item->name,
                'description' => $item->description,

                'store_id'   => $item->store_id,
                'store_code' => object_get($item, 'store.code'),
                'store_name' => object_get($item, 'store.name'),

                'company_id'   => $item->store_id,
                'company_code' => object_get($item, 'store.code'),
                'company_name' => object_get($item, 'store.name'),

                'is_active'  => $item->is_active,
                'created_at' => date('d-m-Y', strtotime($item->created_at)),
                'updated_at' => date('d-m-Y', strtotime($item->updated_at)),
                'created_by' => object_get($item, 'userCreated.full_name', null),
                'updated_by' => object_get($item, 'userUpdated.full_name', null),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
