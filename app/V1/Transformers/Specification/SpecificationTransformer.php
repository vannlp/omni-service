<?php

namespace App\V1\Transformers\Specification;


use App\Supports\TM_Error;
use App\Specification;
use League\Fractal\TransformerAbstract;

class SpecificationTransformer extends TransformerAbstract
{
    /**
     * @param Specification $item
     * @return array
     * @throws \Exception
     */
    public function transform(Specification $item)
    {
        try {
            return [
                'id'         => $item->id,
                'code'       => $item->code,
                'value'      => $item->value,
                'created_at' => date('d-m-Y', strtotime($item->created_at)),
                'updated_at' => date('d-m-Y', strtotime($item->updated_at)),
                'created_by' => object_get($item, 'userCreated.full_name', null),
                'updated_by' => object_get($item, 'userUpdated.full_name', null),
            ];
        }
        catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
