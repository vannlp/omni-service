<?php


namespace App\V1\Transformers\Partner_nutifood;


use App\PartnerNutifood;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class PartnerNutifoodListTransformer extends TransformerAbstract
{
    public function transform(PartnerNutifood $formPartnerNutifood)
    {
        $url = [];
        if(!empty($formPartnerNutifood->id_images)){
            $img = explode(",", $formPartnerNutifood->id_images);

            for($i= 0; $i< count($img); $i++){
                $url[$i]['url'] = env('GET_FILE_URL').$img[$i];
            }
        }
        
        try {
            return [
                'id' =>$formPartnerNutifood->id,
                'name' =>$formPartnerNutifood->name,
                'phone' =>$formPartnerNutifood->phone,
                'email' =>$formPartnerNutifood->email,
                'id_number' =>$formPartnerNutifood->id_number,
                'id_images' =>$formPartnerNutifood->id_images,
                'id_image_url' => $url,
                'cooperate' =>$formPartnerNutifood->cooperate,
                'tax' =>$formPartnerNutifood->tax,
                'address' =>$formPartnerNutifood->address,
                "bank_code" =>$formPartnerNutifood->bank_code,
                'bank_name' =>$formPartnerNutifood->bank_name,
                'bank_account_name' =>$formPartnerNutifood->bank_account_name,
                'bank_account_number' =>$formPartnerNutifood->bank_account_number,
                'bank_branch' =>$formPartnerNutifood->bank_branch,
                'anwser_1' =>$formPartnerNutifood->anwser_1,
                'anwser_2' =>$formPartnerNutifood->anwser_2,
                'anwser_3' =>$formPartnerNutifood->anwser_3,
                'anwser_4' =>$formPartnerNutifood->anwser_4,
                'anwser_5' =>$formPartnerNutifood->anwser_5,
                "city_code" =>$formPartnerNutifood->city_code,
                "ward_code" =>$formPartnerNutifood->ward_code,
                "district_code" =>$formPartnerNutifood->district_code,
                "city_name" =>$formPartnerNutifood->city_name,
                "district_name" =>$formPartnerNutifood->district_name,
                "ward_name" =>$formPartnerNutifood->ward_name,
                'created_at' => date('d-m-Y', strtotime($formPartnerNutifood->created_at)),
                'created_by' => $formPartnerNutifood->created_by,
                'updated_at' => date('d-m-Y', strtotime($formPartnerNutifood->updated_at)),
                'updated_by' => $formPartnerNutifood->created_by,
            ];
        }
        catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
