<?php
/**
 * User: dai.ho
 * Date: 29/06/2020
 * Time: 1:17 PM
 */

namespace App\V1\Validators\ShippingOrder;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ShippingOrderCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            "ship_fee"                  => "nullable|numeric",
            "free_ship"                 => "nullable|in:0,1",
            "pick_money"                => "nullable|numeric",
            "details"                   => "nullable|array",
            "details.*.order_detail_id" => "required|exists:order_details,id,deleted_at,NULL",
            "details.*.batch_id"        => "required|exists:batches,id,deleted_at,NULL",
            "details.*.unit_id"         => "required|exists:units,id,deleted_at,NULL",
            "details.*.warehouse_id"    => "required|exists:warehouses,id,deleted_at,NULL",
            "details.*.ship_qty"        => "required|numeric",
        ];
    }

    protected function attributes()
    {
        return [
        ];
    }
}