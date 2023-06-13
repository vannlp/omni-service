<?php

/**
 * User: dai.ho
 * Date: 27/01/2021
 * Time: 10:48 AM
 */

namespace App\Sync\Models;

use App\Routing;
use App\Supports\Message;
use App\TM;
use App\V1\Models\AbstractModel;

class RoutingModel extends AbstractModel
{
    public function __construct(Routing $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $companyId                       = !empty($input['company_id']) ? $input['company_id'] : null;

        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $routing_model = Routing::where('routing_id', $id)->first();
            if (empty($routing_model)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $routing_model->routing_id         = $input['routing_id'] ?? $routing_model['routing_id'];
            $routing_model->routing_code       = $input['routing_code'] ?? $routing_model['routing_code'];
            $routing_model->routing_name       = $input['routing_name'] ?? $routing_model['routing_name'];
            $routing_model->shop_id            = $input['shop_id'] ?? $routing_model['shop_id'];
            $routing_model->status             = $input['status'] ?? $routing_model['status'];
            $routing_model->updated_at         = date("Y-m-d H:i:s", time());
            $routing_model->store_id           = TM::getIDP()->store_id;
            $routing_model->company_id         = TM::getIDP()->company_id;
            $routing_model->updated_by         = TM::getIDP()->sync_name;
            $routing_model->save();
        } else {
            $param                           = [
                'routing_id'        =>  $input['routing_id'],
                'routing_code'      =>  $input['routing_code'],
                'routing_name'      =>  $input['routing_name'] ?? null,
                'shop_id'           =>  $input['shop_id'] ?? null,
                'status'            =>  $input['status'] ?? null,
                'created_at'        =>  date("Y-m-d H:i:s", time()),
                'created_by'        =>  TM::getIDP()->sync_name,
                'updated_at'        =>  $input['updated_at'] ?? null,
                'updated_by'        =>  $input['updated_by'] ?? null,
                'store_id'          =>  TM::getIDP()->store_id,
                'company_id'        =>  TM::getIDP()->company_id,
            ];
            $routing_model = $this->create($param);
        }
        return $routing_model;
    }
}
