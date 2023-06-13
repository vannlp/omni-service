<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:55 PM
 */

namespace App\Sync\Transformers;

use App\NinjaSyncFilterPost;
use App\SaleOrderConfigMin;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class SaleOrderConfigMinTransformer extends TransformerAbstract
{
    public function transform(SaleOrderConfigMin $sale)
    {
        try {
            return [
                'id'              => $sale->id,
                'shop_id'         => $sale->shop_id,
                'shop_code'       => array_get($sale, 'shop.shop_code', null),
                'shop_name'       => array_get($sale, 'shop.shop_name', null),
                'from_date'       => $sale->from_date,
                'to_date'         => $sale->to_date,
                'product_id'      => $sale->product_id,
                'product_code'    => array_get($sale, 'product.code', null),
                'product_name'    => array_get($sale, 'product.name', null),
                'unit_id'         => $sale->unit_id,
                'unit_code'       => array_get($sale, 'units.code', null),
                'unit_name'       => array_get($sale, 'units.name', null),
                'quantity'        => $sale->quantity,
                'status'          => $sale->status,
                'deleted'         => $sale->deleted,
                'updated_by'      => $sale->updated_by,
                'created_by'      => $sale->created_by,
                'updated_at'      => $sale->updated_at,
                'created_at'      => $sale->created_at,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
