<?php
/**
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:59 PM
 */

namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Promotion;
use App\Supports\Message;
use Illuminate\Http\Request;

class PromotionUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'                        => 'exists:promotions,id,deleted_at,NULL',
            'title'                     => 'nullable|max:200',
            'code'                      => 'nullable|max:20',
            'from'                      => 'nullable|date_format:d-m-Y',
            'to'                        => 'nullable|date_format:d-m-Y',
            'type'                      => 'nullable|in:ALL,GROUP,PRODUCT,POINT,RANKING',
//            'details'                   => 'required|array',
//            'details.*.product_id'      => 'exists:products,id,deleted_at,NULL',
//            'details.*.category_id'     => 'exists:categories,id,deleted_at,NULL',
//            'details.*.qty'             => 'nullable|numeric',
//            'details.*.point'           => 'nullable|numeric',
//            'details.*.price'           => 'nullable|numeric',
//            'details.*.sale_off'        => 'nullable|numeric',
//            'details.*.gift_product_id' => 'nullable|exists:products,id,deleted_at,NULL',
//            'details.*.discount'        => 'nullable|numeric',
//            'details.*.qty_from'        => 'nullable|numeric',
//            'details.*.qty_to'          => 'nullable|numeric',
//            'details.*.customer_type'   => 'required|in:USER,PARTNER,CUSTOMER',
        ];
    }

    protected function attributes()
    {
        return [
            'title'                     => Message::get("title"),
            'code'                      => Message::get("code"),
            'from'                      => Message::get("from"),
            'to'                        => Message::get("to"),
            'type'                      => Message::get("type"),
//            'details'                   => Message::get("detail"),
//            'details.*.qty'             => Message::get("quantity"),
//            'details.*.product_id'      => Message::get("product_id"),
//            'details.*.category_id'     => Message::get("categories"),
//            'details.*.point'           => Message::get("point"),
//            'details.*.price'           => Message::get("price"),
//            'details.*.sale_off'        => Message::get("sale_off"),
//            'details.*.gift_product_id' => Message::get("products"),
//            'details.*.discount'        => Message::get("discount"),
//            'details.*.qty_from'        => Message::get("qty"),
//            'details.*.qty_to'          => Message::get("qty"),
        ];
    }
}
