<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class DistributorAfterCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'    => 'required',
            'city_code' => 'required',
            'district_code' => 'required',
            'ward_code' => 'required',
        ];
    }
    protected function attributes()
    {
        return [];
    }
}