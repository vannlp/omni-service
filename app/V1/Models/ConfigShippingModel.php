<?php


namespace App\V1\Models;

use App\ConfigShipping;
use App\ConfigShippingCondition;
use App\Supports\TM_Error;
use App\Supports\Message;
use DateTime;
use Html2Text\StrToUpperTest;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ConfigShippingModel extends AbstractModel
{
    public function __construct(ConfigShipping $model = null)
    {
        parent::__construct($model);
    }

    public function insert($input)
    {
        try {     
            $shipping_parter_code  = strtoupper($input['shipping_partner_code']);
            $created_date = date('Y-m-d H:i:s', time());
            $param = [
                'code'                                  =>  array_get($input, 'code', null),
                'delivery_code'                         =>  array_get($input, 'delivery_code', null),
                'delivery_name'                         =>  array_get($input, 'delivery_name', null),
                'time_from'                             =>  array_get($input, 'time_from', null),
                'time_to'                               =>  array_get($input, 'time_to', null),
                'time_type'                             =>  array_get($input, 'time_type', null),
                'shipping_partner_code'                 =>  $shipping_parter_code ?? null,
                'shipping_partner_name'                 =>  array_get($input, 'shipping_partner_name', null),
                'shipping_fee'                          =>  array_get($input, 'shipping_fee', null),
                'is_active'                             =>  array_get($input, 'is_active', 0),       
            ];

            $config_shipping = $this->create($param);
            $inputcondition = [];

            foreach($input['shipping_conditions'] as $condition) {
                if($condition['condition_name'] == ""){
                    throw new \Exception("Không được bỏ trống điều kiện");
                }
                $inputcondition[] = [
                    'config_shipping_id' => $config_shipping->id ?? null,
                    'config_shipping_code' => $config_shipping->code ?? null, 
                    'condition_name'                         =>  array_get($condition, 'condition_name', null),
                    'condition_type'                         =>  !empty($condition['condition_type']) ? $condition['condition_type'] : null,
                    'condition_number'                       =>  !empty($condition['condition_number']) ? $condition['condition_number'] : null,
                    'condition_arrays'                       =>  !empty($condition['condition_arrays']) && is_array($condition['condition_arrays'])  ? json_encode($condition['condition_arrays']) : null, 
                    'created_at'                             => $created_date ?? null
                ];
            }

            // dd($inputcondition);

           (new ConfigShippingConditionModel())->config_shipping_create_conditions($inputcondition);


        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }
    }
    public function update($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        $config_shipping = ConfigShipping::findOrFail($id);
        if (empty($config_shipping)) {
            throw new \Exception(Message::get("V003", "ID: #$id"));
        }
        $config_shipping->code = $input['code'];
        $config_shipping->delivery_code = $input['delivery_code'];
        $config_shipping->delivery_name = $input['delivery_name'];
        $config_shipping->time_from = $input['time_from'];
        $config_shipping->time_to = $input['time_to'];
        $config_shipping->time_type = $input['time_type'];
        $config_shipping->shipping_partner_code = $input['shipping_partner_code'];
        $config_shipping->shipping_partner_name = $input['shipping_partner_name'];
        $config_shipping->shipping_fee = $input['shipping_fee'];
        $config_shipping->save();

        $inputcondition = [];

        $condition_shipping_ids = array_pluck($input['shipping_conditions'], 'id');
        ConfigShippingCondition::whereNotIn('id', $condition_shipping_ids)->where('config_shipping_id',$config_shipping->id)->delete();

        if($input['shipping_conditions'] == []){
            throw new HttpException(400, "Không được bỏ trống điều kiện");
        }
        foreach($input['shipping_conditions'] as $condition) {
            $id_condition = $condition['id'];
            $check = ConfigShippingCondition::where('id', $id_condition)->exists();
            $is_update = false;
            if($check) {
                $ConfigShippingCondition = ConfigShippingCondition::findOrFail($id_condition);
                $is_update = true;
            }else{
                $ConfigShippingCondition = new ConfigShippingCondition();
                $is_update = false;
                // $ConfigShippingCondition
            }
            $inputcondition = [
                'config_shipping_id'                     => $config_shipping->id,
                'config_shipping_code'                   => $config_shipping->code, 
                'condition_name'                         =>  array_get($condition, 'condition_name', null),
                'condition_type'                         =>  array_get($condition, 'condition_type', null),
                'condition_number'                       =>  !empty($condition['condition_number']) ?$condition['condition_number'] : null ,
                'condition_arrays'                       =>  !empty($condition['condition_arrays']) && is_array($condition['condition_arrays'])  ? json_encode($condition['condition_arrays']) : null, 
            ];
            if($is_update) {
                $ConfigShippingCondition->update($inputcondition);
            }else{
                // dd($inputcondition);
                $ConfigShippingCondition->create($inputcondition);
            }
        }


        // (new ConfigShippingConditionModel())->config_shipping_update_conditions($inputcondition);

    }
    
    public function searchConfigShipping($input = [], $with = [], $limit = null)
    {
        $query = ConfigShipping::model();
        if(!empty($input['code'])) {
            $query->where('code', $input['code']);
        }
        if(!empty($input['delivery_code'])) {
            $query->where('delivery_code', $input['delivery_code']);
        }
        if(!empty($input['delivery_name'])) {
            $query->where('delivery_name', $input['delivery_name']);
        }
        if(!empty($input['time_from'])) {
            $query->where('time_from', $input['time_from']);
        }
        if(!empty($input['time_to'])) {
            $query->where('time_to', $input['time_to']);
        }
        if(!empty($input['time_type'])) {
            $query->where('time_type', $input['time_type']);
        }
        if(!empty($input['shipping_partner_code'])) {
            $query->where('shipping_partner_code', $input['shipping_partner_code']);
        }
        if(!empty($input['shipping_partner_name'])) {
            $query->where('shipping_partner_name', $input['shipping_partner_name']);
        }
        if(!empty($input['shipping_fee'])) {
            $query->where('shipping_fee', $input['shipping_fee']);
        }
        if(!empty($input['shipping_fee'])) {
            $query->where('shipping_fee', $input['shipping_fee']);
        }
        // $query = $query->whereNull('deleted_at');


        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                if (!empty($input['sort']['date'])) {
                    return $query->get();
                } else {
                    return $query->paginate($limit);
                }
            }
        } else {
            if (!empty($input['sort']['date'])) {
                return $query->get();
            } else {
                return $query->get();
            }
        }
        
    }
}