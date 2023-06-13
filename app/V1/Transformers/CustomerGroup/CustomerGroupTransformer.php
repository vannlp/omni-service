<?php


namespace App\V1\Transformers\CustomerGroup;


use App\CustomerGroup;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class CustomerGroupTransformer extends TransformerAbstract
{
    public function transform(CustomerGroup $customerGroup)
    {
        try {
            return [
                'id'          => $customerGroup->id,
                'code'        => $customerGroup->code,
                'name'        => $customerGroup->name,
                'description' => $customerGroup->description,

                'is_active'  => $customerGroup->is_active,
                'created_at' => date('d-m-Y', strtotime($customerGroup->created_at)),
                'updated_at' => date('d-m-Y', strtotime($customerGroup->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}