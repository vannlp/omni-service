<?php


namespace App\V1\Transformers\Promocode;


use App\Promocode;
use App\Supports\TM_Error;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class PromocodeTransformer extends TransformerAbstract
{
    public function transform(Promocode $promocode)
    {
        try {
            return [
                'id'                => $promocode->id,
                'code'              => $promocode->code,
                'value'             => $promocode->name,
                'user_use'          => $promocode->type,
                'is_active'         => $promocode->discount,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}