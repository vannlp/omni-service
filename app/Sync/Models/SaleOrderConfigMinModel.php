<?php


namespace App\Sync\Models;

use App\SaleOrderConfigMin;
use App\Supports\Message;
use App\TM;
use App\V1\Models\AbstractModel;


class SaleOrderConfigMinModel extends AbstractModel
{
    public function __construct(SaleOrderConfigMin $model = null)
    {
        parent::__construct($model);
    }
    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $saleOrderMin = SaleOrderConfigMin::find($id);
            if (empty($saleOrderMin)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $saleOrderMin->shop_id      = array_get($input, 'shop_id', null);
            $saleOrderMin->from_date    = array_get($input, 'avatar_id', null);
            $saleOrderMin->to_date      = array_get($input, 'avatar', null);
            $saleOrderMin->product_id   = array_get($input, 'avatar', null);
            $saleOrderMin->unit_id      = array_get($input, 'avatar', null);
            $saleOrderMin->quantity     = array_get($input, 'avatar', null);
            $saleOrderMin->status       = array_get($input, 'avatar', null);
            $saleOrderMin->updated_at   = date("Y-m-d H:i:s", time());
            $saleOrderMin->updated_by   = TM::getCurrentUserId();
            $saleOrderMin->save();
        } else {
            $param = [
                'shop_id'              => $input['shop_id'],
                'from_date'            => $input['from_date'],
                'to_date'              => $input['to_date'],
                'product_id'           => $input['product_id'],
                'unit_id'              => $input['unit_id'],
                'quantity'             => $input['quantity'],
                'status'               => $input['status'],
            ];
            $saleOrderMin = $this->create($param);
        }
        return $saleOrderMin;
    }
}