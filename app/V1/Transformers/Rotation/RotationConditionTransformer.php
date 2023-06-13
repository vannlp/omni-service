<?php


namespace App\V1\Transformers\Rotation;


use App\Rotation;
use App\RotationCondition;
use App\Supports\TM_Error;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class RotationConditionTransformer extends TransformerAbstract
{
    public function transform(RotationCondition $rotation)
    {
        try {
            return [
                'id'                => $rotation->id,
                'rotation_id'       => $rotation->rotation_id,
                'code'              => $rotation->code,
                'name'              => $rotation->name,
                'type'              => $rotation->type,
                'price'             => $rotation->price,
                'is_active'         => $rotation->is_active,
                'created_at'        => date('d-m-Y', strtotime($rotation->created_at)),
                'updated_at'        => date('d-m-Y', strtotime($rotation->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}