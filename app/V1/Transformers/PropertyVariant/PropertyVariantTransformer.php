<?php

namespace App\V1\Transformers\PropertyVariant;


use App\Property;
use App\PropertyVariant;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class PropertyVariantTransformer extends TransformerAbstract
{
    public function transform(PropertyVariant $item)
    {
        try {
            return [
                'id'          => $item->id,
                'code'        => $item->code,
                'name'        => $item->name,
                'company_id'  => $item->company_id,
                'store_id'    => $item->store_id,
                'property_id' => $item->property_id,
                'property_code' => object_get($item, 'property.code', null),
                'property_name' => object_get($item, 'property.name', null),
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
