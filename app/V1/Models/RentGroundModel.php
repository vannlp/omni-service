<?php


namespace App\V1\Models;


use App\RentGround;
use App\Supports\Message;

class RentGroundModel extends AbstractModel
{
    public function __construct(RentGround $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $rentGround = RentGround::find($id);
            if (empty($rentGround)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $rentGround->name          = array_get($input, 'name', $rentGround->name);
            $rentGround->phone         = array_get($input, 'phone', $rentGround->phone);
            $rentGround->address       = array_get($input, 'address', $rentGround->address);
            $rentGround->length        = array_get($input, 'length', $rentGround->length);
            $rentGround->height        = array_get($input, 'height', $rentGround->height);
            $rentGround->area          = array_get($input, 'area', $rentGround->area);
            $rentGround->area_anwser_1 = isset($input['area_anwser_1']) ? implode(',', $input['area_anwser_1']) : $rentGround->area_anwser_1;
            $rentGround->area_anwser_2 = array_get($input, 'area_anwser_2', $rentGround->area_anwser_2);
            $rentGround->area_anwser_3 = array_get($input, 'area_anwser_3', $rentGround->area_anwser_3);
            $rentGround->area_anwser_4 = array_get($input, 'area_anwser_4', $rentGround->area_anwser_4);
            $rentGround->area_anwser_5 = array_get($input, 'area_anwser_5', $rentGround->area_anwser_5);
            $rentGround->thumbnail     = isset($input['thumbnail']) ? $input['thumbnail'] : $rentGround->thumbnail;
            $rentGround->price         = array_get($input, 'price', $rentGround->price);
            $rentGround->rush_to_rent  = isset($input['rush_to_rent']) ? implode(',', $input['rush_to_rent']) : $rentGround->rush_to_rent;
            $rentGround->city_code     = array_get($input, 'city_code', $rentGround->city_code);
            $rentGround->district_code = array_get($input, 'district_code', $rentGround->district_code);
            $rentGround->ward_code     = array_get($input, 'ward_code', $rentGround->ward_code);
            $rentGround->city_name     = array_get($input, 'city_name', $rentGround->city_name);
            $rentGround->district_name = array_get($input, 'district_name', $rentGround->district_name);
            $rentGround->ward_name     = array_get($input, 'ward_name', $rentGround->ward_name);
            $rentGround->save();
        } else {
            $createrentGround = [
                'name'          => $input['name'],
                'phone'         => $input['phone'],
                'address'       => $input['address'],
                'length'        => $input['length'],
                'width'         => $input['width'],
                'height'        => $input['height'],
                'area'          => $input['area'],
                'area_anwser_1' => isset($input['area_anwser_1']) ? implode(',', $input['area_anwser_1']) : null,
                'area_anwser_2' => $input['area_anwser_2'] ?? null,
                'area_anwser_3' => $input['area_anwser_3'] ?? null,
                'area_anwser_4' => $input['area_anwser_4'] ?? null,
                'area_anwser_5' => $input['area_anwser_5'] ?? null,
                'thumbnail'     => isset($input['thumbnail']) ? $input['thumbnail'] : null,
                'price'         => $input['price'] ?? null,
                'rush_to_rent'  => isset($input['rush_to_rent']) ? implode(',', $input['rush_to_rent']) : null,
                "city_code"     => $input['city_code'] ?? null,
                "district_code" => $input['district_code'] ?? null,
                "ward_code"     => $input['ward_code'] ?? null,
                "city_name"     => $input['city_name'] ?? null,
                "district_name" => $input['district_name'] ?? null,
                "ward_name"     => $input['ward_name'] ?? null,
            ];
            $rentGround       = $this->create($createrentGround);
        }
        return $rentGround;
    }


}