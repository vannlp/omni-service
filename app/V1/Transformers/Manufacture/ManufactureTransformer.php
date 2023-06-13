<?php

namespace App\V1\Transformers\Manufacture;


use App\Age;
use App\Area;
use App\Manufacture;
use App\Supports\TM_Error;
use App\TM;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class ManufactureTransformer extends TransformerAbstract
{
    public function transform(Manufacture $item)
    {
        try {
            return [
                'id'          => $item->id,
                'code'        => $item->code,
                'name'        => $item->name,
                'company_id'  => $item->company_id,
                'store_id'    => $item->store_id,
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
