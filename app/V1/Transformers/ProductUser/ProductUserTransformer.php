<?php


namespace App\V1\Transformers\ProductUser;


use App\ProductUser;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class ProductUserTransformer extends TransformerAbstract
{
    public function transform(ProductUser $productUser)
    {
        try {
            $output = [
                'id'           => $productUser->id,
                'partner_id'   => $productUser->user_id,
                'product_id'   => $productUser->product_id,
                'product_name' => object_get($productUser, 'product.name'),
                'product_code' => object_get($productUser, 'product.code'),
                'stock'        => $productUser->stock,
                'total_qty'    => $productUser->total_qty,
                'created_at'   => date('d-m-Y', strtotime($productUser->created_at)),
                'created_by'   => object_get($productUser, 'createdBy.profile.full_name'),
                'updated_at'   => !empty($productUser->updated_at) ? date('d-m-Y',
                    strtotime($productUser->updated_at)) : null,
                'updated_by'   => object_get($productUser, 'updatedBy.profile.full_name'),
            ];

            return $output;
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}