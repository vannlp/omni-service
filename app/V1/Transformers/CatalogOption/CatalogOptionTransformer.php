<?php
/**
 * User: dai.ho
 * Date: 1/06/2020
 * Time: 1:35 PM
 */

namespace App\V1\Transformers\CatalogOption;


use App\CatalogOption;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class CatalogOptionTransformer extends TransformerAbstract
{
    /**
     * @param CatalogOption $item
     * @return array
     * @throws \Exception
     */
    public function transform(CatalogOption $item)
    {
        try {
            return [
                'id'          => $item->id,
                'code'        => $item->code,
                'name'        => $item->name,
                'type'        => $item->type,
                'order'       => $item->order,
                'description' => $item->description,

                'store_id'   => $item->store_id,
                'store_code' => object_get($item, 'store.code'),
                'store_name' => object_get($item, 'store.name'),

                'company_id'   => $item->store_id,
                'company_code' => object_get($item, 'store.code'),
                'company_name' => object_get($item, 'store.name'),

                'values' => !empty($item->values) ? json_decode($item->values, true) : [],

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
