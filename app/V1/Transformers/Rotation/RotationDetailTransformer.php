<?php


namespace App\V1\Transformers\Rotation;


use App\RotationDetail;
use App\Supports\TM_Error;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class RotationDetailTransformer extends TransformerAbstract
{
    public function transform(RotationDetail $rotation)
    {
        try {
            return [
                'id'                => $rotation->id,
                'user_id'           => $rotation->user_id,
                'user_phone'        => array_get($rotation, 'user.phone'),
                'user_name'         => array_get($rotation, 'user.name'),
                'rotation_code'     => $rotation->rotation_code,
                'rotation_name'     => array_get($rotation, 'rotationResult.name'),
                'coupon_id'         => array_get($rotation, 'rotationResult.coupon_id'),
                'coupon_code'       => array_get($rotation, 'rotationResult.coupon.name'),
                'coupon_name'       => array_get($rotation, 'rotationResult.coupon.code'),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}