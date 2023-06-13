<?php

namespace App\V1\Validators\RentGround;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class RentGroundCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [

            'name' => 'required',
            'phone'  => 'max:10|required|unique:rent_grounds,phone',
            'address' => 'required',
            'length' => 'required|numeric',
            'width' => 'required|numeric',
            'height' => 'required|numeric',
            'area' => 'required|numeric',
            'price' => 'required',
        ];
    }
    protected function attributes()
    {
        return [
            'name' => Message::get('name'),
            'phone' => Message::get('phone'),
            'address' => Message::get('address'),
            'length' => Message::get('length'),
            'width' => Message::get('width'),
            'height' => Message::get('height'),
            'area' => Message::get('area'),
            'price' => Message::get('price'),
        ];
    }
}
