<?php


namespace App\V1\Transformers\Coupon;


use App\Coupon;
use App\CouponCodes;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class CouponCodeTransformer extends TransformerAbstract
{
    public function transform(CouponCodes $coupon)
    {

        try {
            return [
                'id'                    => $coupon->id,
                'coupon_id'             => $coupon->coupon_id,
                'code'                  => $coupon->code,
                'type'                  => $coupon->type,
                'discount'              => $coupon->discount,
                'limit_discount'        => $coupon->limit_discount,
                'user_code'             => $coupon->user_code ?? null,
                'user_phone'             => $coupon->user->phone ?? null,
                'start_date'            => !empty($coupon->start_date) ? date('Y-m-d H:i:s', strtotime($coupon->start_date)) : null,
                'end_date'              => !empty($coupon->end_date) ? date('Y-m-d H:i:s', strtotime($coupon->end_date)) : null,
                'order_used'            => $coupon->order_used,
                'is_active'             => $coupon->is_active,
                'created_at'            => date('Y-m-d H:i:s', strtotime($coupon->created_at)),
                'updated_at'            => date('Y-m-d H:i:s', strtotime($coupon->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
