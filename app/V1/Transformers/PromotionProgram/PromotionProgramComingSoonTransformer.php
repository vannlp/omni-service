<?php


namespace App\V1\Transformers\PromotionProgram;


use App\PromotionProgram;
use App\Supports\TM_Error;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class PromotionProgramComingSoonTransformer extends TransformerAbstract
{
    public function transform(PromotionProgram $model)
    {
        try {
            return [
                'id'                 => $model->id,
                'code'               => $model->code,
                'name'               => $model->name,
                'thumbnail'          => object_get($model, 'thumbnail.url', null),
                'description'        => $model->description,
                'start_date'         => $model->start_date,
                'end_date'           => $model->end_date,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}