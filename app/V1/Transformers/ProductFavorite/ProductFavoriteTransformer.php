<?php


namespace App\V1\Transformers\ProductFavorite;


use App\ProductFavorite;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class ProductFavoriteTransformer extends TransformerAbstract
{
    public function transform(ProductFavorite $model)
    {
        try {
            return [
                'id'           => $model->id,
                'product_id'   => $model->product_id,
                'product_name' => object_get($model, 'product.name'),
                'product_code' => object_get($model, 'product.code'),
                'user_id'      => $model->user_id,
                'full_name'    => object_get($model, 'user.profile.full_name'),
                'created_at'   => date('d-m-Y', strtotime($model->created_at)),
                'created_by'   => object_get($model, 'createdBy.profile.full_name'),
                'updated_at'   => !empty($model->updated_at) ? date('d-m-Y',
                    strtotime($model->updated_at)) : null,
                'updated_by'   => object_get($model, 'updatedBy.profile.full_name'),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}