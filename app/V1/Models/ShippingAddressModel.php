<?php


namespace App\V1\Models;


use App\ShippingAddress;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;

class ShippingAddressModel extends AbstractModel
{
    /**
     * ShippingAddressModel constructor.
     * @param ShippingAddress|null $model
     */
    public function __construct(ShippingAddress $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        try {
            if($input['is_default'] == 1){
                $check_default = ShippingAddress::model()
                    ->where('user_id', TM::getCurrentUserId())
                    ->where('is_default',1)->first();
                if($check_default){
                    $check_default->is_default = 0;
                    $check_default->save();
                }
            }
            $id = !empty($input['id']) ? $input['id'] : 0;
            if ($id) {
                $result = ShippingAddress::find($id);
                if (empty($result)) {
                    throw new \Exception(Message::get("V003", "ID: #$id"));
                }
                $result->user_id        = TM::getCurrentUserId();
                $result->full_name      = array_get($input, 'full_name', $result->full_name);
                $result->phone          = array_get($input, 'phone', $result->phone);
                $result->city_code      = array_get($input, 'city_code', $result->city_code);
                $result->district_code  = array_get($input, 'district_code', $result->district_code);
                $result->ward_code      = array_get($input, 'ward_code', $result->ward_code);
                $result->street_address = array_get($input, 'street_address', $result->street_address);
                $result->is_default     = array_get($input, 'is_default', $result->is_default);
                $result->updated_at     = date("Y-m-d H:i:s", time());
                $result->updated_by     = TM::getCurrentUserId();
                $result->save();
            } else {
                $param  = [
                    'user_id'        => TM::getCurrentUserId(),
                    'full_name'      => array_get($input, 'full_name', null),
                    'phone'          => array_get($input, 'phone', null),
                    'city_code'      => array_get($input, 'city_code', null),
                    'district_code'  => array_get($input, 'district_code', null),
                    'ward_code'      => array_get($input, 'ward_code', null),
                    'street_address' => array_get($input, 'street_address', null),
                    'company_id'     => TM::getCurrentCompanyId(),
                    'store_id'       => TM::getCurrentStoreId(),
                    'is_default'     => array_get($input, 'is_default', 0),
                ];
                $result = $this->create($param);
            }
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }

        return $result;
    }
}