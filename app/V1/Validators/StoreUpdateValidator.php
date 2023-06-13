<?php
/**
 * User: kpistech2
 * Date: 2019-11-03
 * Time: 14:59
 */

namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Store;
use App\Supports\Message;
use Illuminate\Http\Request;

class StoreUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'          => 'required|unique_update:stores',
            'name'          => 'required|max:50',
            'lat'           => 'required',
            'long'          => 'required',
            'email'         => 'nullable|email',
            'email_notify'  => 'nullable|email',
            'warehouse_id'  => 'nullable|exists:warehouses,id,deleted_at,NULL',
            'company_id'    => 'nullable|exists:companies,id,deleted_at,NULL',
            'address'       => 'required',
            'contact_phone' => 'required',
            'city_code'     => 'required|exists:cities,code,deleted_at,NULL',
            'district_code' => 'required|exists:districts,code,deleted_at,NULL',
            'ward_code'     => 'nullable|exists:wards,code,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'code'          => Message::get("code"),
            'name'          => Message::get("alternative_name"),
            'lat'           => Message::get("lat"),
            'long'          => Message::get("long"),
            'email'         => Message::get("email"),
            'email_notify'  => Message::get("email"),
            'warehouse_id'  => Message::get("warehouses"),
            'company_id'    => Message::get("company_id"),
            'address'       => Message::get("address"),
            'contact_phone' => Message::get("phone"),
            'city_code'     => Message::get("cities"),
            'district_code' => Message::get("districts"),
            'ward_code'     => Message::get("wards"),
        ];
    }
}