<?php
/**
 * User: dai.ho
 * Date: 3/06/2020
 * Time: 10:49 AM
 */

namespace App\V1\Validators\Region;


use App\Distributor;
use App\Http\Validators\ValidatorBase;
use App\Region;
use App\Supports\Message;
use App\TM;

class RegionUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id' => 'required',
            'name' => ['nullable',
                function ($attribute, $value, $fail) {
                    $item = Region::model()->where('code', $value)->where('store_id', TM::getCurrentStoreId())
                        ->where('company_id', TM::getCurrentCompanyId())
                        ->get()->toArray();
                    if (!empty($item) && count($item) > 0) {
                        if (count($item) > 1) {
                            return $fail(Message::get("unique", "$attribute: #$value"));
                        }
                    }
                    return true;
                }
            ],
            'code' => ['nullable',
                function ($attribute, $value, $fail) {
                    $item = Region::model()->where('code', $value)->where('store_id', TM::getCurrentStoreId())
                        ->where('company_id', TM::getCurrentCompanyId())
                        ->get()->toArray();
                    if (!empty($item) && count($item) > 0) {
                        if (count($item) > 1) {
                            return $fail(Message::get("unique", "$attribute: #$value"));
                        }
                    }
                    return true;
                }
            ],
            'distributor_code' => [
                'required',
                'max:20'
            ],
        ];
    }

    protected function attributes()
    {
        return [
            'name' => Message::get("name"),
            'code' => Message::get("code"),
            'distributor_code' => Message::get("distributor_code"),
        ];
    }
}