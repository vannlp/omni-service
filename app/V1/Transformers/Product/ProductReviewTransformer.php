<?php


namespace App\V1\Transformers\Product;


use App\ProductReview;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class ProductReviewTransformer extends TransformerAbstract
{
    public function transform(ProductReview $productReview)
    {
        try {
            return [
                'product_id'   => $productReview->product_id,
                'product_name' => object_get($productReview, 'product.name'),
                'product_code' => object_get($productReview, 'product.code'),
                'rate'         => $productReview->rate,
                'message'      => $productReview->message,
                'created_at'   => date('d-m-Y H:i:s', strtotime($productReview->created_at)),
                'created_by'   => object_get($productReview, 'createdBy.profile.full_name', null),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}