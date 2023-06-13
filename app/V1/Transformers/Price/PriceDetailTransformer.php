<?php


namespace App\V1\Transformers\Price;


use App\PriceDetail;
use App\Supports\TM_Error;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class PriceDetailTransformer extends TransformerAbstract
{
    public function transform(PriceDetail $model)
    {
        try {
            return [
                'id'           => $model->id,
                'price_id'     => $model->price_id,
                'product_id'   => $model->product_id,
                'product_code' => Arr::get($model, 'product.code'),
                'product_name' => Arr::get($model, 'product.name'),
                'unit_id'      => Arr::get($model, 'product.unit_id'),
                'unit_name'    => Arr::get($model, 'product.unit.name'),
                'from'         => !empty($model->from) ? date("d-m-Y", strtotime($model->from)) : null,
                'to'           => !empty($model->to) ? date("d-m-Y", strtotime($model->to)) : null,
                'price'        => $model->price,
                'status'       => $model->status,
                'status_name'  => $model->status == 1 ? "Kích hoạt" : "Chưa kích hoạt",
                'updated_at'   => !empty($model->updated_at) ? date('Y-m-d', strtotime($model->updated_at)) : null,
                'created_at'   => !empty($model->created_at) ? date('Y-m-d', strtotime($model->created_at)) : null,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}