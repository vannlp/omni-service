<?php


namespace App\V1\Validators;


use App\Coupon;
use App\Http\Validators\ValidatorBase;
use App\Supports\Message;
use Illuminate\Http\Request;

class CouponUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'   => 'required|exists:coupons,id,deleted_at,NULL',
            'code' => 'nullable|unique_update_company_delete:coupons,code',
            'name' => 'nullable|max:100',
            'type' => 'nullable|in:P,F',
        ];
    }

    protected function attributes()
    {
        return [
            'code' => Message::get("code"),
            'name' => Message::get("name"),
            'type' => Message::get("type"),
        ];
    }
}