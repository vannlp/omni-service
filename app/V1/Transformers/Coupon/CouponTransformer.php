<?php


namespace App\V1\Transformers\Coupon;


use App\Coupon;
use App\CouponCategory;
use App\CouponProduct;
use App\CouponProductexcept;
use App\CouponCategoryexcept;
use App\Supports\TM_Error;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class CouponTransformer extends TransformerAbstract
{
    public function transform(Coupon $coupon)
    {
        try {
            return [
                'id'                => $coupon->id,
                'code'              => $coupon->code,
                'name'              => $coupon->name,
                // 'type'              => $coupon->type,
                'content'           => $coupon->content,
                'type_discount'     => $coupon->type_discount,
                'type_apply'        => $coupon->type_apply,
                'condition'         => $coupon->condition,
                'stack_able'        => $coupon->stack_able,
                // 'discount'          => $coupon->discount,
                // 'limit_price'       => $coupon->limit_price,
                // 'total'             => $coupon->total,
                'mintotal'          => $coupon->mintotal,
                'maxtotal'          => $coupon->maxtotal,
                'free_shipping'     => $coupon->free_shipping,
                'apply_discount'    => $coupon->apply_discount,
                'product_ids'       => $coupon->product_ids,
                'product_codes'     => $coupon->product_codes,
                'product_names'     => $coupon->product_names,
                'category_ids'      => $coupon->category_ids,
                'category_codes'    => $coupon->category_codes,
                'category_names'    => $coupon->category_names,
                'product_except_ids'       => $coupon->product_except_ids,
                'product_except_codes'     => $coupon->product_except_codes,
                'product_except_names'     => $coupon->product_except_names,
                'category_except_ids'      => $coupon->category_except_ids,
                'category_except_codes'    => $coupon->category_except_codes,
                'category_except_names'    => $coupon->category_except_names,
                'date_start'        => $coupon->date_start,
                'date_end'          => $coupon->date_end,
                'uses_total'        => $coupon->uses_total,
                'uses_customer'     => $coupon->uses_customer,
                'thumbnail'            => $coupon->thumbnail,
                'thumbnail_id'            => $coupon->thumbnail_id,
                'status'            => $coupon->status,
                'coupon_products'   => $coupon->couponProducts->map(function ($item) {
                    return [
                        'product_id'   => $item->product_id,
                        'product_code' => $item->product_code,
                        'product_name' => $item->product_name,
                    ];
                }),
                'coupon_categories' => $coupon->couponCategories->map(function ($item) {
                    return [
                        'category_id'   => $item->category_id,
                        'category_code' => $item->category_code,
                        'category_name' => $item->category_name,
                    ];
                }),
                'coupon_products_except'   => $coupon->couponProductsexcept->map(function ($item) {
                    return [
                        'product_id'   => $item->product_id,
                        'product_code' => $item->product_code,
                        'product_name' => $item->product_name,
                    ];
                }),
                'coupon_categories_except' => $coupon->couponCategoriesexcept->map(function ($item) {
                    return [
                        'category_id'   => $item->category_id,
                        'category_code' => $item->category_code,
                        'category_name' => $item->category_name,
                    ];
                }),
                'created_at'        => date('d-m-Y', strtotime($coupon->created_at)),
                'updated_at'        => date('d-m-Y', strtotime($coupon->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
