<?php

namespace App;


class RentGround extends BaseModel
{
    protected $table = 'rent_grounds';

    protected $fillable = [
        'name',
        'phone',
        'address',
        'length',
        'width',
        'length',
        'height',
        'area',
        'area_anwser_1',
        'area_anwser_2',
        'area_anwser_3',
        'area_anwser_4',
        'area_anwser_5',
        'thumbnail',
        'price',
        'rush_to_rent',
        "city_code",
        "district_code",
        "ward_code" ,
        "city_name" ,
        "district_name",
        "ward_name",
        'deleted',
        'deleted_at',
        'deleted_by',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];
}
