<?php

namespace App\V1\Validators;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ProductVariantUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        $input = $this->_input;
        return [
            'product_id'                        => 'required|integer|exists:products,id,deleted_at,NULL',
            'data'                              => 'required|array',
            'data.*.id'                         => 'integer|exists:product_variants,id,deleted,0',
            'data.*.product_code'               => 'string|max:50',
            'data.*.price'                      => 'nullable|numeric',
//            'data.*.inventory'                  => 'nullable|integer',
            'data.*.image'                      => 'nullable|string|max:255',
            'data.*.product_attributes'         => 'required|array',
            'data.*.product_attributes.*'       => 'exists:product_attributes,id,deleted_at,NULL',
            'data.*.promotions'                 => 'nullable|array',
            'data.*.promotions.*.id'            => 'integer|exists:product_variant_promotions,id,deleted_at,NULL',
            'data.*.promotions.*.user_group_id' => 'exists:user_groups,id,deleted_at,NULL'
        ];
    }

    protected function attributes()
    {
        return [
            'product_id'                        => Message::get("product_id"),
            'data.*.id'                         => Message::get("product_variant_id"),
            'data.*.product_code'               => Message::get("product_code"),
            'data.*.price'                      => Message::get("price"),
            'data.*.inventory'                  => Message::get("inventory"),
            'data.*.product_attributes'         => Message::get("product_attribute"),
            'data.*.promotions'                 => Message::get("promotion"),
            'data.*.promotions.*.id'            => Message::get("promotion"),
            'data.*.promotions.*.user_group_id' => Message::get("user_group_id"),
        ];
    }
}