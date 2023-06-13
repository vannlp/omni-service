<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class PriceDetailCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'details'                      => 'required|array',
            'details.*.product_id'         => 'required|exists:products,id,deleted_at,NULL',
            'details.*.product_price'      => 'required',
            'status'                       => 'required',
            'from'                         => 'required|date_format:Y-m-d H:i:s',
            'to'                           => 'required|date_format:Y-m-d H:i:s',
//            'details.*.product_variant_id' => 'required|exists:product_variants,id,deleted_at,NULL'
        ];
    }

    protected function attributes()
    {
        return [
            'details'                 => Message::get("details"),
            'details.*.product_id'    => Message::get("product_id"),
            'details.*.product_price' => Message::get("product_price"),
            'status'                  => Message::get("status"),
            'from'                    => Message::get("from"),
            'to'                      => Message::get("to"),
//            'product_variant_id'      => Message::get("product_variant_id"),
        ];
    }
}