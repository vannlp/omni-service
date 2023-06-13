<?php


namespace App\V1\Models;

use App\ConfigShipping;
use App\ConfigShippingCondition;
use App\Supports\TM_Error;

class ConfigShippingConditionModel extends AbstractModel
{
    public function __construct(ConfigShippingCondition $model = null)
    {
        parent::__construct($model);
    }

    public function config_shipping_create_conditions(array $input)
    {
        try {
            $this->model->insert($input);
       } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }
    }
    public function config_shipping_update_conditions(array $input)
    {
        try {
            $this->model->update($input);
       } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }
    }
    

}