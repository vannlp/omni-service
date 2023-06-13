<?php

/**
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:34 PM
 */

namespace App\V1\Models;

use App\Supports\Message;
use App\Region;
use App\TM;
use phpDocumentor\Reflection\Types\Nullable;

class RegionModel extends AbstractModel
{
    public function __construct(Region $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {

        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $region = Region::find($id);
            if (empty($region)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $region->code               = array_get($input, 'code', $region->code);
            $region->name               = array_get($input, 'name', $region->name);
            $region->city_code          = array_get($input, 'city_code', $region->city_code);
            $region->city_full_name     = array_get($input, 'city_full_name', $region->city_full_name);
            $region->district_code      = array_get($input, 'district_code', $region->district_code);
            $region->district_full_name = array_get($input, 'district_full_name', $region->district_full_name);
            $region->ward_code          = array_get($input, 'ward_code', $region->ward_code);
            $region->ward_full_name     = array_get($input, 'ward_full_name', $region->ward_full_name);
            $region->distributor_id     = array_get($input, 'distributor_id', $region->distributor_id);
            $region->distributor_code   = array_get($input, 'distributor_code', $region->distributor_code);
            $region->distributor_name   = array_get($input, 'distributor_name', $region->distributor_name);
            $region->updated_at         = date("Y-m-d H:i:s", time());
            $region->updated_by         = TM::getCurrentUserId();
            $region->save();
        } else {
            $param  = [
                'code'               => $input['code'],
                'name'               => $input['name'],
                'city_code'          => $input['city_code'],
                'city_full_name'     => $input['city_full_name'],
                'district_code'      => $input['district_code'],
                'district_full_name' => $input['district_full_name'],
                'ward_code'          => $input['ward_code'],
                'ward_full_name'     => $input['ward_full_name'],
                'distributor_id'     => $input['distributor_id'],
                'distributor_code'   => $input['distributor_code'],
                'distributor_name'   => $input['distributor_name'],
                'store_id'           => TM::getCurrentStoreId(),
                'company_id'         => TM::getCurrentCompanyId(),
            ];
            $region = $this->create($param);
        }
        return $region;
    }
}
