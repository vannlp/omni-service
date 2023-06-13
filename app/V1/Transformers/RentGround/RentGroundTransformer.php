<?php


namespace App\V1\Transformers\RentGround;

use App\RentGround;
use League\Fractal\TransformerAbstract;

class RentGroundTransformer extends TransformerAbstract
{
    public function transform(RentGround $rentGround)
    {
        $url = [];
        if (!empty($rentGround->thumbnail)) {
            $img = explode(",", $rentGround->thumbnail);

            for ($i = 0; $i < count($img); $i++) {
                $url[$i]['url'] = env('GET_FILE_URL') . $img[$i];
            }
        }
        return [
            'id'            => $rentGround->id,
            'name'          => $rentGround->name,
            'address'       => $rentGround->address,
            'phone'         => $rentGround->phone,
            'price'         => $rentGround->price,
            'width'         => $rentGround->width,
            'height'        => $rentGround->height,
            'length'        => $rentGround->length,
            'area'          => $rentGround->area,
            'area_anwser_1' => $rentGround->area_anwser_1,
            'area_anwser_2' => $rentGround->area_anwser_2,
            'area_anwser_3' => $rentGround->area_anwser_3,
            'area_anwser_4' => $rentGround->area_anwser_4,
            'area_anwser_5' => $rentGround->area_anwser_5,
            'thumbnail_url' => $url,
            'thumbnail'     => $rentGround->thumbnail,
            'rush_to_tent'  => $rentGround->rush_to_rent,
            "city_code"     => $rentGround->city_code,
            "district_code" => $rentGround->district_code,
            "ward_code"     => $rentGround->ward_code,
            "city_name"     => $rentGround->city_name,
            "district_name" => $rentGround->district_name,
            "ward_name"     => $rentGround->ward_name,
            'created_at'    => date('d-m-Y', strtotime($rentGround->created_at)),
            'updated_at'    => date('d-m-Y', strtotime($rentGround->updated_at)),

        ];
    }
}