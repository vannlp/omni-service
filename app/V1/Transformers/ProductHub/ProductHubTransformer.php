<?php


namespace App\V1\Transformers\ProductHub;

use App\ProductHub;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class ProductHubTransformer extends TransformerAbstract
{
    public function transform(ProductHub $model)
    {
        try {
            return [
                'id'           => $model->id,
                'product_id'   => $model->product_id,
                'product_name' => $model->product_name,
                'product_code' => $model->product_code,
                'unit_id'      => $model->unit_id,
                'unit_name'    => $model->unit_name,
                'user_id'      => $model->user_id,
                'limit_date'   => $model->limit_date,
                'created_at'   => date('d-m-Y', strtotime($model->created_at)),
                'created_by'   => object_get($model, 'createdBy.profile.full_name'),
                'updated_at'   => !empty($model->updated_at) ? date('d-m-Y',strtotime($model->updated_at)) : null,
                'updated_by'   => object_get($model, 'updatedBy.profile.full_name'),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
