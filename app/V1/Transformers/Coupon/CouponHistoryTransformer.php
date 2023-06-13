<?php


namespace App\V1\Transformers\Coupon;


use App\Coupon;
use App\CouponHistory;
use App\Supports\TM_Error;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class CouponHistoryTransformer extends TransformerAbstract
{
    public function transform(CouponHistory $couponHistory)
    {
        try { 
            return [
                'id'              => $couponHistory->id,
                'order_id'        => $couponHistory->order_id,
                'order_code'      => Arr::get($couponHistory, 'order.code'),
                'order_total'     => Arr::get($couponHistory, 'order.total_price'),
                'date_start'      => $couponHistory->coupon->date_start,
                'date_end'        => $couponHistory->coupon->date_end,
                'user_id'         => $couponHistory->user_id,
                'user_name'       => $couponHistory->user->name ?? null,
                'user_phone'      => $couponHistory->user->phone ?? null,
                'coupon_id'       => Arr::get($couponHistory, 'coupon.id'),
                'coupon_name'     => Arr::get($couponHistory, 'coupon.name'),
                'coupon_discount' => Arr::get($couponHistory, 'coupon.discount'),
                'coupon_type'     => Arr::get($couponHistory, 'coupon.type'),
                'coupon_code'     => $couponHistory->coupon_code,
                'created_at'      => date('d-m-Y', strtotime($couponHistory->created_at)),
                'updated_at'      => date('d-m-Y', strtotime($couponHistory->updated_at)),
            ];
        }
        catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}