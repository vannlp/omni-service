<?php
/**
 * User: SANG NGUYEN
 * Date: 2/24/2019
 * Time: 4:55 PM
 */

namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class SalePriceUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'product_id'         => 'required|exists:products,id,deleted_at,NULL',
            'customer_group_ids' => 'required|array',
            'unit_id'            => 'required|exists:units,id,deleted_at,NULL',
            'price_id'           => 'required|exists:prices,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'product_id'         => Message::get("product_id"),
            'unit_id'            => Message::get("unit_id"),
            'customer_group_ids' => Message::get("customer_group_ids"),
            'price_id'           => Message::get("price_id"),
        ];
    }
}