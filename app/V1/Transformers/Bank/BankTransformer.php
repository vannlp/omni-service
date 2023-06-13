<?php


namespace App\V1\Transformers\Bank;


use App\Bank;
use League\Fractal\TransformerAbstract;

class BankTransformer extends TransformerAbstract
{
    public function transform(Bank $model)
    {
        return [
            'id'          => $model->id,
            'code'        => $model->code,
            'name'        => $model->name,
            'description' => $model->description,
            'logo'        => $model->logo ? url($model->logo) : "",
        ];
    }
}