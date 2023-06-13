<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class PartnerNutifoodCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'name' => 'required',
            'phone'  => 'max:10|required|unique:partner_nutifoods,phone',
            'email' => 'required',
            'id_number' => 'numeric|required',
            'id_images' => 'required',
            'cooperate' => 'required',
            'address' => 'required',
            'bank_name' => 'required',
            'bank_account_name' => 'required',
            'bank_account_number' => 'required',
            'bank_branch' => 'required',
            'anwser_1' => 'required',
            'anwser_2' => 'required',
            'anwser_3' => 'required',
            'anwser_4' => 'required',
            'anwser_5' => 'required',
            "city_code" => 'required',
            "ward_code" => 'required',
            "district_code" => 'required',
            "city_name" => 'required',
            "district_name" => 'required',
            "ward_name" => 'required',
            "bank_code" => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'name' => Message::get("name"),
            'phone' => Message::get("phone"),
            'email' => Message::get("email"),
            'id_number' => Message::get("id_number"),
            'id_images' => Message::get("id_images"),
            'cooperate' => Message::get("cooperate"),
            'address' => Message::get("address"),
            'bank_name' => Message::get("bank_name"),
            'bank_account_name' => Message::get("bank_account_name"),
            'bank_account_number' => Message::get("bank_account_number"),
            'bank_branch' => Message::get("bank_branch"),
            'anwser_1' => Message::get("anwser"),
            'anwser_2' => Message::get("anwser"),
            'anwser_3' => Message::get("anwser"),
            'anwser_4' => Message::get("anwser"),
            'anwser_5' => Message::get("anwser"),
            "city_code" => Message::get("city_code"),
            "ward_code" => Message::get("ward_code"),
            "district_code" => Message::get("district_code"),
            "city_name" => Message::get("cities"),
            "district_name" => Message::get("districts"),
            "ward_name" => Message::get("wards"),
            "bank_code" => Message::get("bank_code"),
            
        ];
    }
}
