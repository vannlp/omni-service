<?php

namespace App\V1\Models;

use App\PartnerNutifood;
use App\TM;
use App\Supports\TM_Error;
use App\Supports\Message;

class PartnerNutifoodModel extends AbstractModel
{
    public function __construct(PartnerNutifood $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;

        if($id){
            $partner = PartnerNutifood::where('id',$id)->first();

            if(empty($partner)){
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }

            $partner->name = array_get($input, 'name',$partner->name);
            $partner->phone = array_get($input, 'phone',$partner->phone);
            $partner->email = array_get($input, 'email',$partner->email);
            $partner->id_number = array_get($input, 'id_number',$partner->id_number);
            $partner->id_images = array_get($input, 'id_images',$partner->id_images);
            $partner->cooperate = implode(',', $input['cooperate']) ?? $partner->cooperate;
            $partner->tax = array_get($input, 'tax',$partner->tax);
            $partner->address = array_get($input, 'address',$partner->address);
            $partner->bank_name = array_get($input, 'bank_name',$partner->bank_name);
            $partner->bank_account_name = array_get($input, 'bank_account_name',$partner->bank_account_name);
            $partner->bank_account_number = array_get($input, 'bank_account_number',$partner->bank_account_number);
            $partner->bank_branch = array_get($input, 'bank_branch',$partner->bank_branch);
            $partner->anwser_1 = array_get($input, 'anwser_1',$partner->anwser_1);
            $partner->anwser_2 = implode(',', $input['anwser_2']) ?? $partner->anwser_2;
            $partner->anwser_3 = array_get($input, 'anwser_3',$partner->anwser_3);
            $partner->anwser_4 = array_get($input, 'anwser_4',$partner->anwser_4);
            $partner->anwser_5 = array_get($input, 'anwser_5',$partner->anwser_5);
            $partner->city_code = array_get($input, 'city_code',$partner->city_code);
            $partner->ward_code = array_get($input, 'ward_code',$partner->ward_code);
            $partner->district_code = array_get($input, 'district_code',$partner->district_code);
            $partner->city_name = array_get($input, 'city_name',$partner->city_name);
            $partner->district_name = array_get($input, 'district_name',$partner->district_name);
            $partner->ward_name = array_get($input, 'ward_name',$partner->ward_name);
            $partner->bank_code = array_get($input, 'bank_code',$partner->bank_code);
            $partner->updated_at = date("Y-m-d H:i:s", time());
            $partner->updated_by = TM::getCurrentUserId();

            $partner->save();
   
        }else{
            $param = [
                'name' => $input['name'],
                'phone' => $input['phone'],
                'email' => $input['email'],
                'id_number' => $input['id_number'],
                'id_images' => $input['id_images'],
                'cooperate' =>implode(',', $input['cooperate']) ?? null,
                'tax' => $input['tax'] ?? null,
                'address' => $input['address'],
                'bank_name' => $input['bank_name'],
                'bank_account_name' => $input['bank_account_name'],
                'bank_account_number' => $input['bank_account_number'],
                'bank_branch' => $input['bank_branch'],
                'anwser_1' => $input['anwser_1'] ?? NULL,
                'anwser_2' => implode(',', $input['anwser_2']) ?? null,
                'anwser_3' => $input['anwser_3'] ?? NULL,
                'anwser_4' => $input['anwser_4'] ?? NULL,
                'anwser_5' => $input['anwser_5'] ?? NULL,
                "city_code" => $input['city_code'] ?? NULL,
                "ward_code" => $input['ward_code'] ?? NULL,
                "district_code" => $input['district_code'] ?? NULL,
                "city_name" => $input['city_name'] ?? NULL,
                "district_name" => $input['district_name'] ?? NULL,
                "ward_name" => $input['ward_name'] ?? NULL,
                "bank_code" => $input['bank_code'] ?? NULL,
                'created_by' => TM::getCurrentUserId(),
            ];

            $partner = $this->create($param);

        }
            return $partner;
    }

}
