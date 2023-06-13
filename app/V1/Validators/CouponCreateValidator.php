<?php


namespace App\V1\Validators;


use App\Coupon;
use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class CouponCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'                            => 'required|unique_create_company_delete:coupons,code',
            'name'                            => 'required|max:100',
            'type'                            => 'required|in:P,F',
            'type_discount'                   => 'required',
            'coupon_products'                 => 'nullable|array',
            'coupon_products.*.product_id'    => 'required|exists:products,id,deleted_at,NULL',
            'coupon_categories'               => 'nullable|array',
            'coupon_categories.*.category_id' => 'required|exists:categories,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'code'                            => Message::get("code"),
            'name'                            => Message::get("name"),
            'type'                            => Message::get("type"),
            'type_discount'                   => Message::get("type_discount"),
            'coupon_products'                 => Message::get("coupon_products"),
            'coupon_categories'               => Message::get("coupon_categories"),
            'coupon_products.*.product_id'    => Message::get("coupon_products"),
            'coupon_categories.*.category_id' => Message::get("coupon_categories"),
        ];
    }
}